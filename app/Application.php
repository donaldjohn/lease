<?php
namespace app;


use app\common\errors\AppException;
use app\common\errors\BaseException;
use app\common\errors\DataException;
use app\common\errors\MicroException;
use app\common\logger\business\Message;
use app\core\DI;
use Phalcon\Config;
use Phalcon\Db\Adapter;
use Phalcon\Di\FactoryDefault;
use Phalcon\Exception;
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use Phalcon\Loader;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Mvc\Model;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Router;
use Phalcon\Logger\Adapter\File as FileAdapter;
use app\core\handler\Handler as ErrorHandler;
use Phalcon\Mvc\Application as BaseApplication;
use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\Frontend\Data as FrontData;

class Application extends BaseApplication
{
    private $di;
    private $config;

    //请求ID，每一次请求期间唯一，预留用于跟踪请求
    private $requestId;

    //定义完config和app覆盖默认DI
    public function __construct(Config $config)
    {
        //设置时区
        date_default_timezone_set($config->app->timezone);
        $this->config = $config;
        $this->di = new FactoryDefault();
        $this->di->setShared('config', $config);
        $this->di->setShared('app', $this);
        parent::__construct($this->di);

        $this->init();
    }

    // 兼容cli模式下引入app
    public function __get($propertyName)
    {
        return $this->di->has($propertyName) ? $this->di->get($propertyName) : parent::__get($propertyName);
    }

    // 获取请求ID
    public function getRequestId()
    {
        if ($this->requestId == null)
            $this->requestId = $this->security->getRandom()->uuid();
        return $this->requestId;
    }

    // 获取请求根地址 ( http://www.host.com:port )
    public function getRequestRoot()
    {
        $request = $this->request;
        $serverPort = $request->getPort();

        $url = $request->getScheme() . "://" . $request->getServerName();
        if ($request->isSecure()) {
            if ($serverPort != 443) $url .= ":${serverPort}";
        } else {
            if ($serverPort != 80 ) $url .= ":${serverPort}";
        }
        return $url;
    }

    // 获取请求URL ( http://www.host.com:port/path?params)
    public function getRequestUrl()
    {
        return $this->getRequestRoot() . $this->request->getURI();
    }

    /**
     * @see \Phalcon\Mvc\Application.useImplicitView
     */
    private function init()
    {
        //model不验证not null
        Model::setup([ 'notNullValidations' => false ]);

        // TODO API 工程不需要 View，Web 工程需要
        $this->useImplicitView(false);

        $loader = new Loader();
        $loader->registerNamespaces(array("app" => APP_PATH))->register();

        //设置错误信息
        //ErrorHandler::register($this->config->env);

        $this->initEvents();
        $this->initModules();
        $this->initRouters();
        $this->initServices();
        $this->initDb();
        $this->initDw();
        $this->initRedis();
    }

    //初始化事件（开始启动多模块前，默认初始模块）
    private function initEvents()
    {
        $eventsManager = new EventsManager();
        $eventsManager->attach("application:beforeStartModule", function($event, Application $app, $name) {
            $app->dispatcher->setDefaultNamespace("app\\modules\\${name}");
        });

//        $eventsManager->attach("application:afterStartModule", function($event, $app, $module) { });
//
//        $eventsManager->attach("application:beforeSendResponse", function($event, Application $app, ResponseInterface $response) {
//            $dispatcher = $app->dispatcher;
//            $result= $dispatcher->getReturnedValue();
//            $resultContent = $response->getContent();;
//            $code = $this->response->getStatusCode();
//
//                //发送日志给日志服务
//                $message = new \app\common\logger\business\Message();
//                if ($code == 200) {
//                    $message->level = 'info';
//                } else {
//                    $message->level = 'error';
//                }
//                $message->bizModuleCode = '00000';
//                $message->timestamp = time();
//                $message->requestId = $this->app->getRequestId();
//                $message->desc = '业务日志';
//
//
//                $params = $this->busLogger->getInParameters();
//                $message->inParameter = json_encode($params,JSON_UNESCAPED_UNICODE);
//                $message->outParameter = json_encode($resultContent,JSON_UNESCAPED_UNICODE);
//                $this->busLogger->setMessage($message);
//                $this->busLogger->sendMessages($this->Zuul->log);
//
//
//            if (is_bool($result) || $result instanceof ResponseInterface) return;
//            if (is_object($result) || is_array($result)) {
//                $response->setJsonContent(array(
//                    "data" => $result,
//                    "requestId" => $app->getRequestId()
//                ), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
//            }
//        });

        $this->setEventsManager($eventsManager);
    }

    private function initModules()
    {
        $modules = array();
        $configModules = $this->config->modules;
        foreach ($configModules as $name => $prefix) {
            $modules[$name] = array("className" => "app\\modules\\${name}\\Module");
        }
        $this->registerModules($modules);

    }

    private function initRouters()
    {
        $config = $this->config;
        $this->di->setShared('router', function () use ($config) {
            $router = new Router(false);
            $router->removeExtraSlashes(true);
            $router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

            $router->notFound(array(
                'module'    => 'home',
                'controller' => 'index',
                'action'     => 'notFound',
                'exceptionSource'   => 'router.notFound'
            ));

            $modules = $config->modules;
            foreach ($modules as $name => $prefix) {
                $routerClass = "app\\modules\\${name}\\ModuleRouter";
                $routerGroup = new $routerClass($prefix, array('module' => $name));
                $router->mount($routerGroup);
            }


            return $router;
        });
    }

    private function initServices()
    {
        new DI($this->di,$this->config);
    }


    public function getExceptionArray(\Throwable $e)
    {
        $result = array(
            'class'      => get_class($e),
            'file'       => $e->getFile(),
            'code'       => $e->getCode(),
            'msg'        => $e->getMessage(),
            'line'       => $e->getLine()
        );
        $prev = $e->getPrevious();
        if (isset($prev)) {
            $result["prev"] = $this->getExceptionArray($prev);
        }
        return $result;
    }

    public function getRequestContext()
    {
        $di = $this->di;
        $request = $di->get('request');
        $dispatcher = $di->get('dispatcher');
        $result = array(
            'method'        => $request->getMethod(),
            'uri'           => $request->getURI(),
            'url'           => $this->getRequestUrl(),
            'route'         => $dispatcher->getModuleName() . '::' . $dispatcher->getControllerName() . '.' . $dispatcher->getActionName(),
            'namespace'     => $dispatcher->getNamespaceName(),
            'agent'         => $request->getUserAgent()
        );
        return $result;
    }

    public function addDebug(&$json, \Throwable $e, $force = false)
    {
        if ($this->config->app->debug != true && $force == false) return;

        $context = $this->getRequestContext();
        $exception = $this->getExceptionArray($e);
        $json["debug"] = array(
            "context" => $context,
            "exception" => $exception,
        );
    }

    public function handleException(\Throwable $e)
    {
        if ($e instanceof MicroException) {
            $this->ExceptionLogger = new  FileAdapter($this->config->log->path . '/app-error.log');
            $this->ExceptionLogger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
            $this->ExceptionLogger->error(json_encode(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()],JSON_UNESCAPED_UNICODE));
            return $this->handleAppException($e);
        }

//        if ($e instanceof DataException) {
//            $this->logger = new  FileAdapter(BASE_PATH . '/'. $this->config->log->path . '/app-service-data-' . date('Ymd') . '.log');
//            $this->logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
////            $this->logger->error((string)(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()]));
//            $this->logger->error(json_encode(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()],JSON_UNESCAPED_UNICODE));
//        }

        if ($e instanceof AppException) {
//     $this->logger = new  FileAdapter(BASE_PATH . '/'. $this->config->log->path . '/app-service-' . date('Ymd') . '.log');
//            $this->logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
//            $this->logger->error((string)(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()]));
            $this->logger->error(json_encode(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()],JSON_UNESCAPED_UNICODE));
            return $this->handleAppException($e);
        }

        if ($e instanceof BaseException) {
//     $this->logger = new  FileAdapter(BASE_PATH . '/'. $this->config->log->path . '/app-service-' . date('Ymd') . '.log');
//            $this->logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
//            $this->logger->error((string)(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()]));
            $this->logger->error(json_encode(['status' => $e->getStatusCode(),'message' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine()],JSON_UNESCAPED_UNICODE));
            return $this->handleBaseException($e);
        }

        $this->handleUnknownException($e);
    }

    public function handleAppException(AppException $e)
    {
//        if ($e instanceof RequestMethodException) {
//            return $this->handleRequestMethodException($e);
//        }
        $response = new Response();
        $response->setStatusCode($e->getStatusCode());
        $json = [
            "content" => "",
            "statusCode" => $e->getCode(),
            "msg" => $e->getMessage()
        ];
        $this->addDebug($json, $e);
        $response->setJsonContent($json, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        $response->sendHeaders();
        $response->sendCookies();
        $response->send();
        return $response;
    }

    public function handleBaseException(BaseException $e)
    {
//        if ($e instanceof RequestMethodException) {
//            return $this->handleRequestMethodException($e);
//        }
        $response = new Response();
        $json = [
            "content" => "",
            "statusCode" => $e->getCode(),
            "msg" => $e->getMessage()
        ];
        $this->addDebug($json, $e);
        $response->setJsonContent($json, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        $response->sendHeaders();
        $response->sendCookies();
        $response->send();
    }


    public function handleUnknownException(\Throwable $e)
    {
        $response = new Response();
        $json = array (
            "statusCode" => 500,
            "msg" => $e->getMessage(),
            "content" => '',
        );
        $this->addDebug($json, $e);
        $this->logException($e, 500);
        $response->setStatusCode(500);
        $response->setJsonContent($json, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        $response->sendHeaders();
        $response->sendCookies();
        $response->send();
    }

    public function logException(\Throwable $e, $seq = 0)
    {
        if ($seq == 0) $seq = $e->getCode();
        $json = array( 'code'   => $seq );
        $this->addDebug($json, $e, true);
        $this->ExceptionLogger = new  FileAdapter($this->config->log->path . '/app-error.log');
        $this->ExceptionLogger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
        $this->ExceptionLogger->critical(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    }


    private function initDb()
    {
        $config = $this->config;
        $this->di->setShared('db', function() use($config) {
            $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                'host'      => $config->db->host,
                'username'  => $config->db->username,
                'password'  => $config->db->password,
                'dbname'    => $config->db->dbname,
                //'charset'   => $config->db->charset,
            ));
//            if ($config->app->debug) {
//                $eventManager = new EventsManager();
//                $logger = new FileAdapter(BASE_PATH . '/'. $config->log->path . '/db-' . date('Ymd') . '.log');
//                $logger->setFormatter(new \Phalcon\Logger\Formatter\Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
//                $eventManager->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, DbAdapter $connection) use ($logger) {
//                    $sqlVariables = $connection->getSQLVariables();
//                    if (count($sqlVariables)) {
//                        $logger->info($connection->getSQLStatement() . ' PARAMS:' . json_encode($sqlVariables,JSON_UNESCAPED_UNICODE));
//                    } else {
//                        $logger->info($connection->getSQLStatement());
//                    }
//                });
//
//                $connection->setEventsManager($eventManager);
//            }
            return $connection;
        });
    }
    private function initDw()
    {
        $config = $this->config;
        // model事件日志
        $eventManager = null;
        if($config->app->debug) {
            $eventManager = new EventsManager();
            $logger = new FileAdapter($config->log->path . '/db.log');
            $logger->setFormatter(new \Phalcon\Logger\Formatter\Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
            $eventManager->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, Adapter $connection) use ($logger) {
                $sqlVariables = $connection->getSQLVariables();
                if (isset($sqlVariables)) {
                    $logger->info($connection->getSQLStatement() . ' PARAMS:' . json_encode($sqlVariables,JSON_UNESCAPED_UNICODE));
                } else {
                    $logger->info($connection->getSQLStatement());

                }
            });
        }
        // 初始化数据库
        $dbnames = $config->dw->dbnames->toArray();
        foreach ($dbnames as $k => $dbName){
            $this->di->setShared('dw_'.$k, function() use($config, $dbName, $eventManager) {
                $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
                    'host'      => $config->dw->host,
                    'username'  => $config->dw->username,
                    'password'  => $config->dw->password,
                    'dbname'    => $dbName,
                ]);

                if(!is_null($eventManager)) {
                    $connection->setEventsManager($eventManager);
                }
                return $connection;
            });
        }

        //硬件数据库
        $this->di->setShared('charging', function() use($config, $eventManager) {
            $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
                'host'      => $config->charging->host,
                'username'  => $config->charging->username,
                'password'  => $config->charging->password,
                'dbname'    => $config->charging->dbname,
            ]);
            if(!is_null($eventManager)) {
                $connection->setEventsManager($eventManager);
            }
            return $connection;
        });
        // 考试系统
        $this->di->setShared('phpems', function() use($config, $eventManager) {
            $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
                'host'      => $config->phpems->host,
                'username'  => $config->phpems->username,
                'password'  => $config->phpems->password,
                'dbname'    => $config->phpems->dbname,
            ]);
            if(!is_null($eventManager)) {
                $connection->setEventsManager($eventManager);
            }
            return $connection;
        });
    }

    /**
     * 增加redis
     */
    private function initRedis()
    {
        $config = $this->config;
        $this->di->setShared('modelsCache', function() use($config) {
            $frontCache = new FrontData(["lifetime" => 120,]);
            $cache = new Redis(
                $frontCache,
                [
                    'lifettime' => 120,
                    'host' => $config->redis->host,
                    'port' => 6379,
                    'auth' => $config->redis->password,
                    'persistent' => false
                ]
            );
            return $cache;
        });
    }

}
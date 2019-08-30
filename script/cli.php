<?php
    use Phalcon\Di\FactoryDefault\Cli as CliDI;
    use Phalcon\Cli\Console as ConsoleApp;
    use Phalcon\Loader;
    use app\common\logger\FileLogger;
    use Phalcon\Logger\Formatter\Line;
    use app\core\ServicesInterface;

    use Phalcon\Events\Manager as EventsManager;
    use Phalcon\Logger\Adapter\File as FileAdapter;
    use Phalcon\Db\Adapter as DbAdapter;
    date_default_timezone_set("PRC");
    define('BASE_PATH', dirname(__DIR__));
    define('APP_PATH', BASE_PATH . '/app');
    // 引入composer自动加载
    require __DIR__ . '/../vendor/autoload.php';
    // 使用CLI工厂类作为默认的服务容器
    $di = new CliDI();

    /**
     * 注册类自动加载器
     */
    $loader = new Loader();

    $loader->registerNamespaces([
        'app\models'    =>  __DIR__."/../app/models",
        'app\services'    =>  __DIR__."/../app/services",
        'app\common'    =>  __DIR__."/../app/common",
        'app\core'    =>  __DIR__."/../app/core",
    ]);
    $loader->registerDirs(
        [
            __DIR__ . "/tasks",
        ]
    );

    $loader->register();

    // 获取公共配置
    $commonConfig = new Phalcon\config\Adapter\Yaml(BASE_PATH . '/config/common.yaml');
    // 获取当前环境定义文件
    $config_file_name = file_get_contents(BASE_PATH . '/config/.env');
    // 如果配置文件不存在，抛出异常
    if(!file_exists(BASE_PATH . '/config/'.$config_file_name)){
        throw new Exception('配置文件不存在');
    }
    // 读取环境配置
    $config = new Phalcon\config\Adapter\Yaml(BASE_PATH . '/config/'.$config_file_name);
    // 记录当前环境配置文件名
    $config->CONFIG_FILE_NAME = $config_file_name;
    // 合并公共配置
    foreach ($commonConfig as $key => $value){
        if (isset($config->$key)) continue;
        $config->$key = $value;
    }
    unset($commonConfig);

    $di->set("config", $config);

    // 创建console应用
    $console = new ConsoleApp();
    $console->setDI($di);

    /**
     * 处理console应用参数
     */
    $arguments = [];

    foreach ($argv as $k => $arg) {
        if ($k === 1) {
            $arguments["task"] = $arg;
        } elseif ($k === 2) {
            $arguments["action"] = $arg;
        } elseif ($k >= 3) {
            $arguments["params"][] = $arg;
        }
    }
    $di->setShared('log', function () use ($config) {
        $logger = new FileAdapter( $config->log->path . '/cli.log');
        $logger->setFormatter(new \Phalcon\Logger\Formatter\Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
        return $logger;
    });
    // 支付宝配置预处理日志文件
    $di->setShared('AliPayConfig', function () use ($config) {
        // 获取配置文件
        $AliPayConfig = $config->alipay->toArray();
        // 解析配置中的日志路径
        $info = pathinfo($AliPayConfig['log']['file']);
        // 按月定义日志文件
        $AliPayConfig['log']['file'] = $config->log->path . '/' . $info['filename'] . $info['extension'];
        // 将配置存入属性
        return $AliPayConfig;
    });

// 微信配置预处理日志文件
$di->setShared('WxPayConfig', function () use ($config) {
    // 获取配置文件
    $WxPayConfig = $config->wxpay->toArray();
    // 解析配置中的日志路径
    $info = pathinfo($WxPayConfig['log']['file']);
    // 按月定义日志文件
    $WxPayConfig['log']['file'] = $config->log->path . '/' . $info['filename'] . $info['extension'];
    // 将配置存入属性
    return $WxPayConfig;
});

    // model事件日志
    $eventManager = null;
    if($config->app->debug) {
        $eventManager = new EventsManager();
        $logger = new FileAdapter($config->log->path . '/db.log');
        $logger->setFormatter(new \Phalcon\Logger\Formatter\Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
        $eventManager->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, DbAdapter $connection) use ($logger) {
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
        $di->setShared('dw_'.$k, function() use($config, $dbName, $eventManager) {
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

    $di->setShared('charging', function() use($config, $eventManager) {
        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            'host'      => $config->charging->host,
            'username'  => $config->charging->username,
            'password'  => $config->charging->password,
            'dbname'    => $config->charging->dbname,
        ));
        if(!is_null($eventManager)) {
            $connection->setEventsManager($eventManager);
        }
        return $connection;
    });
    $di->setShared('db', function() use($config) {
        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            'host'      => $config->db->host,
            'username'  => $config->db->username,
            'password'  => $config->db->password,
            'dbname'    => $config->db->dbname,
            //'charset'   => $config->db->charset,
        ));
                if ($config->app->debug) {
                    $eventManager = new EventsManager();
                    $logger = new FileAdapter($config->log->path . '/db.log');
                    $logger->setFormatter(new \Phalcon\Logger\Formatter\Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
                    $eventManager->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, DbAdapter $connection) use ($logger) {
                        $sqlVariables = $connection->getSQLVariables();
                        if (isset($sqlVariables)) {
                            $logger->info($connection->getSQLStatement() . ' PARAMS:' . json_encode($sqlVariables,JSON_UNESCAPED_UNICODE));
                        } else {
                            $logger->info($connection->getSQLStatement());
                        }
                    });

                    $connection->setEventsManager($eventManager);
                }
        return $connection;
    });
    $di->setShared('Zuul', function() use($config){
        return new \app\services\data\ZuulData();
    });
    $di->setShared('curl', function() use($config){
        return new \app\common\library\CurlService();
    });
    $di->setShared('logger', function() use($config){
        $logLevel = FileLogger::LEVEL_VALUES[$config->log->level];
        $path = $config->log->path;
        $logger = new FileLogger($path . '/app-info.log');
        $logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
        $logger->setLogLevel($logLevel);
        return $logger;
    });

    // 注入app
    $di->setShared('app', function() use($config) {
        require BASE_PATH . '/app/Application.php';
        $app = new app\Application($config);
        return $app;
    });


    try {
        // Handle incoming arguments
        $console->handle($arguments);
    } catch (\Phalcon\Exception $e) {
        echo "Message：".$e->getMessage();
        exit(255);
    }
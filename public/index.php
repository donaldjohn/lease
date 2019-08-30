<?php
//开发室记录所有错误信息
error_reporting(0);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
// 记录运行时间信息,全局变量【勿动】
$RunTimeInfo = [
    's' => microtime(),
    'cN' => 0,
    'cT' => 0
];
// 主动记录的异常提示信息(如Model验证器的错误) 将会在 toError() 中拼接至msg
$ExceptionTipsMsg = [];
try {
    if (file_exists(BASE_PATH .'/vendor/autoload.php')) {
        //加载composer库
        require_once BASE_PATH .'/vendor/autoload.php';
    } else {
        throw new Exception('COMPOSER配置文件不存在');
    }

    require BASE_PATH . '/app/Application.php';
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
    $app = new app\Application($config);
    // 换电柜心跳包接口日志级别ERROR
    if ('/cabinet/data/room' == $app->request->getURI()){
        $app->logger->setLogLevel(Phalcon\Logger::ERROR);
    }
    // 开启日志事务
    $app->logger->begin();
    $app->logger->info(PHP_EOL . PHP_EOL . '【请求开始】' . $app->getRequestId());
    //$app->logger->info('【客户端】'.$_SERVER['HTTP_USER_AGENT'].'【用户访问地址-客户端IP 】'. $_SERVER['REMOTE_ADDR'].'【用户访问地址-代理端的IP 】'. $_SERVER['HTTP_CLIENT_IP'].'【服务器端IP】'.$_SERVER['SERVER_ADDR']);
    $response = $app->handle();
    $response->send();
} catch (Throwable $e) {
    if (isset($app)) {
        // 内部已输出HTTP响应，此处接收仅为后面记录响应日志
        $response = $app->handleException($e);
    } else {
        echo get_class($e) . ": {$e->getMessage()} (" . basename($e->getFile(), '.php') . ":{$e->getLine()})";
    }
}
// 提交日志
if (isset($app) && $app->di->has('logger')){
    $app->logger->info('【服务器端IP】：'. isset($_SERVER['SERVER_ADDR'])? $_SERVER['SERVER_ADDR']: "");
    $app->logger->info('【访问URI】' . $app->request->getURI());
    $app->logger->info('【主动异常】' . json_encode($ExceptionTipsMsg, JSON_UNESCAPED_UNICODE));
    $app->logger->info('【请求参数】' . $app->request->getRawBody());
    if (isset($response)){
        $resContent = $response->getContent();
        //if (strlen($resContent)>5200) $resContent = substr($resContent, 0, 1200);
        $app->logger->info('【响应参数】' . $resContent);
    }
    $app->logger->commit();
}
<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: Logger.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace  app\core\services;

use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\common\logger\FileLogger;
use Phalcon\Logger\Formatter\Line;
use app\core\ServicesInterface;
class Logger implements ServicesInterface {

    public function register(FactoryDefault $di, Config $config)
    {
        //logger
        $di->setShared('logger', function () use ($config) {
            $logLevel = FileLogger::LEVEL_VALUES[$config->log->level];
            $path = $config->log->path;
//            if ($path[0] != '/') { $path = BASE_PATH . '/' . $path; }
            $logger = new FileLogger($path . '/app-info.log');
            $logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
            $logger->setLogLevel($logLevel);
            return $logger;
        });
    }
}
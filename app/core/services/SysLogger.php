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
use app\core\ServicesInterface;

use app\common\logger\system\SysLogger as sLog;

class SysLogger implements ServicesInterface {

    public function register(FactoryDefault $di, Config $config)
    {
        //syslogger
        $log  = new sLog();
        $di->setShared('sysLogger', function () use ($log) {
            return $log;
        });
    }
}
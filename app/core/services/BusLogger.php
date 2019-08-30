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
use app\common\logger\business\BusLogger as busLog;
class BusLogger implements ServicesInterface {

    public function register(FactoryDefault $di, Config $config)
    {
        //buslogger
        $bus = new busLog();
        $di->setShared('busLogger', function () use ($bus) {
            return $bus;
        });
    }
}
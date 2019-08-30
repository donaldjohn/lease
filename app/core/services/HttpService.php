<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: HttpService.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\services;


use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\common\library\HttpService as HS;
use app\core\ServicesInterface;

class HttpService implements ServicesInterface {

    public function register(FactoryDefault $di, Config $config)
    {
        //httpservice
        $di->setShared('httpService', function() use ($config) {
            return new HS();
        });
    }
}
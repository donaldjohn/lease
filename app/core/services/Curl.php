<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: Curl.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\services;


use app\common\library\CurlService;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\core\ServicesInterface;
class Curl implements ServicesInterface {
    public function register(FactoryDefault $di, Config $config)
    {
        //封装curl
        $di->setShared("curl", function() {
            return new CurlService();
        });
    }
}
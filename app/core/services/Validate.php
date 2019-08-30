<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: Validate.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\services;


use app\services\validate\ValidateService;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\core\ServicesInterface;

class Validate implements ServicesInterface
{
    public function register(FactoryDefault $di, Config $config)
    {
        $di->setShared('validate',function() {
            return new ValidateService();
        });
    }
}

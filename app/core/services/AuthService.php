<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: Auth.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\services;


use app\services\auth\AuthService as auth;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\core\ServicesInterface;

class AuthService implements ServicesInterface
{
    public function register(FactoryDefault $di, Config $config)
    {
        $di->setShared('auth', new auth());
    }
}
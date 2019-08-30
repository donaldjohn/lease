<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: ServiceInterface.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core;


use Phalcon\Config;
use Phalcon\Di\FactoryDefault;

/**
 * Interface ServicesInterface
 * @package app\core\services
 *
 *
 */
interface ServicesInterface
{
    public function register(FactoryDefault $di, Config $config);
}
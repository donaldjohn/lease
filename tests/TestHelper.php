<?php

use Phalcon\Di;
use Phalcon\Loader;

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('TESTS_PATH', ROOT_PATH . '/tests');
defined('APP_PATH') || define('APP_PATH', ROOT_PATH . '/app');
define('IS_CLI', false);
set_include_path(
    TESTS_PATH . PATH_SEPARATOR . get_include_path()
);
// Use the application autoloader to autoload the classes
// Autoload the dependencies found in composer
$loader = new Loader();
$loader->registerNamespaces(
    [
        'Tests' => TESTS_PATH,
    ]
);
$loader->register();
// Add any needed services to the DI here
require ROOT_PATH . '/app/Application.php';
// 环境加载配置文件
$config = new Phalcon\config\Adapter\Yaml(ROOT_PATH . '/config/config-dev.yaml');
$app = new app\Application($config);

Di::setDefault($app->getDI());

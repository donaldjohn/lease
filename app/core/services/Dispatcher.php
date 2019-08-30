<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: Dispatcher.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------

namespace app\core\services;


use app\core\ServicesInterface;
use app\modules\DispatchHandler;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Dispatcher as MVCDispatcher;

class Dispatcher implements ServicesInterface {

    public function register(FactoryDefault $di, Config $config)
    {
        // 为所有模块初始化 dispatcher
        // dispatcher的defaultNameSpace在 initEvents 中设置
        // 当有模块需要定制时可以在对应Module里覆盖
        $di->setShared('dispatcher', function () use ($config) {
            $eventsManager = new Manager();
            $eventsHandler = new DispatchHandler();

            $eventsManager->attach('dispatch:beforeExecuteRoute', $eventsHandler);
            $eventsManager->attach("dispatch:beforeException", $eventsHandler);

            $dispatcher = new MVCDispatcher();
            $dispatcher->setEventsManager($eventsManager);

            return $dispatcher;
        });
    }
}
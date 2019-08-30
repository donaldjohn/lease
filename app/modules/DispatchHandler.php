<?php
namespace app\modules;


use app\common\errors\AuthenticationException;
use Phalcon\Events\Event;
use Phalcon\Exception;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;

class DispatchHandler extends Plugin
{

    public function beforeExecuteRoute(Event $event, MvcDispatcher $dispatcher)
    {
        if ($this->auth->allow()) return true;
        throw new AuthenticationException();
    }

    //根据Dispatcher携带的Module、Namespace、Controller、Action获得完整的类与方法名，如果找不到则触发事件
    public function beforeException(Event $event, MvcDispatcher $dispatcher, \Exception $e)
    {
        if ($e instanceof DispatcherException) {

            throw new Exception("路由不存在");
            return false;
        }

    }

}
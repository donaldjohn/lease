<?php
namespace app\modules\template;

class ModuleRouter extends \app\modules\ModuleRouter {

    public function initialize()
    {

        /**
         * 消息模板接口
         */
        $this->addRoutes("/message", array(
            "POST" => array("controller" => "message", "action" => "add"),
            "PUT" => array("controller" => "message", "action" => "edit"),
            "DELETE" => array("controller" => "message", "action" => "delete"),
            "GET" => array("controller" => "message", "action" => "list"),
        ));

        /**
         * umeng模板接口
         */
        $this->addRoutes("/umeng", array(
            "POST" => array("controller" => "umeng", "action" => "create"),
            "GET" => array("controller" => "umeng", "action" => "page"),
        ));
        $this->addRoutes("/umeng/{id:[0-9]+}", array(
            "GET" => array("controller" => "umeng", "action" => "one"),
            "PUT" => array("controller" => "umeng", "action" => "update"),
            "DELETE" => array("controller" => "umeng", "action" => "delete"),
        ));
        $this->addRoutes("/umeng/page", array(
            "GET" => array("controller" => "umeng", "action" => "page"),
        ));


        $this->addRoutes("/message/list", array(
            "GET" => array("controller" => "message", "action" => "search")
        ));


        $this->addRoutes("/dwapp", array(
            "POST" => array("controller" => "dwapp", "action" => "create"),
            "GET" => array("controller" => "dwapp", "action" => "list"),
        ));
        $this->addRoutes("/dwapp/{id:[0-9]+}", array(
            "GET" => array("controller" => "dwapp", "action" => "one"),
            "PUT" => array("controller" => "dwapp", "action" => "update"),
            "DELETE" => array("controller" => "dwapp", "action" => "delete"),
        ));
        $this->addRoutes("/dwapp/{id:[0-9]+}/status", array(
            "PUT" => array("controller" => "dwapp", "action" => "status"),
        ));
        $this->addRoutes("/dwapp/page", array(
            "GET" => array("controller" => "dwapp", "action" => "page"),
        ));
        $this->addRoutes("/dwapp/{id:[0-9]+}/event", array(
            "GET" => array("controller" => "dwapp", "action" => "event"),
            "PUT" => array("controller" => "dwapp", "action" => "updateevent"),
        ));

        $this->addRoutes("/dwapp/type", array(
            "GET" => array("controller" => "dwapp", "action" => "apptype"),
        ));


        $this->addRoutes("/event", array(
            "POST" => array("controller" => "event", "action" => "create"),
            "GET" => array("controller" => "event", "action" => "list"),
        ));
        $this->addRoutes("/event/{id:[0-9]+}", array(
            "GET" => array("controller" => "event", "action" => "one"),
            "PUT" => array("controller" => "event", "action" => "update"),
            "DELETE" => array("controller" => "event", "action" => "delete"),
        ));
        $this->addRoutes("/event/{id:[0-9]+}/status", array(
            "PUT" => array("controller" => "event", "action" => "status"),
        ));
        $this->addRoutes("/event/page", array(
            "GET" => array("controller" => "event", "action" => "page"),
        ));
        $this->addRoutes("/event/order", array(
            "GET" => array("controller" => "event", "action" => "order"),
        ));



        $this->addRoutes("/call", array(
            "GET" => array("controller" => "index", "action" => "call"),
        ));




    }
}
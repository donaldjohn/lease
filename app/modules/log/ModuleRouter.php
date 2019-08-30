<?php
namespace app\modules\log;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        //Log管理
        $this->addRoutes("/business",array(
            "GET" => array("controller" => "log", "action" => "business"),
        ));
        $this->addRoutes("/platform",array(
            "GET" => array("controller" => "log", "action" => "platform"),
        ));
        $this->addRoutes("/system",array(
            "GET" => array("controller" => "log", "action" => "system"),
        ));




        $this->add('[/]?');
    }
}
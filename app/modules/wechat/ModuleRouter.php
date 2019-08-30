<?php
namespace app\modules\wechat;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        $this->add('/:controller/:action/:params', array(
            'controller'    => 1,
            'action'        => 2,
            'params'        => 3,
        ));

        $this->add('[/]?');


//        $this->addRoutes("/actions/{id:[0-9]+}",array(
//            "GET"        => array( "controller" => "action", "action" => "getone" ),
//            "PUT"       => array( "controller" => "action", "action" => "update" ),
//            "DELETE"    => array( "controller" => "action", "action" => "delete" ),
//        ));


    }
}
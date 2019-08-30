<?php
namespace app\modules\area;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        $this->addRoutes("",array(
            "GET"    => array( "controller" => "index", "action" => "list" ),
            "POST"    => array( "controller" => "index", "action" => "create" ),
        ));

        $this->addRoutes("/{id:[0-9]+}",array(
            "PUT"    => array( "controller" => "index", "action" => "update" ),
            "DELETE"    => array( "controller" => "index", "action" => "delete" ),
        ));


    }
}
<?php
namespace app\modules\multicodetask;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {

        $this->addRoutes("/task",array(
            "GET"    => array( "controller" => "task", "action" => "list" ),
            "POST"   => array( "controller" => "task", "action" => "create" ),
        ));
        $this->addRoutes("/task/{id:[0-9]+}",array(
            "GET"    => array( "controller" => "task", "action" => "one" ),
            "PUT"    => array( "controller" => "task", "action" => "update" ),
            "DELETE"   => array( "controller" => "task", "action" => "delete" ),
        ));
        $this->addRoutes("/task/status",array(
            "PUT"   => array( "controller" => "task", "action" => "status" )
        ));

        $this->addRoutes("/task/user",array(
            "GET"   => array( "controller" => "task", "action" => "user" )
        ));
        $this->addRoutes("/task/{id:[0-9]+}/detail",array(
            "GET"   => array( "controller" => "task", "action" => "detail" )
        ));


        //商品
        $this->addRoutes("/products/catalogue",array(
            "GET" => array("controller" => "products", "action" => "catalogue")
        ));


        $this->addRoutes("/products/searching",array(
            "GET" => array("controller" => "products", "action" => "search")
        ));

        $this->addRoutes("/products/skus/catalogue",array(
            "GET" => array("controller" => "products", "action" => "skucatalog")
        ));


        $this->addRoutes("/micro",array(
            "GET"    => array( "controller" => "micro", "action" => "list" ),
        ));
        $this->addRoutes("/micro/{id:[0-9]+}",array(
            "GET"    => array( "controller" => "micro", "action" => "one" ),
            "POST"    => array( "controller" => "micro", "action" => "create" ),
        ));
        $this->addRoutes("/micro/{id:[0-9]+}/status",array(
            "PUT"    => array( "controller" => "micro", "action" => "status" ),
        ));

        $this->addRoutes("/task/platenum",array(
            "POST"    => array( "controller" => "task", "action" => "leadin" ),
        ));

        $this->addRoutes("/micro/check",array(
            "POST"    => array( "controller" => "micro", "action" => "check" ),
        ));
        $this->addRoutes("/micro/udid",array(
            "POST"    => array( "controller" => "micro", "action" => "getUdid" ),
        ));

        $this->addRoutes("/micro/udid",array(
            "POST"    => array( "controller" => "micro", "action" => "getUdid" ),
        ));




        $this->add('[/]?');
    }
}
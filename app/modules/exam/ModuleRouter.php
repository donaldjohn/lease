<?php
namespace app\modules\exam;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {

        $this->addRoutes("",array(
            "GET"    => array( "controller" => "index", "action" => "list" ),
        ));
        $this->addRoutes("/history",array(
            "GET"    => array( "controller" => "index", "action" => "history" ),
        ));
        $this->addRoutes("/licence",array(
            "GET"    => array( "controller" => "index", "action" => "licence" ),
        ));
        $this->addRoutes("/licence/{id}",array(
            "GET"    => array( "controller" => "index", "action" => "LicenceDetail" ),
            "PUT"    => array( "controller" => "index", "action" => "UpdateLicence" ),
        ));

        $this->addRoutes("/{basicId:[0-9]+}/signup",array(
            "GET"    => array( "controller" => "signup", "action" => "list" ),
            "POST"    => array( "controller" => "signup", "action" => "create" ),
        ));

        $this->addRoutes("/{basicId:[0-9]+}/users",array(
            "PUT"    => array( "controller" => "signup", "action" => "delete" ),
        ));


        $this->addRoutes("/batch",array(
            "GET"    => array( "controller" => "signup", "action" => "batch" ),
        ));
        $this->addRoutes("/batch/{id:[0-9]+}",array(
            "GET"    => array( "controller" => "signup", "action" => "batchdetail" ),
        ));

        $this->addRoutes("/driverlicence/{id}",array(
            "GET"    => array( "controller" => "index", "action" => "checkLicenceIsSend" ),
        ));
        $this->addRoutes("/driverlicence",array(
            "PUT"    => array( "controller" => "index", "action" => "updateLicenceIsSend" ),
        ));


    }
}
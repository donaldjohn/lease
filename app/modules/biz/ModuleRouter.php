<?php
namespace app\modules\biz;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {

        $this->addRoutes("/expressprintdriverslicensestatus",array(
            "GET"    => array( "controller" => "index", "action" => "getInsExpressPrintDriversLicenseStatus" ),
        ));
    }

}
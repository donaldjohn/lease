<?php
namespace app\modules\microprograms;

class ModuleRouter extends \app\modules\ModuleRouter {

    public function initialize()
    {


        $this->addRoutes("/unusual",array(
            "GET" => array("controller" => "index", "action" => "unusual"),
        ));

        $this->add('[/]?');

    }

}
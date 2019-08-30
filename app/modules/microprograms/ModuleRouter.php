<?php
namespace app\modules\microprograms;

class ModuleRouter extends \app\modules\ModuleRouter {

    public function initialize()
    {

    /**
     * 商品及规格接口
     */

        // 获取一级目录
        $this->addRoutes("/category",array(
            "GET"    => array( "controller" => "product", "action" => "category" ),
        ));

        // 获取二级目录
        $this->addRoutes("/brand",array(
            "GET"    => array( "controller" => "product", "action" => "brand" ),
        ));

        // 获取商品目录
        $this->addRoutes("/product",array(
            "GET"    => array( "controller" => "product", "action" => "product" ),
        ));

        // 获取规格目录
        $this->addRoutes("/sku",array(
            "GET"    => array( "controller" => "product", "action" => "sku" ),
        ));


    /**
     * 四码合一批次接口
     */

        // 批次新增
        $this->addRoutes("/batch",array(
            "GET"    => array( "controller" => "batch", "action" => "list" ),
            "POST"    => array( "controller" => "batch", "action" => "add" ),
            "PUT"    => array( "controller" => "batch", "action" => "update" ),
        ));

    /**
     * 四码合一记录接口
     */

        $this->addRoutes("/record",array(
            "GET"    => array( "controller" => "record", "action" => "list" ),
            "POST"    => array( "controller" => "record", "action" => "add" ),
            "PUT"    => array( "controller" => "record", "action" => "update" ),
            "DELETE"    => array( "controller" => "record", "action" => "delete" ),
        ));

    /**
     * 车辆接口
     */

        $this->addRoutes("/vehicle",array(
            "POST"    => array( "controller" => "vehicle", "action" => "add" ),
        ));



        $this->addRoutes("/secondhand",array(
            "GET"    => array( "controller" => "secondhand", "action" => "list" ),
            "POST"    => array( "controller" => "secondhand", "action" => "create" ),
        ));

        $this->addRoutes("/secondhand/general",array(
            "POST"    => array( "controller" => "secondhand", "action" => "ocrgeneral" ),
        ));
        $this->addRoutes("/secondhand/vin",array(
            "POST"    => array( "controller" => "secondhand", "action" => "ocrvin" ),
        ));

        $this->addRoutes("/install/fj",array(
            "POST"    => array( "controller" => "secondhand", "action" => "CreateFJ" ),
        ));
    }

}
<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: ModuleRouter.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        //车辆架构
        $this->addRoutes("/elements",array(
            "GET" => array("controller" => "elements", "action" => "list"),
            "POST" => array("controller" => "elements", "action" => "create"),
        ));
        $this->addRoutes("/elements/{id:[0-9]+}",array(
            "GET" => array("controller" => "elements", "action" => "one"),
            "PUT" => array("controller" => "elements", "action" => "update"),
            "DELETE" => array("controller" => "elements", "action" => "delete"),
        ));
        $this->addRoutes("/elements/tree",array(
            "GET" => array("controller" => "elements", "action" => "tree"),
        ));
        $this->addRoutes("/elements/order",array(
            "GET" => array("controller" => "elements", "action" => "order"),
        ));
        $this->addRoutes("/elements/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "elements", "action" => "status"),
        ));
        $this->addRoutes("/elements/leadout",array(
            "POST" => array("controller" => "elements", "action" => "leadout")
        ));

        //车辆类型
        $this->addRoutes("/vehicletypes",array(
            "GET" => array("controller" => "index", "action" => "vehicletypes"),

        ));
        //车辆区域
        $this->addRoutes("/vehicleareas",array(
            "GET" => array("controller" => "index", "action" => "vehicleareas"),

        ));
        //区域
        $this->addRoutes("/region",array(
            "GET" => array("controller" => "index", "action" => "region"),

        ));
        //客户 下拉菜单列表
        $this->addRoutes("/customers",array(
            "GET" => array("controller" => "customers", "action" => "list"),
        ));
        //客户信息列表
        $this->addRoutes("/customers/info",array(
            "GET" => array("controller" => "customers", "action" => "index"),
            "POST" => array("controller" => "customers", "action" => "create"),
            "PUT" => array("controller" => "customers", "action" => "update"),
            "DELETE" => array("controller" => "customers", "action" => "delete"),
        ));
        $this->addRoutes("/customers/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "customers", "action" => "status"),
        ));
        $this->addRoutes("/customers/{id:[0-9]+}/secret",array(
            "GET" => array("controller" => "customers", "action" => "secret"),
            "PUT" => array("controller" => "customers", "action" => "updateSecret"),
        ));
        //商品
        $this->addRoutes("/products/catalogue",array(
            "GET" => array("controller" => "products", "action" => "catalogue")
        ));

        $this->addRoutes("/products",array(
            "GET" => array("controller" => "products", "action" => "list")
        ));
        $this->addRoutes("/products/searching",array(
            "GET" => array("controller" => "products", "action" => "search")
        ));
        $this->addRoutes("/products/skus/{id:[0-9]+}",array(
            "GET" => array("controller" => "products", "action" => "skus")
        ));
        $this->addRoutes("/products/skus/catalogue",array(
            "GET" => array("controller" => "products", "action" => "skucatalog")
        ));

        $this->addRoutes("/products/leadout",array(
            "POST" => array("controller" => "products", "action" => "leadout")
        ));



        $this->addRoutes("/vehicleboms",array(
            "GET" => array("controller" => "boms", "action" => "list"),
            "POST" => array("controller" => "boms", "action" => "create"),
        ));
        $this->addRoutes("/vehicleboms/{id:[0-9]+}",array(
            "GET" => array("controller" => "boms", "action" => "one"),
            "PUT" => array("controller" => "boms", "action" => "update"),
            "DELETE" => array("controller" => "boms", "action" => "delete"),
        ));
        $this->addRoutes("/vehicleboms/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "boms", "action" => "status"),
        ));
        $this->addRoutes("/vehicleboms/sku",array(
        "GET" => array("controller" => "boms", "action" => "skubom"),
        ));
        $this->addRoutes("/vehicleboms/leadout",array(
            "POST" => array("controller" => "boms", "action" => "leadout"),
        ));
        $this->addRoutes("/vehicleboms/leadin",array(
            "POST" => array("controller" => "boms", "action" => "leadin"),
        ));

        //订单
        $this->addRoutes("/orders",array(
            "GET" => array("controller" => "orders", "action" => "list"),
            "POST" => array("controller" => "orders", "action" => "create"),
        ));
        $this->addRoutes("/orders/{id:[0-9]+}",array(
            "GET" => array("controller" => "orders", "action" => "one"),
            "PUT" => array("controller" => "orders", "action" => "update"),
            "DELETE" => array("controller" => "orders", "action" => "delete"),
        ));
        $this->addRoutes("/orders/status",array(
            "PUT" => array("controller" => "orders", "action" => "status"),
        ));


        //订单方案
        $this->addRoutes("/warrantyschemes",array(
            "GET" => array("controller" => "warrantyschemes", "action" => "list"),
            "POST" => array("controller" => "warrantyschemes", "action" => "create"),
        ));
        $this->addRoutes("/warrantyschemes/{id:[0-9]+}",array(
            "GET" => array("controller" => "warrantyschemes", "action" => "one"),
            "PUT" => array("controller" => "warrantyschemes", "action" => "update"),
            "DELETE" => array("controller" => "warrantyschemes", "action" => "delete"),
        ));
        $this->addRoutes("/warrantyschemes/status",array(
            "PUT" => array("controller" => "warrantyschemes", "action" => "status"),
        ));


        $this->addRoutes("/prices",array(
            "GET" => array("controller" => "prices", "action" => "list"),
            //"POST" => array("controller" => "prices", "action" => "create"),
        ));
        $this->addRoutes("/prices/{id:[0-9]+}",array(
            //"GET" => array("controller" => "orders", "action" => "one"),
            "PUT" => array("controller" => "prices", "action" => "update"),
            "DELETE" => array("controller" => "prices", "action" => "delete"),
        ));
        $this->addRoutes("/prices/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "prices", "action" => "status"),
        ));
        $this->addRoutes("/prices/leadout",array(
            "POST" => array("controller" => "prices", "action" => "leadout"),
        ));
        $this->addRoutes("/prices/leadin",array(
            "POST" => array("controller" => "prices", "action" => "leadin"),
        ));



        $this->addRoutes("/schemes",array(
            "GET" => array("controller" => "schemes", "action" => "list"),
            "POST" => array("controller" => "schemes", "action" => "create"),
        ));
        $this->addRoutes("/schemes/{id:[0-9]+}",array(
            "GET" => array("controller" => "schemes", "action" => "one"),
            "PUT" => array("controller" => "schemes", "action" => "update"),
            "DELETE" => array("controller" => "schemes", "action" => "delete"),
        ));
        $this->addRoutes("/schemes/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "schemes", "action" => "status"),
        ));
        $this->addRoutes("/schemes/leadout",array(
            "POST" => array("controller" => "schemes", "action" => "leadout"),
        ));
        $this->addRoutes("/schemes/leadin",array(
            "POST" => array("controller" => "schemes", "action" => "leadin"),
        ));
        $this->addRoutes("/schemes/region",array(
            "GET" => array("controller" => "schemes", "action" => "region"),
        ));

        $this->add('[/]?');


        /**
         * 门店车辆维修小程序接口
         */
        // 获取车辆信息的维修记录
        $this->addRoutes("/store/scan",array(
            "GET" => array("controller" => "microprograms", "action" => "scan"),
        ));

        // 获取维修记录详情
        $this->addRoutes("/store/info",array(
            "GET" => array("controller" => "microprograms", "action" => "info"),
        ));

        // 获取门店维修记录
        $this->addRoutes("/store/list",array(
            "GET" => array("controller" => "microprograms", "action" => "list"),
        ));

        // 获取商品BOM清单
        $this->addRoutes("/store/bom",array(
            "POST" => array("controller" => "microprograms", "action" => "bom"),
        ));

        // 获取车辆类型对应区域
        $this->addRoutes("/store/vehicle/area",array(
            "POST" => array("controller" => "microprograms", "action" => "area"),
        ));

        // 提交维修清单
        $this->addRoutes("/store/send",array(
            "POST" => array("controller" => "microprograms", "action" => "send"),
        ));

        // 提交维修清单
        $this->addRoutes("/store/count",array(
            "POST" => array("controller" => "microprograms", "action" => "count"),
        ));

        // 修改维修单状态
        $this->addRoutes("/store/change",array(
            "POST" => array("controller" => "microprograms", "action" => "change"),
        ));


        /**
         * 管理后台车辆维修订单接口
         */
        // 所有车辆维修订单列表
        $this->addRoutes("/repair",array(
            "GET" => array("controller" => "repairs", "action" => "list"), // 维修订单列表
        ));

        // 维修订单详情
        $this->addRoutes("/repair/{id:[0-9]+}",array(
            "GET" => array("controller" => "repairs", "action" => "one"),
        ));

        // 维修订单详情
        $this->addRoutes("/repair/detail",array(
            "GET" => array("controller" => "repairs", "action" => "detail"),
        ));

        /**
         * 租赁开放维修订单接口
         */
        $this->addRoutes("/outside/repair",array(
            "POST" => array("controller" => "outside", "action" => "repair"), // 创建维修订单
            "GET" => array("controller" => "outside", "action" => "repairInfo"), // 维修订单详情
            "PUT" => array("controller" => "outside", "action" => "repairStatus"), // 取消维修订单
        ));
        $this->addRoutes("/outside/order",array(
            "POST" => array("controller" => "outside", "action" => "order"), // 创建联保订单
        ));
        $this->addRoutes("/outside/order/status",array(
            "PUT" => array("controller" => "outside", "action" => "orderstatus"), // 过期联保订单
        ));

    }
}
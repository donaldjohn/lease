<?php
namespace app\modules\dispatch;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        //区域API
        $this->addRoutes("/region",array(
            "GET" => array("controller" => "region", "action" => "list"),
            "POST" => array("controller" => "region", "action" => "create"),
        ));
        $this->addRoutes("/region/{id:[0-9]+}",array(
            "GET" => array("controller" => "region", "action" => "one"),
            "PUT" => array("controller" => "region", "action" => "update"),
            "DELETE" => array("controller" => "region", "action" => "delete"),
        ));

        //站点API
        $this->addRoutes("/site",array(
            "GET" => array("controller" => "site", "action" => "list"),
            "POST" => array("controller" => "site", "action" => "create"),
        ));
        $this->addRoutes("/site/{id:[0-9]+}",array(
            "GET" => array("controller" => "site", "action" => "one"),
            "PUT" => array("controller" => "site", "action" => "update"),
            "DELETE" => array("controller" => "site", "action" => "delete"),
        ));

        //门店API
        $this->addRoutes("/stores",array(
            "GET" => array("controller" => "store", "action" => "list"),
            "POST" => array("controller" => "store", "action" => "create"),
        ));
        $this->addRoutes("/store/{id:[0-9]+}",array(
            "GET" => array("controller" => "store", "action" => "one"),
            "PUT" => array("controller" => "store", "action" => "update"),
            "DELETE" => array("controller" => "store", "action" => "delete"),
        ));

        //骑手API
        $this->addRoutes("/driver",array(
            "GET" => array("controller" => "driver", "action" => "list"),
            "POST" => array("controller" => "driver", "action" => "create"),
        ));
        $this->addRoutes("/driver/{id:[0-9]+}",array(
            "GET" => array("controller" => "driver", "action" => "one"),
            "PUT" => array("controller" => "driver", "action" => "update"),
            "DELETE" => array("controller" => "driver", "action" => "delete"),
        ));
        // 站点APP获取骑手列表
        $this->addRoutes("/drivers",array(
            "GET" => array("controller" => "driver", "action" => "drivers"),
        ));
        // APP获取站点信息
        $this->addRoutes("/siteinfo",array(
            "GET" => array("controller" => "site", "action" => "info"),
        ));
        // 站点APP获取附近门店
        $this->addRoutes("/store",array(
            "POST" => array("controller" => "site", "action" => "store"),
        ));

        $this->add('[/]?');

        // 获取得威二维码对应车辆信息
        $this->addRoutes("/qrcode/info",array(
            "GET" => array("controller" => "site", "action" => "qrcodeInfo"),
        ));

        // 站点批量绑车
        $this->addRoutes("/vehicle/bind",array(
            "PUT" => array("controller" => "site", "action" => "vehicleBind"),
        ));

        // 获取门店所有车辆
        $this->addRoutes("/store/vehicle",array(
            "GET" => array("controller" => "store", "action" => "vehicle"),
        ));

        // 门店批量绑车
        $this->addRoutes("/store/bind",array(
            "POST" => array("controller" => "store", "action" => "bind"),
        ));

        // 站点APP获取车辆信息
        $this->addRoutes("/store/qrcode",array(
            "GET" => array("controller" => "store", "action" => "qrcodeInfo"),
        ));

        // 骑手APP申请还车接口
        $this->addRoutes("/store/untie",array(
            "POST" => array("controller" => "store", "action" => "tie"),
        ));

        // 门店确认还车
        $this->addRoutes("/store/return",array(
            "POST" => array("controller" => "store", "action" => "returnVehicle"),
        ));

        // 获取门店小程序首页的门店信息
        $this->addRoutes("/store/info",array(
            "GET" => array("controller" => "store", "action" => "info"),
        ));

        // 门店小程序登陆有效性验证
        $this->addRoutes("/store/check",array(
            "GET" => array("controller" => "store", "action" => "loginCheck"),
        ));
        // 解绑门店车辆绑定
        $this->addRoutes("/store/unvehicle",array(
            "POST" => array("controller" => "store", "action" => "unbindvehicle"),
        ));
        // 站点绑定骑手
        $this->addRoutes("/site/binddriver",array(
            "POST" => array("controller" => "site", "action" => "binddriver"),
        ));
        // 站点扫码获取骑手信息
        $this->addRoutes("/site/scandriver",array(
            "POST" => array("controller" => "site", "action" => "scandriver"),
        ));
        // 站点批量绑定骑手
        $this->addRoutes("/site/binddrivers",array(
            "POST" => array("controller" => "site", "action" => "binddrivers"),
        ));
        // 获取骑手身份证号码
        $this->addRoutes("/driver/identify/{driverId:[0-9]+}",array(
            "GET" => array("controller" => "driver", "action" => "driveridentify"),
        ));
        // 附近门店【支持类型和名称】 TODO:预废弃
        $this->addRoutes("/store/nearby",array(
            "GET" => array("controller" => "store", "action" => "Nearbystore"),
        ));
        // 站点锁车
        $this->addRoutes("/site/lock",array(
            "POST" => array("controller" => "site", "action" => "Lock"),
        ));
        // 添加编辑业务员关系
        $this->addRoutes("/staff",array(
            "POST" => array("controller" => "region", "action" => "Upstaff"),
        ));
        // 删除业务员关系
        $this->addRoutes("/staff/{id:[0-9]+}",array(
            "DELETE" => array("controller" => "region", "action" => "Delstaff"),
        ));
        // 快递公司用户列表
        $this->addRoutes("/express/user",array(
            "GET" => array("controller" => "Express", "action" => "Userlist"),
        ));
        // 快递公司用户列表
        $this->addRoutes("/select/driver",array(
            "POST" => array("controller" => "Driver", "action" => "Selectlist"),
        ));
        //骑手导入
        $this->addRoutes("/driver/leadin",array(
            "POST" => array("controller" => "driver", "action" => "leadin"),
        ));

        // 获取门店收益总计
        $this->addRoutes("/store/income",array(
            "GET" => array("controller" => "Store", "action" => "getStoreIncome"),
        ));
        // 门店租车收益列表
        $this->addRoutes("/store/rent/income",array(
            "GET" => array("controller" => "Store", "action" => "getStoreRentVehicleIncome"),
        ));
        // 门店换电收益列表
         $this->addRoutes("/store/charging/income",array(
            "GET" => array("controller" => "Store", "action" => "getStoreChargingIncome"),
        ));
        // 门店押金列表
         $this->addRoutes("/store/deposit/income",array(
            "GET" => array("controller" => "Store", "action" => "getStoreDepositIncome"),
        ));
        // 门店退款列表
         $this->addRoutes("/store/ReturnBill",array(
            "GET" => array("controller" => "Store", "action" => "getStoreReturnBill"),
        ));
        // 门店租车数量统计
         $this->addRoutes("/store/RentQuantity",array(
            "GET" => array("controller" => "Store", "action" => "VehicleRentQuantity"),
        ));


         //门店安装记录
        $this->addRoutes("/store/installCount",array(
            "GET" => array("controller" => "Store", "action" => "InstallCount"),
        ));
        //
        $this->addRoutes("/store/everydayRecord",array(
            "GET" => array("controller" => "Store", "action" => "EverydayRecord"),
        ));
        //通过车架号获取快递公司
        $this->addRoutes("/store/company",array(
            "GET" => array("controller" => "Store", "action" => "company"),
        ));
        //通过快递公司获取品牌型号
        $this->addRoutes("/store/brand",array(
            "GET" => array("controller" => "Store", "action" => "brand"),
        ));
        //通过快递公司获取品牌型号
        $this->addRoutes("/store/productSku",array(
            "GET" => array("controller" => "Store", "action" => "ProductSku"),
        ));
        //合码（三码合一/四码合一）
        $this->addRoutes("/store/combine",array(
            "POST" => array("controller" => "Store", "action" => "combine"),
        ));
        //硬件检测结果
        $this->addRoutes("/store/checkDevice",array(
            "GET" => array("controller" => "Store", "action" => "CheckDevice"),
        ));
        // 快递公司下站点的年检任务
        $this->addRoutes("/yearcheck",array(
            "GET" => array("controller" => "Yearcheck", "action" => "List"),
        ));
        // 快递公司下站点列表
        $this->addRoutes("/yearcheck/select/site",array(
            "GET" => array("controller" => "Yearcheck", "action" => "SelectSite"),
        ));
    }
}
<?php
namespace app\modules\vehicle;

class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        $this->addRoutes("/index",array(
            "GET"    => array( "controller" => "index", "action" => "index" ),//车辆列表
            "PUT"    => array( "controller" => "index", "action" => "lock" ),//车辆的解锁/锁定
        ));
        $this->addRoutes("/detail",array(
            "GET"    => array( "controller" => "index", "action" => "detail" ),//车辆详情
        ));
        $this->addRoutes("/driver/{id:[0-9]+}/identify",array(
            "GET"    => array( "controller" => "index", "action" => "identify" ),//骑手的身份证信息
        ));
        $this->addRoutes("/export",array(
            "GET"    => array( "controller" => "index", "action" => "export" ),//导出车辆报表
        ));
        $this->addRoutes("/map",array(
            "GET"    => array( "controller" => "index", "action" => "map" ),//车辆地图
        ));
        $this->addRoutes("/untie",array(
            "PUT"    => array( "controller" => "index", "action" => "untie" ),//车辆解除绑定
        ));
        $this->addRoutes("/chart",array(
            "GET"    => array( "controller" => "mileage", "action" => "chart" ),//车辆的chart图表接口
        ));

        $this->addRoutes("/mileage",array(
            "GET"    => array( "controller" => "mileage", "action" => "index" ),//车辆里程详情
        ));

        $this->addRoutes("/mileagelist",array(
            "GET"    => array( "controller" => "mileage", "action" => "list" ),//车辆里程列表
        ));

        $this->addRoutes("/mileageexport",array(
            "GET"    => array( "controller" => "mileage", "action" => "export" ),//车辆里程报表导出
        ));

        $this->addRoutes("/gps",array(
            "GET"    => array( "controller" => "gps", "action" => "index" ),//车辆轨迹接口
        ));
        // APP端获取站点车辆列表
        $this->addRoutes("/sitevehicles",array(
            "GET"    => array( "controller" => "index", "action" => "sitevehicles" ),
        ));
        // APP端获取车辆轨迹
        $this->addRoutes("/gpstrack/{vehicleId:[0-9]+}",array(
            "GET"    => array( "controller" => "gps", "action" => "gpstrack" ),
        ));
        // 车辆电子围栏
        $this->addRoutes("/rail",array(
            "GET"    => array( "controller" => "rail", "action" => "index" ),//电子围栏列表
            "POST"    => array( "controller" => "rail", "action" => "create" ),//增加电子围栏列表
            "PUT"    => array( "controller" => "rail", "action" => "edit" ),//编辑电子围栏列表
            "DELETE"    => array( "controller" => "rail", "action" => "delete" ),//删除电子围栏列表
        ));
        $this->addRoutes("/raillist",array(
            "GET"    => array( "controller" => "rail", "action" => "list" ),//电子围栏列表
        ));
        $this->addRoutes("/area/rail",array(
            "GET"    => array( "controller" => "rail", "action" => "AreaRailing" ),// 查询行政区域下的启用的电子围栏信息
        ));
        // APP获取车辆信息
        $this->addRoutes("/info/{vehicleId:[0-9]+}",array(
            "GET" => array("controller" => "index", "action" => "appvehicle"),
        ));
//        // 车辆管理列表【门店】
//        $this->addRoutes("/storevehiclelist",array(
//            "GET" => array("controller" => "index", "action" => "storevehiclelist"),
//        ));
//        // 导出车辆管理列表【门店】
//        $this->addRoutes("/store/export",array(
//            "GET" => array("controller" => "index", "action" => "Exportstorevehicle"),
//        ));
        $this->addRoutes("/lock",array(
            "GET" => array("controller" => "lock", "action" => "index"),
            "PUT" => array("controller" => "lock", "action" => "update"),
        ));
        $this->addRoutes("/batch/lock",array(
            "PUT" => array("controller" => "lock", "action" => "multi"),
        ));
        // 查询老系统车辆信息
        $this->addRoutes("/old",array(
            "GET" => array("controller" => "Oldtonew", "action" => "Oldinfo"),
        ));
        // 保存车辆三码信息到新系统
        $this->addRoutes("/migrate",array(
            "POST" => array("controller" => "Oldtonew", "action" => "Migrate"),
        ));


        $this->addRoutes("/mileage/report",array(
            "GET"    => array( "controller" => "mileage", "action" => "report" ),//车辆里程报表
        ));

        $this->addRoutes("/LicenseIssued",[
            "GET"    => array( "controller" => "Postoffice", "action" => "LicenseIssuedVehicle" ),
        ]);
        $this->addRoutes("/VehicleUsage",[
            // 车辆使用统计
            "GET"    => array( "controller" => "Postoffice", "action" => 'VehicleUsageStatistics' ),
        ]);
        $this->addRoutes("/MonthVehicleUsage",[
            // 车辆使用统计月使用趋势
            "POST"    => array( "controller" => "Postoffice", "action" => 'MonthVehicleUsage' ),
        ]);
        $this->addRoutes("/YearVehicleUsage",[
            // 车辆使用统计年使用趋势
            "POST"    => array( "controller" => "Postoffice", "action" => 'YearVehicleUsage' ),
        ]);

        $this->addRoutes("/datevalues",array(
            "POST"    => array( "controller" => "index", "action" => "DateValues" ),//可用日期
        ));
        // 车辆信息 - 图表信息
        $this->addRoutes("/statistic/chart/{id:[0-9]+}",array(
            "GET"    => array( "controller" => "index", "action" => "StatisticChart" ),
        ));
        //后装预约
        $this->addRoutes("/deviceInstall",array(
            "GET"    => array( "controller" => "deviceinstall", "action" => "index" ),
            "POST"    => array( "controller" => "deviceinstall", "action" => "create" ),
            "PUT"    => array( "controller" => "deviceinstall", "action" => "edit" ),
        ));
        $this->addRoutes("/deviceInstall/update",array(
            "PUT"    => array( "controller" => "deviceinstall", "action" => "update" ),
        ));
        //导入后装车辆
        $this->addRoutes("/deviceInstall/import",array(
            "POST"    => array( "controller" => "deviceinstall", "action" => "import" ),
        ));
        $this->addRoutes("/cityList",array(
            "GET"    => array( "controller" => "deviceinstall", "action" => "city" ),
        ));
        $this->addRoutes("/companyList",array(
            "GET"    => array( "controller" => "deviceinstall", "action" => "company" ),
        ));
        $this->addRoutes("/deviceInstall/detail",array(
            "GET"    => array( "controller" => "deviceinstall", "action" => "detail" ),
            "DELETE"    => array( "controller" => "deviceinstall", "action" => "del" ),
        ));
        $this->addRoutes("/storeCount",array(
            "GET"    => array( "controller" => "storeCount", "action" => "index" ),
        ));
        //后装门店列表
        $this->addRoutes("/storeCount/export",array(
            "GET"    => array( "controller" => "storeCount", "action" => "export" ),
        ));
        $this->addRoutes("/storeCount/detail",array(
            "GET"    => array( "controller" => "storeCount", "action" => "detail" ),
        ));
        //门店每日列表
        $this->addRoutes("/storeCount/exportDay",array(
            "GET"    => array( "controller" => "storeCount", "action" => "exportDay" ),
        ));
        $this->addRoutes("/storeList",array(
            "GET"    => array( "controller" => "storecount", "action" => "store" ),
        ));

        // 租赁后台锁车
        $this->addRoutes("/rent/lock",array(
            "POST"    => array( "controller" => "Lock", "action" => "RentLock" ),
        ));
        // 租赁后台解锁
        $this->addRoutes("/rent/unLock",array(
            "POST"    => array( "controller" => "Lock", "action" => "RentUnLock" ),
        ));

        // 租赁编辑车辆
        $this->addRoutes("/rent/{vehicleId:[0-9]+}",array(
            "PUT"    => array( "controller" => "Rent", "action" => "EditVehicle" ),
        ));

        // 快递公司后台锁车
        $this->addRoutes("/express/lock",array(
            "POST"    => array( "controller" => "Lock", "action" => "ExpressCompanyLock" ),
        ));
        // 快递公司后台解锁
        $this->addRoutes("/express/unLock",array(
            "POST"    => array( "controller" => "Lock", "action" => "ExpressCompanyUnLock" ),
        ));

        $this->add('[/]?');

    }
}
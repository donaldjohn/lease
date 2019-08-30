<?php
namespace app\modules\cabinet;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        /**
         * 换电柜硬件数据接收功能
         */
        // 换电柜上电数据接收API
        $this->addRoutes("/data/board",array(
            "POST"    => array( "controller" => "data", "action" => "board" ),
        ));

        // 换电柜状态心跳数据包接收API
        $this->addRoutes("/data/room",array(
            "POST"    => array( "controller" => "data", "action" => "room" ),
        ));


        /**
         * 换电柜管理后台功能
         */
        // 换电柜列表及搜索API
        $this->addRoutes("/admin/board",array(
            "GET"    => array( "controller" => "admin", "action" => "board" ),
        ));

        // 换电柜绑定站点及重置API
        $this->addRoutes("/admin/store",array(
            "POST"    => array( "controller" => "admin", "action" => "store" ),
        ));

        // 换电柜解除站点
        $this->addRoutes("/admin/store",array(
            "DELETE"    => array( "controller" => "admin", "action" => "cancel" ),
        ));

        // 换电柜柜子状态获取
        $this->addRoutes("/admin/room",array(
            "GET"    => array( "controller" => "admin", "action" => "room" ),
        ));

        // 换电柜柜子操作
        $this->addRoutes("/admin/room",array(
            "POST"    => array( "controller" => "admin", "action" => "operation" ),
        ));

        // 换电柜异常列表及搜索API
        $this->addRoutes("/admin/error",array(
            "GET"    => array( "controller" => "admin", "action" => "error" ),
        ));

        // 换电柜记录列表及API
        $this->addRoutes("/admin/record",array(
            "GET"    => array( "controller" => "admin", "action" => "record" ),
        ));

        // 模糊搜索门店
        $this->addRoutes("/admin/search/store",array(
            "GET"    => array( "controller" => "admin", "action" => "storeSearch" ),
        ));

        // 模糊搜索骑手
        $this->addRoutes("/admin/search/driver",array(
            "GET"    => array( "controller" => "admin", "action" => "driverSearch" ),
        ));

        // 模糊搜索区域
        $this->addRoutes("/admin/search/area",array(
            "GET"    => array( "controller" => "admin", "action" => "areaSearch" ),
        ));

        // 设置默认空柜
        $this->addRoutes("/admin/empty",array(
            "POST"    => array( "controller" => "admin", "action" => "setEmpty" ),
        ));


        /**
         * 换电柜骑手端功能
         */
        // 骑手首页地图展示API
        $this->addRoutes("/drivers/map",array(
            "POST"    => array( "controller" => "drivers", "action" => "map" ),
        ));

        // 骑手扫码请求开门API
        $this->addRoutes("/drivers/scan",array(
            "POST"    => array( "controller" => "drivers", "action" => "scan" ),
        ));

        // 骑手扫码请求开门API【新】
        $this->addRoutes("/drivers/qrcode",array(
            "POST"    => array( "controller" => "drivers", "action" => "qrcode" ),
        ));

        // 骑手扫码轮询接口【新】
        $this->addRoutes("/drivers/room",array(
            "POST"    => array( "controller" => "drivers", "action" => "room" ),
        ));

        // 骑手扫码轮询接口
        $this->addRoutes("/drivers/result",array(
            "POST"    => array( "controller" => "drivers", "action" => "result" ),
        ));

        // 服务开通城市
        $this->addRoutes("/drivers/city",array(
            "GET"    => array( "controller" => "drivers", "action" => "city" ),
        ));

        // TODO: 换电柜二期
        // 换电记录
        $this->addRoutes("/v2/findChargingRecord",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "record" ),
        ));
        // 操作日志
        $this->addRoutes("/v2/findOperationLog",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "findOperationLog" ),
        ));
        // 换电柜管理
        $this->addRoutes("/v2/findChargingManage",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "findChargingManage" ),
        ));
        // 仓门管理
        $this->addRoutes("/v2/findRoomManage",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "findRoomManage" ),
        ));
        // 电池管理
        $this->addRoutes("/v2/findBatteryManage",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "findBatteryManage" ),
        ));
        // 异常信息管理
        $this->addRoutes("/v2/findAbnormal",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "findAbnormal" ),
        ));
        // 编辑网点
        $this->addRoutes("/v2/editCharging",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "editCharging" ),
        ));
        // 解除网点
        $this->addRoutes("/v2/delCharging",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "delCharging" ),
        ));
        // 异常信息处理
        $this->addRoutes("/v2/handleAbnormal",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "handleAbnormal" ),
        ));
        // 新增换电柜操作日志
        $this->addRoutes("/v2/addOperationLog",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "addOperationLog" ),
        ));
        // 请求开门、开始充电、停止充电指令
        $this->addRoutes("/v2/pubOperation",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "pubOperation" ),
        ));
        // 恢复门锁状态为正常状态
        $this->addRoutes("/v2/resetLock",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "resetLock" ),
        ));

        // 二期骑手接口
        // 查询换电柜版本
        $this->addRoutes("/v2/findVersion",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "findVersion" ),
        ));
        // 扫码打开换电柜
        $this->addRoutes("/v2/drivers/scan",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "OpenRoom" ),
        ));
        // 骑手换电轮询接口
        $this->addRoutes("/v2/drivers/Polling",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "PollingUpshot" ),
        ));


        // 新增推送
        $this->addRoutes("/v2/AddPush",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "AddPush" ),
        ));
        // 编辑推送（启用禁用编辑同一个接口）
        $this->addRoutes("/v2/EditPush",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "EditPush" ),
        ));
        // 查询推送记录
        $this->addRoutes("/v2/FindPushRecording",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "FindPushRecording" ),
        ));
        // 查询推送管理界面
        $this->addRoutes("/v2/FindPushList",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "FindPushList" ),
        ));
        // 查询推送详情
        $this->addRoutes("/v2/PushInfo",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "PushInfo" ),
        ));
        // 查询内部所有用户
        $this->addRoutes("/v2/InternalUser",array(
            "POST"    => array( "controller" => "Vertwo", "action" => "InternalUser" ),
        ));
    }
}
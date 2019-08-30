<?php
namespace app\modules\driversapp;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        // 骑手登陆接口
        $this->addRoutes("/login",array(
            "POST"    => array( "controller" => "index", "action" => "login" ),
        ));

        // 骑手个人信息接口
        $this->addRoutes("/driver",array(
            "GET"    => array( "controller" => "index", "action" => "driver" ),
        ));

        // 骑手修改个人资料
        $this->addRoutes("/upinfo",array(
            "PUT"    => array( "controller" => "index", "action" => "upinfo" ),
        ));

        // 获取/校验 验证码
        $this->addRoutes("/verifycode",array(
            // 获取验证码
            "PUT"    => array( "controller" => "index", "action" => "sendverifycode" ),
            // 校验验证码
            "POST"    => array( "controller" => "index", "action" => "checkverifycode" ),
        ));

        // 校验身份信息
        $this->addRoutes("/verifyidentity",array(
            // 校验身份信息
            "POST"    => array( "controller" => "index", "action" => "checkidentityinfo" ),
        ));

        // 骑手获取个人信息
        $this->addRoutes("/info",array(
            // 校验身份信息
            "GET"    => array( "controller" => "index", "action" => "driverinfo" ),
        ));

        $this->addRoutes("/repair",array(
            // 骑手APP发起维修申请
            "POST"    => array( "controller" => "operate", "action" => "Createrepair"),
            // 骑手获取维修单列表
            "GET"    => array( "controller" => "operate", "action" => "Repairlist"),
        ));

        $this->addRoutes("/repair/{id:[0-9]+}",array(
            // 骑手APP取消维修单
            "PUT"    => array( "controller" => "operate", "action" => "Cancelrepair"),
            // 骑手APP获取维修单详情
            "GET"    => array( "controller" => "operate", "action" => "Repairinfo"),
        ));

        $this->addRoutes("/msg",array(
            // 消息列表
            "GET"    => array( "controller" => "Msg", "action" => "Msglist"),
            // 批量已读
            "PUT"    => array( "controller" => "Msg", "action" => "Readmsgs"),
            // 批量已读
            "DELETE"    => array( "controller" => "Msg", "action" => "Delmsgs"),
        ));

        $this->addRoutes("/msg/{id:[0-9]+}",array(
            // 消息列表
            "GET"    => array( "controller" => "Msg", "action" => "Readmsg"),
        ));

        $this->addRoutes("/msg/unreadtotal",array(
            // 消息列表
            "GET"    => array( "controller" => "Msg", "action" => "Unreadtotal"),
        ));

        $this->addRoutes("/charging/bill",array(
            // 消息列表
            "POST"    => array( "controller" => "operate", "action" => "Chargingpaybill"),
        ));

        // 查询骑手是否有未支付服务
        $this->addRoutes("/unpaid/service",array(
            "GET"    => array( "controller" => "operate", "action" => "Unpaidservice"),
        ));

        // 骑手锁车
        $this->addRoutes("/lock",array(
            "POST"    => array( "controller" => "operate", "action" => "Lock"),
        ));
        $this->addRoutes("/unLock",array(
            "POST"    => array( "controller" => "operate", "action" => "UnLock"),
        ));

        $this->addRoutes("/message/tree",array(
            // 消息列表
            "GET"    => array( "controller" => "message", "action" => "tree"),
            "PUT"    => array( "controller" => "message", "action" => "updatetree"),
        ));

        $this->addRoutes("/peccancy",array(
            // 违章列表
            "GET"    => array( "controller" => 'Index', "action" => 'Peccancy'),
        ));

        // 得威出行APP首页门店地图
        $this->addRoutes("/StoreMap", [
            'POST'    => ['controller' => 'Index', 'action' => 'StoreMap'],
        ]);
    }
}
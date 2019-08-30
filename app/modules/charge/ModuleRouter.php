<?php
namespace app\modules\charge;

class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        $this->addRoutes("/device",array(
            "GET"    => array( "controller" => "device", "action" => "list" ),//获取设备
            "PUT"    => array( "controller" => "device", "action" => "update" ),//设备修改
            "POST"    => array( "controller" => "device", "action" => "create" ),//设备新增
            "DELETE"    => array( "controller" => "device", "action" => "delete" ),//删除设备
        ));

        $this->addRoutes("/device/detail",array(
            "GET"    => array( "controller" => "device", "action" => "detail" ),//获取详细信息
        ));
        $this->addRoutes("/search/site",array(
            "GET"    => array( "controller" => "device", "action" => "site" ),//获取站点信息
        ));
        $this->addRoutes("/log",array(
            "GET"    => array( "controller" => "log", "action" => "list" ),//获取硬件日志信息
        ));
        $this->addRoutes("/chart/fault",array(
            "GET"    => array( "controller" => "chart", "action" => "fault" ),//获取设备故障信息
        ));
        $this->addRoutes("/chart/pie",array(
            "GET"    => array( "controller" => "chart", "action" => "time" ),//获取时间排序
        ));
        $this->addRoutes("/chart/bar",array(
            "GET"    => array( "controller" => "chart", "action" => "bar" ),//获取地区排序
        ));
        $this->addRoutes("/chart/map",array(
            "GET"    => array( "controller" => "chart", "action" => "map" ),//获取地图
        ));
        $this->addRoutes("/chart/area",array(
            "GET"    => array( "controller" => "chart", "action" => "area" ),//获取地区排序
        ));
        $this->addRoutes("/chart/total",array(
            "GET"    => array( "controller" => "chart", "action" => "total" ),//获取总的充电次数
        ));
        $this->addRoutes("/chart/current",array(
            "GET"    => array( "controller" => "chart", "action" => "current" ),//获取当前的数据
        ));
        $this->add('[/]?');

    }
}
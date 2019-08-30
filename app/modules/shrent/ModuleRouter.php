<?php
namespace app\modules\shrent;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        //骑手发起APP支付
        $this->addRoutes("/pay",array(
            "POST" => array("controller" => "Transaction", "action" => "Apppay"),
        ));
        //骑手发起实人认证
        $this->addRoutes("/personcert",array(
            // 发起实人认证
            "GET" => array("controller" => "Transaction", "action" => "Personcert"),
            // APP实人认证完成
            "POST" => array("controller" => "Transaction", "action" => "Personcerted"),
        ));
        //骑手查看换电记录
        $this->addRoutes("/chargingrec",array(
            "GET" => array("controller" => "Driverapp", "action" => "Chargingrec"),
        ));
        //骑手取消支付单
        $this->addRoutes("/cancelpay",array(
            "POST" => array("controller" => "Transaction", "action" => "Cancelpay"),
        ));
    }
}
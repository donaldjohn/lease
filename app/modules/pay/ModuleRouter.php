<?php
namespace app\modules\pay;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        // 发起支付宝支付
        $this->addRoutes("/alipay/{type}",array(
            "GET" => array("controller" => "alipay", "action" => "Start"),
        ));
        // 支付宝异步回调
        $this->addRoutes("/aliasync",array(
            "POST" => array("controller" => "alipay", "action" => "Async"),
        ));
        // 发起微信支付
        $this->addRoutes("/wxpay/{type}",array(
            "GET" => array("controller" => "wxpay", "action" => "Start"),
        ));
        // 微信异步回调
        $this->addRoutes("/wxasync",array(
            "POST" => array("controller" => "wxpay", "action" => "Async"),
        ));
        // 微信退款异步回调
        $this->addRoutes("/wxrefundasync",array(
            "POST" => array("controller" => "wxpay", "action" => "Refundasync"),
        ));
        // 测试文件上传
        $this->addRoutes("/upfile",array(
            "POST" => array("controller" => "filetest", "action" => "upfile"),
        ));

        $this->add('[/]?');
    }
}
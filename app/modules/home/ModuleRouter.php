<?php
namespace app\modules\home;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
// curl
        $this->addRoutes("/router",array(
            "GET"    => array( "controller" => "index", "action" => "getAllRouter" ),
        ));

        // curl
        $this->addRoutes("/curl",array(
            "GET"    => array( "controller" => "index", "action" => "curl" ),
        ));

        // curl
        $this->addRoutes("/region",array(
            "GET"    => array( "controller" => "index", "action" => "region" ),
        ));


        // 用户登入
        $this->addRoutes("/login",array(
            "POST"    => array( "controller" => "login", "action" => "create" ),
        ));

        $this->addRoutes("/vcode",array(
            "GET"    => array( "controller" => "index", "action" => "vcode" ),
        ));

        $this->addRoutes("/test",array(
            "GET"    => array( "controller" => "index", "action" => "test" ),
        ));
        //安骑回调接口
        $this->addRoutes("/callback",array(
            "POST"    => array( "controller" => "vehicle", "action" => "callback" ),
        ));
        //文件上传接口
        $this->addRoutes("/upfile",array(
            "POST"    => array( "controller" => "file", "action" => "upbase" ),
        ));
        // 查看env定义
        $this->addRoutes("/infovwen",array(
            "POST"    => array( "controller" => "index", "action" => "catenvfile" ),
        ));
        // APP版本校验
        $this->addRoutes("/appverup",array(
            "GET"    => array( "controller" => "app", "action" => "checkver" ),
        ));

        // APP版本库
        $this->addRoutes("/appver",array(
            // 查看已有版本列表
            "GET"    => array( "controller" => "app", "action" => "list" ),
            // 新增新版本
            "POST"    => array( "controller" => "app", "action" => "craete" ),
        ));

        // APP版本库
        $this->addRoutes("/appver/{id:[0-9]+}",array(
            // 修改版本状态
            "PUT"    => array( "controller" => "app", "action" => "upver" ),
        ));


        // 查看env定义
        $this->addRoutes("/rsaencrypt",array(
            "GET"    => array( "controller" => "index", "action" => "rsaencrypt" ),
        ));


        // 用户登入
        $this->addRoutes("/micro/login",array(
            "POST"    => array( "controller" => "micro", "action" => "create" ),
        ));

        // 站点APP登入
        $this->addRoutes("/site/login",array(
            "POST"    => array( "controller" => "login", "action" => "Sitelogin" ),
        ));

        // 站点APP登入
        $this->addRoutes("/messages",array(
            "GET"    => array( "controller" => "message", "action" => "index" ),
        ));

        $this->addRoutes("/secondhand/login",array(
            "POST"    => array( "controller" => "secondhandmicro", "action" => "create" ),
        ));

        $this->addRoutes("/police-man/login",array(
            "POST"    => array( "controller" => "policeman", "action" => "create" ),
        ));

        // 用户登入
        $this->addRoutes("/black/check",array(
            "GET"    => array( "controller" => "login", "action" => "blackUserName" ),
        ));
        // 红绿灯
        $this->addRoutes("/traffic/light",array(
            "GET"    => array( "controller" => "traffic", "action" => "list" ),
        ));

        // 站点验证码登录
        $this->addRoutes("/site/login/sms",array(
            // 获取登录验证码
            "GET"    => array( "controller" => "login", "action" => "SiteLoginSendSMSCode" ),
            // 使用验证码登录
            "POST"    => array( "controller" => "login", "action" => "SiteLoginBySMSCode" ),
        ));


        // APP门店地图
        $this->addRoutes("/StoreMap",array(
            "POST"    => array( "controller" => "Store", "action" => "APPStoreMap" ),
        ));


        $this->addRoutes("/expressprintdriverslicensestatus",array(
            "GET"    => array( "controller" => "index", "action" => "getInsExpressPrintDriversLicenseStatus" ),
        ));
        $this->addRoutes("/updateinsname",array(
            "GET"    => array( "controller" => "index", "action" => "updateInsName" ),
        ));
    }

}
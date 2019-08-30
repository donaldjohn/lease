<?php
namespace app\modules\qrcode;

class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        $this->add('[/]?');
        //增加创建的路由
        $this->addRoutes("/index",array(
            "POST"    => array( "controller" => "index", "action" => "create" ),//二维码创建
            "GET"    => array( "controller" => "index", "action" => "index" ),//二维码列表信息
        ));
        $this->addRoutes("/count",array(
            "GET"    => array( "controller" => "index", "action" => "count" ),//获取二维码可发放的数量
        ));
        $this->addRoutes("/export",array(
            "GET"    => array( "controller" => "index", "action" => "export" ),//发放并导出二维码
        ));
        $this->addRoutes("/image",array(
            "GET"    => array( "controller" => "index", "action" => "image" ),//获取二维码图片信息
        ));
        $this->addRoutes("/ExportZip",array(
            "GET"    => array( "controller" => "index", "action" => "ExportZip" ),//导出二维码压缩包
        ));
        $this->add('[/]?');
    }
}
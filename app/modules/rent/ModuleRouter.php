<?php
namespace app\modules\rent;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        $this->addRoutes("/serviceitem",array(
            // 服务项列表
            "GET" => array("controller" => "Serviceitem", "action" => "List"),
            // 创建服务项
            "POST" => array("controller" => "Serviceitem", "action" => "Create"),
        ));

        $this->addRoutes("/serviceitem/{id:[0-9]+}",array(
            // 服务项详情
            "GET" => array("controller" => "Serviceitem", "action" => "One"),
            // 编辑服务项目
            "PUT" => array("controller" => "Serviceitem", "action" => "Update"),
            // 删除服务项
            "DELETE" => array("controller" => "Serviceitem", "action" => "Del"),
        ));

        $this->addRoutes("/serviceitem/upstatus",array(
            // 批量更新服务项目状态
            "POST" => array("controller" => "Serviceitem", "action" => "Upstatus"),
        ));

        $this->addRoutes("/package",array(
            // 服务套餐列表
            "GET" => array("controller" => "Package", "action" => "List"),
            // 创建服务套餐
            "POST" => array("controller" => "Package", "action" => "Create"),
        ));

        $this->addRoutes("/package/upstatus",array(
            // 批量变更服务套餐状态
            "POST" => array("controller" => "Package", "action" => "Upstatus"),
        ));

        $this->addRoutes("/package/{id:[0-9]+}",array(
            // 套餐详情
            "GET" => array("controller" => "Package", "action" => "One"),
            // 编辑套餐
            "PUT" => array("controller" => "Package", "action" => "Update"),
            // 删除套餐
            "DELETE" => array("controller" => "Package", "action" => "Del"),
        ));

        $this->addRoutes("/regiontree",array(
            // 获取区域树
            "GET" => array("controller" => "Package", "action" => "Regiontree"),
        ));
        $this->addRoutes("/returnbill",array(
            // 获取退款单列表
            "GET" => array("controller" => "Returnbill", "action" => "List"),
            // 审核退款单
            "PUT" => array("controller" => "Returnbill", "action" => "Audit"),
        ));
        $this->addRoutes("/serviceorder",array(
            // 获取服务单列表
            "GET" => array("controller" => "Serviceorder", "action" => "List"),
        ));
        $this->addRoutes("/serviceorder/{sn}",array(
            // 获取服务单列表
            "GET" => array("controller" => "Serviceorder", "action" => "One"),
        ));
        $this->addRoutes("/service/closure",array(
            // 后台结算服务单
            "POST" => array("controller" => "Serviceorder", "action" => "Closure"),
        ));
        $this->addRoutes("/depositbill",array(
            // 获取押金单列表
            "GET" => array("controller" => "Depositbill", "action" => "List"),
        ));
        $this->addRoutes("/paybill",array(
            // 获取支付单列表
            "GET" => array("controller" => "Paybill", "action" => "List"),
        ));
        $this->addRoutes("/flowbill",array(
            // 获取支付单列表
            "GET" => array("controller" => "Flowbill", "action" => "List"),
        ));
        $this->addRoutes("/rentorder",array(
            // 获取租赁单列表
            "GET" => array("controller" => "Rentorder", "action" => "List"),
        ));

        $this->addRoutes("/repairup",array(
            // 更新维修单接口
            "POST" => array("controller" => "Repairorder", "action" => "update"),
        ));
        $this->addRoutes("/refund",array(
            // 后台发起退款
            "POST" => array("controller" => "Returnbill", "action" => "Refund"),
        ));

        // 租赁业务区域管理
        $this->addRoutes("/area",array(
            // 列表
            "GET" => array("controller" => "Rentarea", "action" => "List"),
            // 编辑区域
            "POST" => array("controller" => "Rentarea", "action" => "Edit"),
        ));


        $this->addRoutes("/repairorder",array(
            //维修单接口
            "GET" => array("controller" => "Repairorder", "action" => "list"),
        ));
        $this->addRoutes("/warrantybill",array(
            //联保单接口
            "GET" => array("controller" => "Warrantybill", "action" => "list"),
        ));

        $this->FastRoutes([
            // 租赁车辆列表
            'GET /Vehicle' => ['Vehicle', 'List'],
            // 导出租赁车辆列表
            'GET /Vehicle/Export' => ['Vehicle', 'Export'],
            // 租赁车辆详情
            'GET /Vehicle/{vehicleId:[0-9]+}' => ['Vehicle', 'Info'],
            // 解绑门店
            'POST /Vehicle/UnBindStore' => ['Vehicle', 'UnBindStore'],
            // 编辑车辆
            'PUT /Vehicle/{vehicleId:[0-9]+}' => ['Vehicle', 'EditVehicle'],
            // 服务费列表
            'GET /Servicefee' => ['Servicefee', 'List'],
            // 解除服务
            'PUT /RescindContract/{serviceContractId:[0-9]+}' => ['Serviceorder', 'RescindContract'],
            // 骑手列表
            'GET /Driver' => ['Driver', 'List'],
            // 换电单列表
            'GET /Charging' => ['Charging', 'List'],
        ]);
    }
}
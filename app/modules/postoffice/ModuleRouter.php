<?php
namespace app\modules\postoffice;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        //新增商品API
        $this->addRoutes("/goods",array(
            "GET" => array("controller" => "goods", "action" => "list"),
            "POST" => array("controller" => "goods", "action" => "create"),
        ));
        $this->addRoutes("/goods/{id:[0-9]+}",array(
            "GET" => array("controller" => "goods", "action" => "one"),
            "PUT" => array("controller" => "goods", "action" => "update"),
            "DELETE" => array("controller" => "goods", "action" => "delete"),
        ));

        //新增保单API
        $this->addRoutes("/warranty",array(
            "GET" => array("controller" => "warranty", "action" => "list"),
            "POST" => array("controller" => "warranty", "action" => "create"),
        ));
        $this->addRoutes("/warranty/{id:[0-9]+}",array(
            "GET" => array("controller" => "warranty", "action" => "one"),
            "PUT" => array("controller" => "warranty", "action" => "update"),
            "DELETE" => array("controller" => "warranty", "action" => "delete"),
        ));

        $this->addRoutes("/warranty/leadin",array(
            "POST" => array("controller" => "warranty", "action" => "leadin"),
        ));

        //新增行驶证API
        $this->addRoutes("/license",array(
            "GET" => array("controller" => "license", "action" => "list"),
            "POST" => array("controller" => "license", "action" => "create"),
        ));
        $this->addRoutes("/license/{id:[0-9]+}",array(
            "GET" => array("controller" => "license", "action" => "one"),
            "PUT" => array("controller" => "license", "action" => "update"),
            "DELETE" => array("controller" => "license", "action" => "delete"),
        ));

        //商品目录API
        $this->addRoutes("/catalogue",array(
            "POST" => array("controller" => "goods", "action" => "cataloguelist"),
        ));

        //商品品牌API
        $this->addRoutes("/goodsbrand",array(
            "GET" => array("controller" => "goods", "action" => "brandlist"),
        ));
        //待保车辆API
        $this->addRoutes("/securevehicle",array(
            "GET" => array("controller" => "warranty", "action" => "secureVehicle"),
        ));
        // 保险公司获取快递公司下拉列表
        $this->addRoutes("/express/select",array(
            "GET" => array("controller" => "warranty", "action" => "Selectexpress"),
        ));
        $this->addRoutes('/insurance/export/vehicle',[
            // 保险公司导出车辆
            'GET' => ['controller' => 'Warranty', 'action' => 'ExportVehicle'],
        ]);

        // 车辆牌照配额
        $this->addRoutes('/license/quota',[
            // 配额申请列表
            'GET' => ['controller' => 'Licensequota', 'action' => 'List'],
            // 快递公司申请配额
            'POST' => ['controller' => 'Licensequota', 'action' => 'Add'],
        ]);
        $this->addRoutes('/license/quota/{id:[0-9]+}',[
            // 配额提交/审核
            'PUT' => ['controller' => 'Licensequota', 'action' => 'Process'],
        ]);
        $this->addRoutes('/UsedQuota',[
            // 网点配额使用记录
            'GET' => ['controller' => 'Licensequota', 'action' => 'UsedQuotaList'],
        ]);
        $this->addRoutes('/ExpressQuota',[
            // 快递公司配额使用情况列表
            'GET' => ['controller' => 'Licensequota', 'action' => 'ExpressQuotaList'],
        ]);
        $this->addRoutes('/express/quota/info',[
            // 快递公司自己的配额使用量
            'GET' => ['controller' => 'Licensequota', 'action' => 'ExpressQuotaInfo'],
        ]);

        // 年检列表
        $this->addRoutes('/inspection',[
            'GET' => ['controller' => 'Vehicleinspection', 'action' => 'List'],
        ]);
        // 年检审核
        $this->addRoutes('/inspection/audit',[
            'PUT' => ['controller' => 'Vehicleinspection', 'action' => 'Audit'],
        ]);
        // 年检激活
        $this->addRoutes('/inspection/active',[
            'PUT' => ['controller' => 'Vehicleinspection', 'action' => 'Active'],
        ]);
        // 年检审核前详情
        $this->addRoutes('/inspection/{id:[0-9]+}',[
            'GET' => ['controller' => 'Vehicleinspection', 'action' => 'Info'],
        ]);
        // 车辆年检历史
        $this->addRoutes('/inspection/history/{id:[0-9]+}',[
            'GET' => ['controller' => 'Vehicleinspection', 'action' => 'Record'],
        ]);

        $this->addRoutes('/inspection/item',[
            // 年检项目列表
            'GET' => ['controller' => 'Vehicleinspection', 'action' => 'ItemList'],
            // 新增年检项目
            'POST' => ['controller' => 'Vehicleinspection', 'action' => 'AddItem'],
        ]);
        $this->addRoutes('/inspection/item/{id:[0-9]+}',[
            // 年检项目详情
            'GET' => ['controller' => 'Vehicleinspection', 'action' => 'ItemInfo'],
            // 编辑年检项目
            'PUT' => ['controller' => 'Vehicleinspection', 'action' => 'EditItem'],
            // 删除年检项目
            'DELETE' => ['controller' => 'Vehicleinspection', 'action' => 'DelItem'],
        ]);
        $this->addRoutes('/inspection/item/status/{id:[0-9]+}',[
            // 启禁用年检项目
            'PUT' => ['controller' => 'Vehicleinspection', 'action' => 'ItemStatus'],
        ]);
        $this->addRoutes('/inspection/print',[
            // 年检任务批量打印
            'POST' => ['controller' => 'Vehicleinspection', 'action' => 'BatchPrint'],
        ]);
        $this->addRoutes('/inspection/print/{id:[0-9]+}',[
            // 年检任务单个打印
            'POST' => ['controller' => 'Vehicleinspection', 'action' => 'Print'],
        ]);


        $this->addRoutes('/inspection/statistics/company',[
            // 快递协会查看快递公司年检情况统计
            'GET' => ['controller' => 'Checkreportform', 'action' => 'CompanyStatistics'],
        ]);
        $this->addRoutes('/inspection/statistics/site',[
            // 快递公司查看站点年检情况统计
            'GET' => ['controller' => 'Checkreportform', 'action' => 'SiteStatistics'],
        ]);
        $this->addRoutes('/inspection/statistics/company/export',[
            // 导出快递公司年检情况统计
            'GET' => ['controller' => 'Checkreportform', 'action' => 'ExportCompanyStatistics'],
        ]);
        $this->addRoutes('/inspection/statistics/site/export',[
            // 导出站点年检情况统计
            'GET' => ['controller' => 'Checkreportform', 'action' => 'ExportSiteStatistics'],
        ]);
        $this->addRoutes('/inspection/select/site',[
            // 快递公司下拉站点列表【查看站点年检情况统计页面用】
            'GET' => ['controller' => 'Checkreportform', 'action' => 'SelectSite'],
        ]);
        $this->addRoutes('/inspection/select/express',[
            // 快递协会下拉快递公司【查看年检情况统计页面用】
            'GET' => ['controller' => 'Checkreportform', 'action' => 'SelectExpress'],
        ]);

        // 邮管系统参数
        $this->addRoutes('/system/param',[
            'GET' => ['controller' => 'Systemparam', 'action' => 'List'],
            'POST' => ['controller' => 'Systemparam', 'action' => 'Edit'],
        ]);

        // 添加保单附件
        $this->addRoutes('/Warranty/SecureFile/{id:[0-9]+}',[
            'POST' => ['controller' => 'Warranty', 'action' => 'AddSecureFile'],
        ]);
        // 批量删除保单
        $this->addRoutes('/Warranty/BatchDel',[
            'POST' => ['controller' => 'Warranty', 'action' => 'BatchDel'],
        ]);

        // 【透传】编辑配额
        $this->addRoutes('/license/quota/edit',[
            'POST' => ['controller' => 'Licensequota', 'action' => 'Edit'],
        ]);
        // 【透传】删除配额
        $this->addRoutes('/license/quota/del',[
            'POST' => ['controller' => 'Licensequota', 'action' => 'Del'],
        ]);



        $this->add('[/]?');
    }
}
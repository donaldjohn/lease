<?php
namespace app\modules\traffic;

class ModuleRouter extends \app\modules\ModuleRouter {

    public function initialize()
    {

        $this->addRoutes('/general', [
            'POST' => array('controller' => 'App', 'action' => 'general'),
        ]);

        // 违章管理
        $this->addRoutes('/Peccancy', [
            // 违章列表
            'GET' => array('controller' => 'Peccancy', 'action' => 'List'),
            // 交管APP违章开单
            'POST' => array('controller' => 'App', 'action' => 'Add'),
        ]);
        // 违章处理
        $this->addRoutes('/Peccancy/Process/{id:[0-9]+}', [
            // 处理详情
            'GET' => array('controller' => 'Peccancy', 'action' => 'Processinfo'),
            // 处理违章
            'PUT' => array('controller' => 'Peccancy', 'action' => 'Process'),
        ]);
        // 违章轨迹
        $this->addRoutes('/Peccancy/Locus/{id:[0-9]+}', [
            // 违章轨迹
            'GET' => array('controller' => 'Peccancy', 'action' => 'Locus'),
        ]);

        // 道路管理
        $this->addRoutes('/Road', [
            // 道路列表
            'GET' => array('controller' => 'Road', 'action' => 'List'),
            // 新增
            'POST' => array('controller' => 'Road', 'action' => 'AddRoad'),
        ]);
        // 道路编辑/删除
        $this->addRoutes('/Road/{id:[0-9]+}', [
            // 道路编辑启禁用
            'PUT' => array('controller' => 'Road', 'action' => 'EditRoad'),
            // 删除道路
            'DELETE' => array('controller' => 'Road', 'action' => 'DelRoad'),
        ]);

        // 路段管理
        $this->addRoutes('/RoadSection', [
            // 路段列表
            'GET' => array('controller' => 'Road', 'action' => 'RoadSectionList'),
            // 新增
            'POST' => array('controller' => 'Road', 'action' => 'AddRoadSection'),
        ]);
        // 路段标记
        $this->addRoutes('/RoadSection/Mark/{id:[0-9]+}', [
            // 路段标记
            'PUT' => array('controller' => 'Road', 'action' => 'MarkRoadSection'),
        ]);
        // 路段编辑/删除
        $this->addRoutes('/RoadSection/{id:[0-9]+}', [
            // 路段编辑启禁用
            'PUT' => array('controller' => 'Road', 'action' => 'EditRoadSection'),
            // 删除路段
            'DELETE' => array('controller' => 'Road', 'action' => 'DelRoadSection'),
        ]);


        //禁行区
        $this->addRoutes("/noentry",array(
            "GET"    => array( "controller" => "noentry", "action" => "list" ),//获取设备
            "PUT"    => array( "controller" => "noentry", "action" => "update" ),//设备修改
            "POST"    => array( "controller" => "noentry", "action" => "create" ),//设备新增
            "DELETE"    => array( "controller" => "noentry", "action" => "del" ),//删除设备
        ));
        $this->addRoutes("/noentry/status",array(
            "PUT" => array("controller" => "noentry", "action" => "status"),//启用禁用
        ));
        $this->addRoutes("/noentry/detail",array(
            "GET" => array("controller" => "noentry", "action" => "detail"),//详细信息
        ));
        $this->addRoutes("/noentry/area",array(
            "GET" => array("controller" => "noentry", "action" => "area"),//详细信息
        ));
        $this->addRoutes("/noentry/userarea",array(
            "GET" => array("controller" => "noentry", "action" => "userarea"),//查询指定行政区域内所有未删除且时间冲突的禁行区
        ));
        //禁行区
        $this->addRoutes("/noparking",array(
            "GET"    => array( "controller" => "noparking", "action" => "list" ),//获取设备
            "PUT"    => array( "controller" => "noparking", "action" => "update" ),//设备修改
            "POST"    => array( "controller" => "noparking", "action" => "create" ),//设备新增
            "DELETE"    => array( "controller" => "noparking", "action" => "del" ),//删除设备
        ));
        $this->addRoutes("/noparking/status",array(
            "PUT" => array("controller" => "noparking", "action" => "status"),//启用禁用
        ));
        $this->addRoutes("/noparking/area",array(
            "GET" => array("controller" => "noparking", "action" => "area"),//区域下面的禁停区
        ));
        $this->addRoutes("/noparking/detail",array(
            "GET" => array("controller" => "noparking", "action" => "parkingList"),//禁停区域下面的停车区列表
            "POST" => array("controller" => "noparking", "action" => "parkingCreate"),//禁停区域下面的停车区编辑
        ));
        $this->addRoutes("/police/list",array(
            "GET" => array("controller" => "police", "action" => "list"),//交警执法车辆列表
        ));
        $this->addRoutes("/police/rail",array(
            "GET" => array("controller" => "police", "action" => "rail"),//交警执法页面围栏
        ));
        $this->addRoutes("/police/detail",array(
            "GET" => array("controller" => "police", "action" => "detail"),//交警执法页面车辆详细信息
        ));
        // 交管APP获取车辆信息
        $this->addRoutes('/vehicle', [
            'GET' => array('controller' => 'App', 'action' => 'Vehicle'),
        ]);
        // 交管APP获取开单
        $this->addRoutes('/app/record', [
            'GET' => array('controller' => 'App', 'action' => 'Recordlist'),
        ]);
        // 交管APP作废违章单
        $this->addRoutes('/app/abolition/{id:[0-9]+}', [
            'PUT' => array('controller' => 'App', 'action' => 'Abolition'),
        ]);
        // 交管APP核对骑手
        $this->addRoutes('/app/CheckDriver', [
            'POST' => array('controller' => 'App', 'action' => 'CheckDriver'),
        ]);
        // 交管APP获取附近车辆
        $this->addRoutes('/app/NearbyVehicle', [
            'POST' => array('controller' => 'App', 'action' => 'NearbyVehicle'),
        ]);
        // 交管APP获取附近车辆信息
        $this->addRoutes('/app/NearbyVehicle/{id:[0-9]+}', [
            'GET' => array('controller' => 'App', 'action' => 'VehicleDetail'),
        ]);

        // 查询违章-车辆列表
        $this->addRoutes('/map/vehicle', [
            'POST' => array('controller' => 'Vehicle', 'action' => 'MapVehicleList'),
        ]);
        // 根据子系统机构id查询快递公司信息
        $this->addRoutes('/sub/company', [
            'POST' => array('controller' => 'Vehicle', 'action' => 'SubInsIdFindCompany'),
        ]);
        // 获取GPS点列表
        $this->addRoutes('/gps', [
            'POST' => array('controller' => 'Vehicle', 'action' => 'GPSList'),
        ]);
        // 获取GPS点去重复
        $this->addRoutes('/gpsdelreppoint', [
            'POST' => array('controller' => 'Vehicle', 'action' => 'GPSListDelRepPoint'),
        ]);


        // 违章处理规则列表
        $this->addRoutes('/peccancy/rule', [
            'GET' => array('controller' => 'Peccancyrule', 'action' => 'List'),
            'POST' => array('controller' => 'Peccancyrule', 'action' => 'Add'),
        ]);
        $this->addRoutes('/peccancy/rule/{id:[0-9]+}', [
            // 更新违章处理规则
            'PUT' => array('controller' => 'Peccancyrule', 'action' => 'Edit'),
        ]);
        // 违章处理规则列表
        $this->addRoutes('/select/peccancyType', [
            'GET' => array('controller' => 'Peccancyrule', 'action' => 'SelectPeccancyType'),
        ]);

        $this->addRoutes('/peccancyParam', [
            // 获取违章锁车和短信发送按钮状态
            'GET' => array('controller' => 'Peccancyrule', 'action' => 'PeccancyParamStatus'),
            // 新增或更新违章按钮参数
            'PUT' => array('controller' => 'Peccancyrule', 'action' => 'EditPeccancyParam'),
        ]);
        //
        $this->addRoutes('/Peccancy/{id:[0-9]+}', [
            // 处理违章记录
            'PUT' => array('controller' => 'Peccancy', 'action' => 'ProcessPeccancy'),
        ]);
    }
}
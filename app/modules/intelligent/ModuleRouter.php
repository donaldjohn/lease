<?php
namespace app\modules\intelligent;



class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        // 智能设备
        $this->addRoutes('/DeviceModel', [
            'GET'    => ['controller' => 'Devicemodel', 'action' => 'List'],
            'POST'    => ['controller' => 'Devicemodel', 'action' => 'Add'],
        ]);
        $this->addRoutes('/DeviceModel/{id:[0-9]+}', [
            'PUT'    => ['controller' => 'Devicemodel', 'action' => 'Edit'],
            'DELETE'    => ['controller' => 'Devicemodel', 'action' => 'Del'],
        ]);

        // 下拉选择供应商
        $this->addRoutes('/select/supplier', [
            'GET'    => ['controller' => 'Select', 'action' => 'Supplier'],
        ]);
        // 下拉选择站点
        $this->addRoutes('/select/site', [
            'GET'    => ['controller' => 'Select', 'action' => 'Site'],
        ]);
        // 下拉选择智能设备型号
        $this->addRoutes('/select/deviceModel', [
            'GET'    => ['controller' => 'Select', 'action' => 'DeviceModel'],
        ]);

        // 邮管车辆数据
        $this->addRoutes('/postOffice/vehicle', [
            'GET'    => ['controller' => 'Postofficevehicle', 'action' => 'List'],
        ]);
        $this->addRoutes('/postOffice/vehicle/{id:[0-9]+}', [
            'PUT'    => ['controller' => 'Postofficevehicle', 'action' => 'Edit'],
            'DELETE'    => ['controller' => 'Postofficevehicle', 'action' => 'Del'],
        ]);

        // 32795 查询频率配置【透传】
        $this->addRoutes('/frequency/info', [
            'POST'    => ['controller' => 'Devicemodel', 'action' => 'FindFrequencySet'],
        ]);
        // 32792 新增频率配置【透传】
        $this->addRoutes('/frequency/add', [
            'POST'    => ['controller' => 'Devicemodel', 'action' => 'AddFrequencySet'],
        ]);
        // 32793 更新频率配置【透传】
        $this->addRoutes('/frequency/update', [
            'POST'    => ['controller' => 'Devicemodel', 'action' => 'UpdateFrequencySet'],
        ]);


    }
}
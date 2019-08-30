<?php
namespace app\models\service;

// 车辆用途表
class VehicleUsage extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_vehicle_usage');
    }

}

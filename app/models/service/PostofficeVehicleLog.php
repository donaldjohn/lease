<?php
namespace app\models\service;

// 邮管车辆日志表
class PostofficeVehicleLog extends BaseModel
{
    const OPERATOR_TYPE_SYSTEM = 1;
    const OPERATOR_TYPE_USER = 2;
    const OPERATOR_TYPE_DRIVER = 3;


    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_postoffice_vehicle_log');
    }

}

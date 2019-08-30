<?php
namespace app\models\service;

// 设备型号
class DeviceModel extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_device_model');
    }

}

<?php
namespace app\models\dispatch;

class DriverIns extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_driver_ins");
    }
}
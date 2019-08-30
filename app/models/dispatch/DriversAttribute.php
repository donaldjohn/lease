<?php
namespace app\models\dispatch;

class DriversAttribute extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_drivers_attribute");
    }
}
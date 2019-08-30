<?php
namespace app\models\dispatch;

class DriversIdentification extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_drivers_identification");
    }
}
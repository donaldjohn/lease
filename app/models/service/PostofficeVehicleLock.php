<?php
namespace app\models\service;

class PostofficeVehicleLock extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_postoffice_vehicle_lock");
    }
}
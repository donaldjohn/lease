<?php
/**
 * Created by PhpStorm.
 * User: Lishiqin
 * Date: 2018/8/22
 * Time: 14:10
 */
namespace app\models\service;

class VehicleArea extends BaseModel
{
    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_vehicle_area");
    }
}
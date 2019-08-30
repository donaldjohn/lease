<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/28
 * Time: 17:35
 */
namespace app\models;

use Phalcon\Mvc\Model;

class VehicleRail extends Model
{
    /**
     * gps数据
     */
    public function initialize()
    {
        $this->setConnectionService('dw_service');
        $this->setSource("dw_rail_vehicle");
    }
}
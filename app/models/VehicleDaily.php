<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/22
 * Time: 15:27
 */
namespace app\models;

use Phalcon\Mvc\Model;

class VehicleDaily extends Model
{
    /**
     * 每日报表信息
     */
    public function initialize()
    {
        $this->setConnectionService("db");
        $this->setSource("fa_vehicle_daily");
    }
}
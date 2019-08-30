<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models\order;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

class VehicleRepairOrder extends BaseModel
{
    /**
     * 车辆维修订单表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_vehicle_repair_order");
    }

    public function beforeCreate()
    {
        $this->create_at = time();
        $this->update_at = 0;
    }

    public function beforeUpdate()
    {
        $this->update_at = time();
    }

}
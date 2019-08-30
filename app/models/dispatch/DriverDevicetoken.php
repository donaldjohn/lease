<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models\dispatch;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

class DriverDevicetoken extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_driver_devicetoken");
    }

    public function beforeCreate()
    {
        $this->create_at = time();
    }
}
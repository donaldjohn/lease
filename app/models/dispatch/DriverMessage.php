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

class DriverMessage extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_driver_message");
    }

    public function beforeCreate()
    {
        $this->is_read = 1;
        $this->is_delete = 1;
        $this->message_time = time();
    }
}
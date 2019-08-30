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

class Deposit extends BaseModel
{
    /**
     * 押金单表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_deposit");
    }

}
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

class PayBill extends BaseModel
{
    // 1：待支付 2：已支付
    const NotPay = 1;
    const IsPay = 2;

    /**
     * 支付单表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_pay_bill");
    }

    // 查询待支付支付单
    public static function arrFindFirstUnpaid($data)
    {
        $data['pay_status'] = 1;
        $data['is_delete'] = 0;
        return self::arrFindFirst($data);
    }

}
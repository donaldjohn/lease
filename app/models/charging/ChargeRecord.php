<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 13:31
 */
namespace app\models\charging;

class ChargeRecord extends BaseModel
{
    /**
     * 换电柜
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_charge_record");
    }
}
<?php
namespace app\models\order;

class PayRecord extends BaseModel
{
    /**
     * 支付流水表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_pay_record");
    }

}
<?php
namespace app\models\order;

class ReturnBill extends BaseModel
{
    /**
     * 支付单表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_return_bill");
    }

}
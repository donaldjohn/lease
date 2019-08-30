<?php
namespace app\models\order;


class ChargingOrder extends BaseModel
{
    /**
     * 换电记录表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_charging_order");
    }

}
<?php
namespace app\models\service;


class AreaPowerExchangePrice extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_area_power_exchange_price");
    }

}
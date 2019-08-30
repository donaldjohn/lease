<?php
namespace app\models\cabinet;

class ChargingManage extends BaseModel
{
    /**
     * 二代换电柜管理表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_charging_manage");
    }
}
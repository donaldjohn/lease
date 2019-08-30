<?php
namespace app\models\dispatch;

class Drivers extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_drivers");
    }
}
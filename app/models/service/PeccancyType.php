<?php
namespace app\models\service;

// 违章类型表
class PeccancyType extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_peccancy_type");
    }

}

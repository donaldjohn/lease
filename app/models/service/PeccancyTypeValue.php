<?php
namespace app\models\service;

// 违章类型属性表
class PeccancyTypeValue extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_peccancy_type_value");
    }

}

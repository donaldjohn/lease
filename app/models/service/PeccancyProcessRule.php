<?php
namespace app\models\service;

// 违章处理规则表
class PeccancyProcessRule extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_peccancy_process_rule");
    }

}

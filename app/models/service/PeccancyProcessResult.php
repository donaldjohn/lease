<?php
namespace app\models\service;

// 处理违章结果表
class PeccancyProcessResult extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_peccancy_process_result");
    }

}

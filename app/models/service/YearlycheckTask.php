<?php
namespace app\models\service;

// 年检任务表
class YearlycheckTask extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_yearlycheck_task');
    }

}

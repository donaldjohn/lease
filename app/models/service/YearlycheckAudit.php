<?php
namespace app\models\service;

// 年检审核记录表
class YearlycheckAudit extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_yearlycheck_audit');
    }

}

<?php
namespace app\models\service;

// 违章记录
class PeccancyRecord extends BaseModel
{
    const UN_PROCESS = 0; // 待处理
    const PROCESSED = 1; // 已处理
    const INVALID = 20; // 作废

    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_peccancy_record");
    }

}

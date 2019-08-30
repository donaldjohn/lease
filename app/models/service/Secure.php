<?php
namespace app\models\service;

// 保单表
class Secure extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_secure');
    }

}

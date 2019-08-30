<?php
namespace app\models\service;

// 道路表
class Road extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_road');
    }

}

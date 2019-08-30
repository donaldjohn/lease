<?php
namespace app\models\service;

// 路段表
class RoadSection extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_road_section");
    }

}

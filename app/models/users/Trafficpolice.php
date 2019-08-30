<?php
namespace app\models\users;

class Trafficpolice extends BaseModel
{
    // 交警队
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_trafficpolice');
    }
}
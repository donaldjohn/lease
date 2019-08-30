<?php

namespace app\models\users;

class Operator extends BaseModel
{

    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_operator");
    }

}

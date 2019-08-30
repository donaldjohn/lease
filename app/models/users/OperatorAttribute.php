<?php

namespace app\models\users;

class OperatorAttribute extends BaseModel
{

    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_operator_attribute");
    }

}

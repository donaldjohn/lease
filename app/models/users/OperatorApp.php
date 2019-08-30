<?php

namespace app\models\users;

class OperatorApp extends BaseModel
{

    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_operator_app_relation");
    }

}

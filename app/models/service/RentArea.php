<?php

namespace app\models\service;

class RentArea extends BaseModel
{

    /**
     *
     * @var integer
     */
    public $area_id;


    public $ins_id;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_rent_area");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_rent_area';
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29 0029
 * Time: 17:32
 */
namespace app\models\phpems;

class X2Examsession extends BaseModel {

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("x2_examsession");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_examsession';
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 13:31
 */
namespace app\models\cabinet;

class Cabinet extends BaseModel
{
    /**
     * 充电柜管理表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_cabinet");
    }
}
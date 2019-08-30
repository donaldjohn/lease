<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/3
 * Time: 19:39
 */
namespace app\models\dispatch;

class Site extends BaseModel
{
    /**
     * 换电柜
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_site");
    }
}
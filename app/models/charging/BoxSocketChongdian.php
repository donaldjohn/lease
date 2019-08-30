<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 13:31
 */
namespace app\models\charging;

class BoxSocketChongdian extends BaseModel
{
    /**
     * 换电柜
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("box_socket_chongdian");
    }
}
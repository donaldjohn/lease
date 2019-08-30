<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/17
 * Time: 9:47
 */
namespace app\models\users;

use app\models\MyBaseModel;

class BaseModel extends MyBaseModel
{
    public function initialize()
    {
        $this->setConnectionService("dw_users");
    }
}
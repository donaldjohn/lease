<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/17
 * Time: 9:47
 */
namespace app\models\cabinet;

use app\models\MyBaseModel;

class BaseModel extends MyBaseModel
{
    public function initialize()
    {
        $this->setConnectionService('dw_cabinet');//充电桩硬件服务器的数据库
    }
}
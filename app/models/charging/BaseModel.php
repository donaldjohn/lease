<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/17
 * Time: 9:47
 */
namespace app\models\charging;

use app\models\MyBaseModel;

class BaseModel extends MyBaseModel
{
    /**
     * 充电桩硬件服务器的数据库
     */
    public function initialize()
    {
        $this->setConnectionService('charging');
    }
}
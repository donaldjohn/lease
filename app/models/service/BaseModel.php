<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/17
 * Time: 9:47
 */
namespace app\models\service;

use app\models\MyBaseModel;

class BaseModel extends MyBaseModel
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
        $this->setConnectionService('dw_service');
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models;

use Phalcon\Mvc\Model;

class Rail extends Model
{
    /**
     * 每日报表信息
     */
    public function initialize()
    {
        $this->setConnectionService('dw_service');
        $this->setSource("dw_rail");
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: lishiqin
 * Date: 2018/7/22
 * Time: 13:41
 */
namespace app\models;

use Phalcon\Mvc\Model;

class Cabinet extends Model
{
    /**
     * 换电柜
     */
    public function initialize()
    {
        $this->setConnectionService("dw_cabinet");
        $this->setSource("dw_cabinet");
    }
}
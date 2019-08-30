<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/3
 * Time: 19:39
 */
namespace app\models\dispatch;

class SiteDrivers extends BaseModel
{
    /**
     *
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_site_drivers");
    }
}
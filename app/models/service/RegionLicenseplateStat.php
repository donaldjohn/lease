<?php
namespace app\models\service;

// 网点牌照配额使用记录表
class RegionLicenseplateStat extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_region_licenseplate_stat");
    }

}

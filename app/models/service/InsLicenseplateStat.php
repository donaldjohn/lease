<?php
namespace app\models\service;

// 机构牌照配额统计表
class InsLicenseplateStat extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_ins_licenseplate_stat");
    }

}

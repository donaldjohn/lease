<?php
namespace app\models\order;

class ServicePackageRelation extends BaseModel
{
    /**
     * 服务单与套餐关联表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_service_package_relation");
    }
}
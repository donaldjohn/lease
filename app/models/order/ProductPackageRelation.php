<?php
namespace app\models\order;

/**
 * 套餐商品关系表
 */
class ProductPackageRelation extends BaseModel
{
    // 套餐车辆商品类型
    const ProductTypeVehicle = 1;
    // 套餐电池商品类型
    const ProductTypeBattery = 2;

    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_product_package_relation");
    }
}
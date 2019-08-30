<?php
namespace app\models\order;

class ProductPackage extends BaseModel
{
    /**
     * 商品套餐表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_product_package");
    }

}
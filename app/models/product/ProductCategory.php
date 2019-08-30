<?php
namespace app\models\product;

class ProductCategory extends BaseModel
{
    /**
     * 商品类别目录
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_product_category");
    }

}
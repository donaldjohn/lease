<?php
namespace app\models\product;

class Product extends BaseModel
{
    /**
     * 商品
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_product");
    }

}
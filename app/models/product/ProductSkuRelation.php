<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models\product;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

class ProductSkuRelation extends BaseModel
{
    /**
     * 商品sku
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_product_sku_relation");
    }

}
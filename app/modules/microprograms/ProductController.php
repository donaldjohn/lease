<?php
namespace app\modules\microprograms;


use app\modules\BaseController;

/**
 * Class ProductController
 * 分类、品牌、商品及规格获取类
 * @author Lishiqin
 * @package app\modules\microprograms
 */
class ProductController extends BaseController
{

    /**
     * 获取三级分类
     * @param int parentCategoryId 分类父级ID（必填）
     * @return mixed
     */
    public function CategoryAction()
    {
        // 分类父级ID有效性判断
        $request = $this->request->get();
        $parentCategoryId = isset($request['parentCategoryId']) ? $request['parentCategoryId'] : 0;
        if ($parentCategoryId < 0) {
            return $this->toError(500, '分类父级ID无效');
        }

        // 如果$parentCategoryId = 0,获取一级分类，不为0获取对应的分类列表
        if ($parentCategoryId == 0) {
            $param['categoryLevel'] = $parentCategoryId;
        } else {
            $param['parentCategoryId'] = $parentCategoryId;
        }

        $params = [
            'code' => 10001,
            'parameter' => $param
        ];

        // 请求微服务接口获取分类列表
        $result = $this->curl->httpRequest($this->Zuul->product, $params, "POST");

        // 判断结果，并返回
        if ($result['statusCode'] == 200 && count($result['content']['productCategoryList']) > 0) {
            $data = $result['content']['productCategoryList'];
            foreach ($data as $key => $value) {
                $data[$key]["id"]            = $value["id"];
                $data[$key]["category_name"] = $value["categoryName"];
            }
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '获取分类列表失败');
        }
    }

    /**
     * 获取品牌列表
     * @param int category3Id 三级分类ID
     * @return mixed
     */
    public function BrandAction()
    {
        // 判断三级分类的ID有效性
        $request = $this->request->get();
        $categoryId = isset($request['category3Id']) ? $request['category3Id'] : 0;

        if ($categoryId <= 0) {
            return $this->toError(500, '三级分类ID无效');
        }

        $params = [
            "code" => 10003,
            "parameter" => [
                "categoryId" => $categoryId
            ]
        ];

        // 请求微服务接口获取三级分类与品牌的关系
        $result = $this->curl->httpRequest($this->Zuul->product, $params, "POST");

        if ($result['statusCode'] == 200 && count($result["content"]["productCategoryBrandRelationList"]) > 0) {
            $brandIdList = [];
            foreach ($result["content"]["productCategoryBrandRelationList"] as $key => $value) {
                $brandIdList[$key]["id"] = $value["brandId"];
            }
        } else {
            return $this->toError(500, '没有对应品牌信息');
        }

        // 如果返回数据有品牌ID，请求品牌接口获取相关信息
        if (count($brandIdList) > 0) {

            // 接口请求参数拼装
            $_params = [
                "code" => 10000,
                "parameter" => [
                    "list" => $brandIdList
                ]
            ];

            // 请求微服务接口获取品牌列表
            $_result = $this->curl->httpRequest($this->Zuul->product, $_params, "POST");

            // 判断结果，并返回
            if ($_result['statusCode'] == 200 && count($_result["content"]["brandList"]) > 0) {
                $data = $_result["content"]["brandList"];
                foreach ($data as $key => $value) {
                    $data[$key]["id"] = $value['id'];
                    $data[$key]["brand_name"] = $value["brandName"];
                }
                return $this->toSuccess($data);
            } else {
                return $this->toError(500, '获取品牌信息失败');
            }
        }
    }

    /**
     * 获取商品列表
     * @param int brandId 品牌ID
     * @return mixed
     *
     */
    public function ProductAction()
    {
        // 判断品牌的ID有效性
        $request = $this->request->get();
        $brandId = isset($request['brandId']) ? $request['brandId'] : 0;

        if ($brandId <= 0) {
            return $this->toError(500, '品牌ID无效');
        }

        $params = [
            "code" => 10002,
            "parameter" => [
                "brandId" => $brandId
            ]
        ];

        // 请求微服务接口获取品牌下对应商品列表
        $result = $this->curl->httpRequest($this->Zuul->product, $params, "POST");

        // 判断结果，并返回
        if ($result['statusCode'] == 200 && count($result["content"]["productList"]) > 0) {
            $data = $result['content']['productList'];
            foreach ($data as $key => $value) {
                $data[$key]['id']           = $value['productId'];
                $data[$key]['product_name'] = $value['productName'];
            }
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '没有对应商品');
        }
    }

    /**
     * 获取规格列表
     * @param int productId 商品ID
     * @return mixed
     */
    public function SkuAction()
    {
        // 判断商品的ID有效性
        $request = $this->request->get();
        $productId = isset($request['productId']) ? $request['productId'] : 0;

        if ($productId <= 0) {
            return $this->toError(500, '商品ID无效');
        }

        $params = [
            "code" => 10004,

            "parameter" => [
                "productId" => $productId
            ]
        ];

        // 请求微服务接口获取对应商品的规格列表
        $result = $this->curl->httpRequest($this->Zuul->product, $params, "POST");

        // 判断结果，并返回
        if ($result['statusCode'] == 200 && count($result["content"]["productSkuRelationList"]) > 0) {
            $data = $result["content"]["productSkuRelationList"];
            foreach ($data as $key => $value) {
                $data[$key]["id"]       = $value['id'];
                $data[$key]["sku_name"] = $value["skuValues"];
            }
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '没有对应规格');
        }
    }

}

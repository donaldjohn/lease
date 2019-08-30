<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: ProductController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace  app\modules\multicodetask;


use app\models\service\MulticodeTask;
use app\modules\BaseController;

class ProductsController extends BaseController
{

    //获取用户目录4 3级目录获取商品和规格 10017 10013
    public function SkucatalogAction()
    {
        $lists = [];
        $categoryId = $this->request->getQuery('categoryId','int',null);
        if (is_null($categoryId))
            return $this->toError(500,'三级目录ID不能为空!');
        $params = [
            'code' => 10017,
            'parameter' => ['categoryId' => $categoryId,'companyId' => $this->authed->insId]
        ];
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        foreach ($result['content']['productList'] as $item) {
            $lists['products'][] = $item;
            $params = [
                'code' => 10004,
                'parameter' => ['productId' => $item['productId'],"companyId" => $this->authed->insId]
            ];
            $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
            if ($result['statusCode'] != '200') {
                return $this->toError($result['statusCode'],$result['msg']);
            }
            foreach ($result['content']['productSkuRelationList'] as $item) {
                $lists['skus'][] = $item;
            }
        }

        return $this->toSuccess($lists);

//        $params = [
//            'code' => 10004,
//            'parameter' => ['categoryId' => $categoryId]
//        ];
//        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
//        if ($result['statusCode'] != '200') {
//            return $this->toError($result['statusCode'],$result['msg']);
//        }
//        foreach ($result['content']['productList'] as $item) {
//            $lists['products'][] = $item;
//        }


    }

    //获取用户目录3
    public function CatalogueAction()
    {

        $params = [
            'code' => 10001,
            'parameter' => ['type' => 1]
        ];
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }

        $lists = [];
        foreach ($result['content']['productCategoryList'] as $item) {
            if ($item['categoryLevel'] == 0) {
                $lists['level1'][] = $item;
            } elseif ($item['categoryLevel'] == 1) {
                $lists['level2'][] = $item;
            } elseif ($item['categoryLevel'] == 2) {
                $lists['level3'][] = $item;
            }
        }

        return $this->toSuccess($lists);
    }

    public function SearchAction(){
        $json = [];
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!is_null($indexText)) {
            $json['indexText'] = $indexText;
        }
        $json['companyId'] = $this->authed->insId;
        $result = $this->userData->common($json,$this->Zuul->product,10018,'productList');
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if (count($result) > 0) {
            foreach($result as $key => $item) {
                $result[$key]['skuValueInfo'] = $this->concat($item['skuValues']);
            }
        }
        return $this->toSuccess($result,$pageInfo);
    }


    private function concat($skuValues)
    {
        $skus = explode('|',$skuValues);
        $values = '';
        if($skus > 1) {
            foreach ($skus as $item ) {
                $value = explode('#',$item);
                $values = $values.$value[1];
            }
        }
        return $values;
    }

}
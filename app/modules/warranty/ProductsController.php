<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\library\ReturnCodeService;
use app\modules\BaseController;

class ProductsController extends BaseController {


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface|void
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 商品列表
     */
    public function ListAction()
    {
        $indexText = $this->request->getQuery('indexText','string',null);
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);
        if (!is_null($indexText)) {
            $parameter['indexText'] = $indexText;
        }
        $parameter['pageNum'] = $pageNum;
        $parameter['pageSize'] = $pageSize;
        //来源业务
        $parameter['bizId'] = 1;
        //调用微服务接口获取数据
        $params = [
            'code' => 10016,
            'parameter' => $parameter
        ];
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            $meta['total'] = $result['content']['pageInfo']['total'];
            $meta['pageNum'] = $parameter['pageNum'];
            $meta['pageSize'] = $parameter['pageSize'];

            $list = [];
            foreach ($result['content']['productSkuCustomerList'] as $key => $value){
                $value['createAt'] = !empty($value['createAt']) ? date('Y-m-d H:i:s',$value['createAt']) : '-';
                $value['gmtCreated'] = !empty($value['gmtCreated']) ? date('Y-m-d H:i:s',$value['gmtCreated']) : '-';
                $list[$key] = $value;
            }
            return $this->toSuccess($list,$meta);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }



    //获取用户目录4 3级目录获取商品和规格 10017 10013
    public function SkucatalogAction()
    {
        $lists = [];
        $categoryId = $this->request->getQuery('categoryId','int',null);
        if (is_null($categoryId))
            return $this->toError(500,'三级目录ID不能为空!');
        $params = [
            'code' => 10040,
            'parameter' => ['categoryId' => $categoryId]
        ];
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        foreach ($result['content']['productList'] as $item) {
            $lists['products'][] = $item;
            $params = [
                'code' => 10004,
                'parameter' => ['productId' => $item['productId']]
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

    //获取商品规格
    public function SkusAction($id)
    {
        $params = [
            'code' => 10015,
            'parameter' => ['productSkuRelationId' => $id]
        ];
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']);
    }


    public function SearchAction(){
        $json = [];
        $customerId = $this->request->getQuery('customerId','int',null);
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!is_null($indexText)) {
            $json['indexText'] = $indexText;
        }
        if (!is_null($customerId)) {
            $json['customerId'] = $customerId;
        }
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


    // 商品导出
    public function LeadOutAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('商品导出:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_OUT_PRODUCTS);
        return $this->toSuccess('http://'.$result['data']['address'],null,200,$result['msg']);
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
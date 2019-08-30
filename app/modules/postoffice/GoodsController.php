<?php
namespace app\modules\postoffice;

use app\common\library\ZuulApiService;
use app\modules\BaseController;
use app\services\data\UserData;
use function foo\func;

//商品模块
class GoodsController extends BaseController
{
    /**
     * 查询商品
     * code：10005
     */
    public function listAction()
    {
        $fields = [
            //商品编号
            'productCode' => 0,
            //商品名称
            'productName' => 0,
            //状态 1启用 2禁用
            'status' => [
                'min' => '1',
                'max' => '2',
            ],
            //商品目录ID
            'categoryId' => 0,
            //商品品牌ID
            'brandId' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (!$parameter){
            return;
        }
        $supplierIdList = $this->userData->getSupplierIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false !== $supplierIdList){
            if (empty($supplierIdList)){
                return $this->toEmptyList();
            }
            $parameter['companyIds'] = $supplierIdList;
        }
        $parameter['insId'] = $this->authed->insId;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->product,[
            'code' => 10005,
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            $tipsTmp = [
                '10028' => '当前机构不存在',
                '10029' => '当前机构不是邮管局或快递协会',
                '10030' => '机构对应的邮管局不存在',
                '10031' => '邮管局下没有快递协会',
                '10032' => '邮管局下存在多个快递协会',
                '10033' => '机构对应的快递协会不存在',
            ];
            $msg = $tipsTmp[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        $meta['total'] = $result['content']['pageInfo']['total'];
        $meta['pageNum'] = $parameter['pageNum'];
        $meta['pageSize'] = $parameter['pageSize'];
        $fields = [
            'productId' => 0,
            'productName' => 0,
            'skuCode' => 0,
            'productCode' => 0,
            'model' => '',
            'skuValues' => [
                'fun' => 'free',
                'func' => function($val){
                    $list = explode('|', $val);
                    foreach ($list as $k => $v){
                        $tmp = explode('#', $v);
                        $list[$k] = isset($tmp[1]) ? $tmp[1] : $v;
                    }
                    return implode('-',$list);
                },
            ],
            'brandName' => '',
            'status' => [
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用'
                ]
            ],
            'categoryName' => '',
            'unit' => '',
            'gmtCreated' => [
                'fun' => 'time'
            ]
        ];

        $list = [];
        foreach ($result['content']['productList'] as $key => $value){
            $list[$key] = $this->backData($fields,$value);
        }
        return $this->toSuccess($list,$meta);
    }

    /**
     * 商品详情
     * code：10006
     */
    public function oneAction($id)
    {
        //根据id查询数据
        $params = [
            "code" => "10006",
            "parameter" => [
                'productId' => (int)$id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $product = $result['content'];
        if (!empty($product['productInfo'])){
            //处理 skuval
            $skuval = $product['productInfo']['skuValues'];
            $list = explode('|', $skuval);
            foreach ($list as $k => $v){
                $tmp = explode('#', $v);
                $list[$k] = isset($tmp[1]) ? $tmp[1] : $v;
            }
            $product['productInfo']['skuValues'] = implode('-',$list);
            // 格式化创建及更新时间
            $product['productInfo']['gmtCreated'] = date('Y-m-d H:i:s',$product['productInfo']['gmtCreated']);
            $product['productInfo']['gmtUpdate'] = date('Y-m-d H:i:s',$product['productInfo']['gmtUpdate']);
        }
        // 处理SKU属性
        foreach ($product['productSkuRelations'] as $key => $val){
            // 处理extAttrValues
            $exts = [];
            if (''!=$val['extAttrValues']){
                $extAttrValues = explode('|', $val['extAttrValues']);
                foreach ($extAttrValues as $k => $v){
                    $tmp = explode('#', $v);
                    $exts[] = [
                        'name' => $tmp[0],
                        'val' => $tmp[1],
                    ];
                }
            }
            $product['productSkuRelations'][$key]['extAttrValuesArr'] = $exts;
            // 处理skuValues
            $skus = [];
            if (''!=$val['skuValues']){
                $skuValues = explode('|', $val['skuValues']);
                foreach ($skuValues as $k => $v){
                    $tmp = explode('#', $v);
                    $skus[] = [
                        'name' => $tmp[0],
                        'val' => $tmp[1],
                    ];
                }
            }
            $product['productSkuRelations'][$key]['skuValuesArr'] = $skus;
            $product['productSkuRelations'][$key]['gmtCreated'] = date('Y-m-d H:i:s', $val['gmtCreated']);
            $product['productSkuRelations'][$key]['gmtUpdate'] = date('Y-m-d H:i:s', $val['gmtUpdate']);
        }
        return $this->toSuccess($product);
    }


    /**
     * 新增商品
     * code：
     */
    public function CreateAction()
    {
    }


    /**
     * 修改商品
     * code：
     */
    public function UpdateAction($id)
    {
    }

    /**
     * 删除商品
     * code：
     */
    public function DeleteAction($id)
    {
        $params = [
            "code" => "",
            "parameter" => [
                'id' => $id,
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        // 调用微服务接口获取数据
//        $result = $this->curl->httpRequest('http://172.16.0.135:10005/apiservice',$params,"post");
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 查询商品目录
     * code：10001
     */
    public function cataloguelistAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $params = [
            "code" => "10001",
            "parameter" => [
                // 查询三级目录
                'categoryLevel' =>2,
            ]
        ];
        $params['parameter']['categoryName'] = isset($request['categoryName']) ? $request['categoryName'] : '';
        //本地测试
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['productCategoryList'];
        foreach ($list as $k => $v){
            $list[$k] = [
                "id" => $v['id'],
                "categoryName" => $v['categoryName'],
            ];
        }
        return $this->toSuccess($list);
    }

    /**
     * 通过商品目录id查询品牌信息
     * code：10003
     */
    public function brandlistAction()
    {
        if (!isset($_GET['categoryId'])){
            return $this->toError(500,'categoryId不能为空');
        }
        $params = [
            "code" => "10003",
            "parameter" => [
                'categoryId' => $_GET['categoryId']
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->product,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['productCategoryBrandRelationList'];
        // 未查到直接返回
        if (0==count($list)){
            return $this->toSuccess($list);
        }
        // 获取品牌id集
        $ids = [];
        foreach ($list as $k => $v){
            $ids[] = [
                'id' => $v['brandId'],
            ];
        }
        $brandparams = [
            "code" => "10000",
            "parameter" => [
                'list' => $ids
            ]
        ];
        //调用微服务接口获取品牌数据
        $brandresult = $this->curl->httpRequest($this->Zuul->product,$brandparams,"post");
        // 异常直接返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $brandList = [];
        foreach ($brandresult['content']['brandList'] as $brand){
            $brandList[] = [
                "brandId" => $brand['id'],
                "brandName" => $brand['brandName'],
            ];
        }
        return $this->toSuccess($brandList);
    }


}

<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\modules\BaseController;

//维修订单
class RepairsController extends BaseController {

    // 维修订单状态
    const REPAIR_STATUS = [
        1 => '待接单',
        2 => '已接单',
        3 => '维修中',
        4 => '待支付',
        5 => '已完成',
        6 => '已取消'
    ];

    /**
     * 获取维修订单列表
     * @return mixed
     */
    public function ListAction()
    {
        $indexText = $this->request->get('indexText', null);
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);

        // 获取维修订单列表
        $params['code'] = 10055;
        if (!empty($indexText)) {
            $params['parameter']['indexText'] = trim($indexText);
        }

        $params['parameter']['pageNum'] = $pageNum;
        $params['parameter']['pageSize'] = $pageSize;

        // 判断结果，并返回数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] == 200 && isset($result['content']['data'][0])) {
            $repairList = $result['content']['data'];
            $data = [];
            foreach ($repairList as $key => $value) {
                $data[$key]['repairSn']         = $value['repairSn'];
                $data[$key]['id']               = $value['id'];
                $data[$key]['vehicleId']        = $value['vehicleId'];
                $data[$key]['productName']      = $value['productName'];
                $data[$key]['malfunctionId']    = $value['malfunctionId'];
                $data[$key]['skuName']          = $value['skuValues'];
                $data[$key]['malfunctionUser']  = $value['malfunctionUser'];
                $data[$key]['repairStatus']     = $value['repairStatus'];
                $data[$key]['repairStatusName'] = self::REPAIR_STATUS[$value['repairStatus']];
                $data[$key]['productImg']       = $value['imgUrl'];
                $data[$key]['totalPay']       = $value['totalPay'];
                $data[$key]['storeName']       = $this->userData->getStoreByInsId($value['storeId'])['storeName'];
                $data[$key]['createAt']       = date('Y-m-d H:i:s', $value['createAt']);
                $data[$key]['updateAt']       = date('Y-m-d H:i:s', $value['updateAt']);
                // 来源客户信息
                $data[$key]['customerName']    = $value['customerName'];
                $data[$key]['customerId']       = 1;
            }

            return $this->toSuccess($data, $result['content']['pageInfo']);
        } else {
            $data = [];
            $pageInfo = [];
            $pageInfo['total'] = 0;
            $pageInfo['pageNum'] = 1;
            $pageInfo['pageSize'] = 10;
            return $this->toSuccess($data, $pageInfo);
        }
    }

    /**
     * 获取维修订单详情
     * @return mixed
     */
    public function OneAction($id = 0)
    {
        // 请求参数有效性检测
        if ($id == 0) {
            return $this->toError(500, '订单ID不能为空');
        }

        // 根据维修订单ID查询订单详情
        $params = [
            'code' => 10058,
            'parameter' => [
                'id' => $id
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data']['id'])) {
            $repairInfo = $result['content']['data'];
            $store = $this->userData->getStoreByInsId($repairInfo['storeId']);
            // 整理订单详情基础数据
            $data = [];
            $data['order']['repairSn'] = $repairInfo['repairSn'];
            $data['order']['createAt'] = $repairInfo['createAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['createAt']) : 0;
            $data['order']['updateAt'] = $repairInfo['updateAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['updateAt']) : 0;
            $data['order']['receiveAt'] = $repairInfo['receiveAt'] >0 ? date('Y-m-d H:i:s', $repairInfo['receiveAt']) : 0;
            $data['order']['repairStartAt'] = $repairInfo['repairStartAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['repairStartAt']) : 0;
            $data['order']['repairEndAt'] = $repairInfo['repairEndAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['repairEndAt']) : 0;
            $data['order']['payAt'] = $repairInfo['payAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['payAt']) : 0;
            $data['order']['effectAt'] = date('Y-m-d H:i:s', $repairInfo['createAt']);
            $data['order']['customerId'] = $repairInfo['customerId'];
            $data['order']['customerName'] = isset($repairInfo['customerName']) ? $repairInfo['customerName'] : '';
            $data['order']['productName'] = isset($repairInfo['productName']) ? $repairInfo['productName'] : '';
            $data['order']['skuValues'] = isset($repairInfo['skuValues']) ? $repairInfo['skuValues'] : '';
            $data['order']['storeName'] = $store['storeName'];
            $data['order']['linkMan'] = $store['linkMan'];
            $data['order']['linkPhone'] = $store['linkPhone'];
            $province = $store['provinceId'] ? $this->userData->getRegionName($store['provinceId'])['areaName'] : '';
            $city = $store['cityId'] ? $this->userData->getRegionName($store['cityId'])['areaName'] : '';
            $areaId = $store['areaId'] ? $this->userData->getRegionName($store['areaId'])['areaName'] : '';
            $data['order']['address'] = $province.$city.$areaId.$store['address'];
            $data['order']['repairStatus'] = $repairInfo['repairStatus'];
            $data['order']['warrantyType'] = 1;
            $data['order']['malfunctionUser'] = $repairInfo['malfunctionUser'];
            $data['order']['userSimpleName'] = $repairInfo['malfunctionUser'];
            $data['order']['vehicleId'] = $repairInfo['vehicleId'];
            $data['order']['vehicleVin'] = 'LZSNJCT08H8055856';
            $data['order']['vehicleInfo'] = $repairInfo['productName'].'-'.$repairInfo['skuValues'];
            $data['order']['malfunctionMessage'] = $repairInfo['failureMessage'];
            foreach ($repairInfo['orderDetail'] as $key => $value) {
                $repairInfo['orderDetail'][$key]['createAt'] = date('Y-m-d H:i:s', $repairInfo['orderDetail'][$key]['createAt']);
            }
            $data['orderDetail'] = $repairInfo['orderDetail'];
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '获取详情失败');
        }
    }

    /**
     * 获取维修订单详情
     * @return mixed
     */
    public function DetailAction()
    {
        $repairSn = $this->request->getQuery("repairSn","string",null);
        if ($repairSn == null) {
            return $this->toError(500, '维修订单编号不能为空！');
        }

        // 根据维修订单ID查询订单详情
        $params = [
            'code' => 10058,
            'parameter' => [
                'repairSn' => $repairSn
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data']['id'])) {
            $repairInfo = $result['content']['data'];
            $store = $this->userData->getStoreByInsId($repairInfo['storeId']);
            // 整理订单详情基础数据
            $data = [];
            $data['order']['repairSn'] = $repairInfo['repairSn'];
            $data['order']['createAt'] = $repairInfo['createAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['createAt']) : 0;
            $data['order']['updateAt'] = $repairInfo['updateAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['updateAt']) : 0;
            $data['order']['receiveAt'] = $repairInfo['receiveAt'] >0 ? date('Y-m-d H:i:s', $repairInfo['receiveAt']) : 0;
            $data['order']['repairStartAt'] = $repairInfo['repairStartAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['repairStartAt']) : 0;
            $data['order']['repairEndAt'] = $repairInfo['repairEndAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['repairEndAt']) : 0;
            $data['order']['payAt'] = $repairInfo['payAt'] > 0 ? date('Y-m-d H:i:s', $repairInfo['payAt']) : 0;
            $data['order']['effectAt'] = date('Y-m-d H:i:s', $repairInfo['createAt']);
            $data['order']['customerId'] = $repairInfo['customerId'];
            $data['order']['customerName'] = isset($repairInfo['customerName']) ? $repairInfo['customerName'] : '';
            $data['order']['productName'] = isset($repairInfo['productName']) ? $repairInfo['productName'] : '';
            $data['order']['skuValues'] = isset($repairInfo['skuValues']) ? $repairInfo['skuValues'] : '';
            $data['order']['storeName'] = $store['storeName'];
            $data['order']['linkMan'] = $store['linkMan'];
            $data['order']['linkPhone'] = $store['linkPhone'];
            $province = $store['provinceId'] ? $this->userData->getRegionName($store['provinceId'])['areaName'] : '';
            $city = $store['cityId'] ? $this->userData->getRegionName($store['cityId'])['areaName'] : '';
            $areaId = $store['areaId'] ? $this->userData->getRegionName($store['areaId'])['areaName'] : '';
            $data['order']['address'] = $province.$city.$areaId.$store['address'];
            $data['order']['repairStatus'] = $repairInfo['repairStatus'];
            $data['order']['warrantyType'] = 1;
            $data['order']['malfunctionUser'] = $repairInfo['malfunctionUser'];
            $data['order']['userSimpleName'] = $repairInfo['malfunctionUser'];
            $data['order']['vehicleId'] = $repairInfo['vehicleId'];
            $data['order']['vehicleVin'] = 'LZSNJCT08H8055856';
            $data['order']['vehicleInfo'] = $repairInfo['productName'].'-'.$repairInfo['skuValues'];
            $data['order']['malfunctionMessage'] = $repairInfo['failureMessage'];
            foreach ($repairInfo['orderDetail'] as $key => $value) {
                $repairInfo['orderDetail'][$key]['createAt'] = date('Y-m-d H:i:s', $repairInfo['orderDetail'][$key]['createAt']);
            }
            $data['orderDetail'] = $repairInfo['orderDetail'];
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '获取详情失败');
        }
    }


    /**
     * 获取门店详情
     */
    public function getStoreInfo($storeId)
    {

    }
}
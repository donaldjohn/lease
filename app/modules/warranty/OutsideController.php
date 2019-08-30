<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: OutsideController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\library\ReturnCodeService;


class OutsideController extends XController {

    // 维修订单状态
    const REPAIR_START  = 1; // 订单提交
    const REPAIR_ACCEPT = 2; // 订单接单
    const REPAIR_DOING  = 3; // 订单维修中
    const REPAIR_PAY    = 4; // 订单支付
    const REPAIR_END    = 5; // 订单完成
    const REPAIR_CANCEL = 6; // 订单取消

    //新增联保订单(根据联保订单方案)
    //return 编号 状态
    /**
     *
     * effectAt warrantyType
     */
    public function orderAction()
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['vehicleId'])) {
            return $this->toError(500,'车辆编号必填!');
        }
        if (!isset($json['vehicleVin'])) {
            return $this->toError(500,'车架号必填!');
        }
        if (!isset($json['bizCode'])) {
            return $this->toError(500,'业务代码必填!');
        }
        if (!isset($json['reductionTime'])) {
            return $this->toError(500,'减扣时间必填!');
        }

        if (!isset($json['effectTime'])) {
            return $this->toError(500,'生效时间必填!');
        } else {
            $result = $this->warrantyData->is_timestamp($json['effectTime']);
            if ($result == false) {
                return $this->toError(500,'生效时间格式不规范!');
            }
        }

        //根据客户ID和业务代码查询联保方案
        $customerId = $this->getCustomerId();
        $params = [];
        $params['customerId'] = $customerId;
        $params['bizCode'] = $json['bizCode'];
        $this->logger->info('查询联保方案:'.json_encode($json));
        $result = $this->userData->common($params,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_ORDERS_SCHEMES_ID);
        if (empty($result['data'])) {
            return $this->toError(500,'联保方案不存在!');
        }
        //联保方案详情
        $warrantySchemes = $result['data'];

        if ($warrantySchemes['warrantyStatus'] != 1) {
            return $this->toError(500,'当前联保方案已禁用!');
        }

        //定义合法的联保订单
        $postData = [];
        $postData['customerId'] = $customerId;
        $postData['effectAt'] = $json['effectTime'];
        $postData['warrantyType'] = $warrantySchemes['warrantyType'];
        $postData['vehicleId'] = $json['vehicleId'];
        $postData['vehicleSku'] =  $warrantySchemes['vehicleSku'];
        $postData['vehicleVin'] = $json['vehicleVin'];
        //$postData['warrantyStatus'] = 2; //生效中


        //绑定$orderDetail
        $orderDetail = [];
        if (isset($warrantySchemes['schemeDetail'])) {
            foreach ($warrantySchemes['schemeDetail'] as $item ) {
                $list = [];
                $list['bomElementId'] = $item['bomElementId'];
                $time = $item['elementExp']-$json['reductionTime'];
                if ($time > 0) {
                    $list['elementExp'] = $time;
                } else {
                    $list['elementExp'] = 0;
                }
                $orderDetail[] = $list;
            }
        }
        $postData['orderDetail'] = $orderDetail;

        //绑定$orderArea
        $orderArea = [];
        if (isset($warrantySchemes['schemeAreas'])) {
            foreach ($warrantySchemes['schemeAreas'] as $item ) {
                $list = [];
                $list['fitAreaId'] = $item['fitAreaId'];
                $orderArea[] = $list;
            }
        }
        $postData['orderArea'] = $orderArea;

        $this->logger->info('第三方新增联保订单:'.json_encode($postData));
        $result = $this->userData->common($postData,$this->Zuul->biz,ReturnCodeService::WARRANTY_CREATE_ORDERS);

        $json = [];
        $json['idList'][] = $result['data']['id'];
        $json['status'] = 2;
        $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_STATUS_ORDERS);
        $result['data']['warrantyStatus'] = 2;
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }


    /**
     * 更新联保订单方案
     * 根据订单编号
     */
    public function orderStatusAction()
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['warrantySn'])) {
            return $this->toError(500,'订单编号必填!');
        }
        $params = [];
        $params['customerId'] = $this->getCustomerId();
        $params['warrantySn'] = $json['warrantySn'];
        $this->logger->info('查询联保订单:'.json_encode($params));
        $resultOrder = $this->userData->common($params,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_ORDERS_ID);
        $resultOrder = $resultOrder['data'];
        if (empty($resultOrder)) {
            return $this->toError(500,'订单不存在!');
        }
        $json = [];
        $json['idList'][] = $resultOrder['id'];
        $json['status'] = 3;
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_STATUS_ORDERS);
        return $this->toSuccess(true,null,200,$result['msg']);



    }

//    //新增商品
//    public function productsAction()
//    {
//        // 10010 新增商品
//        // 增加关系
//        $json = $this->request->getJsonRawBody(true);
//        $this->logger->info('新增商品:'.json_encode($json));
//        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_CREATE_PRODUCT);
//        foreach ($result['data']['productSkuRelationIds'] as $item) {
//            $parameter = ['bizId'=> 1,'customerId'=> $this->getCustomerId(),'skuId' => $item];
//            $result = $this->userData->postCommon($parameter,$this->Zuul->product,ReturnCodeService::WARRANTY_CREATE_CUSTOMER_RELATION);
//        }
//        return $this->toSuccess(null,null,200,$result['msg']);
//    }
//
//    public function ProductRelationAction()
//    {
//        $json = $this->request->getJsonRawBody(true);
//        if (!isset($json['skuId']))
//            return $this->toError(500, '缺少skuId参数');
//        $parameter = ['bizId' => 1, 'customerId' => $this->getCustomerId(), 'skuId' => $json['skuId']];
//        $result = $this->userData->postCommon($parameter, $this->Zuul->product, ReturnCodeService::WARRANTY_CREATE_CUSTOMER_RELATION);
//        return $this->toSuccess(null, null, 200, $result['msg']);
//    }


    /**
     * 新增维修订单
     * @param string malfunctionId 报障单号
     * @param string vehicleId 车辆编号
     * @param string malfunctionUser 报障人
     * @param string malfunctionMessage 报障信息
     * @param string userPhone 用户电话
     * @param string storeId 门店ID
     * @return mixed
     */
    public function RepairAction()
    {
        // 获取参数
        $request = $this->request->getJsonRawBody();
        $malfunctionId = isset($request->malfunctionId) ? $request->malfunctionId : null;
        $vehicleId = isset($request->vehicleId) ? $request->vehicleId : null;
        $malfunctionUser = isset($request->malfunctionUser) ? $request->malfunctionUser : null;
        $malfunctionMessage = isset($request->malfunctionMessage) ? $request->malfunctionMessage : null;
        $userPhone = isset($request->userPhone) ? $request->userPhone : null;
        $storeId = isset($request->storeId) ? $request->storeId : null;

        if (empty($vehicleId)) {
            return $this->toError(500, '车辆编号不能为空');
        }

        if (empty($malfunctionUser)) {
            return $this->toError(500, '报障用户不能为空');
        }

        if (empty($malfunctionMessage)) {
            return $this->toError(500, '报障信息不能为空');
        }

        if (empty($userPhone)) {
            return $this->toError(500, '报障人联系电话不能为空');
        }

        if (empty($storeId)) {
            return $this->toError(500, '门店ID不能为空');
        }

        // 判断车辆联保信息是否有效，并获取车辆的vehicleSku信息
        $params = [
            'code' => 11024,
            'parameter' => [
                'customerId' => $this->getCustomerId(),
                'vehicleId' => $vehicleId,
                'warrantyStatus' => 2, // 联保生效状态
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] != 200 || !isset($result['content']['data'][0])) {
            return $this->toError(500, "车辆没有有效的联保信息，无法进行维修");
        }

        $vehicleSku = $result['content']['data'][0]['vehicleSku'];

        // 调用微服务新增维修订单
        $params = [
            'code' => 10049,
            'parameter' => [
                'malfunctionId'     => $malfunctionId,
                'customerId' => $this->getCustomerId(),
                'vehicleId' => $vehicleId,
                'malfunctionUser' => $malfunctionUser,
                'failureMessage' => $malfunctionMessage,
                'userPhone' => $userPhone,
                'storeId' => $storeId,
                'vehicleSku' => $vehicleSku
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data'])) {
            $info = $result['content']['data'];
            $data = [];
            $data['repairSn'] = $info['repairSn'];
            $data['repairStatus'] = $info['repairStatus'];
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, $result['msg']);
        }

    }

    /**
     * 获取维修订单详情
     * @param string repairSn 维修订单编号
     * @return mixed
     */
    public function RepairInfoAction()
    {
        $request = $this->request->get();
        $repairSn = isset($request['repairSn']) ? $request['repairSn'] : null;

        if (empty($repairSn)) {
            return $this->toError(500, '维修编号不能为空');
        }

        // 获取维修订单详情
        $result = $this->getRepairContent($repairSn);

        if (!$result['status']) {
            return $this->toError(500, $result['msg']);
        }

        $info = $result['msg'];
        if ($info['customerId'] != $this->getCustomerId()) {
            return $this->toError(500, '无权限获取该数据');
        }

        // 数据封装
        $data = [];
        $data['vehicleId'] = $info['vehicleId'];
        $data['skuValues'] = $info['skuValues'];
        $data['failureMessage'] = $info['failureMessage'];
        if (count($info['orderDetail']) > 0) {
            foreach ($info['orderDetail'] as $key => $value) {
                $bom = [];
                $bom['productName'] = $value['productName'];
                $bom['repairType'] = $value['repairType'];
                $bom['fittingCost'] = $value['fittingCost'];
                $bom['timeCost'] = $value['timeCost'];
                $bom['payer'] = $value['payer'];
                $data['orderDetail'][$key] = $bom;
            }
        } else {
            $data['orderDetail'] = [];
        }
        return $this->toSuccess($data);
    }

    /**
     * 维修订单状态修改
     * @param string repairSn 维修订单编号
     * @return mixed
     */
    public function RepairStatusAction()
    {
        $request = $this->request->getJsonRawBody();
        $repairSn = isset($request->repairSn) ? $request->repairSn : null;

        if (empty($repairSn)) {
            return $this->toError(500, '维修单号不能为空');
        }

        // 获取维修订单详情
        $result = $this->getRepairContent($repairSn);

        if (!$result['status']) {
            return $this->toError(500, $result['msg']);
        }

        $info = $result['msg'];
        if ($info['customerId'] != $this->getCustomerId()) {
            return $this->toError(500, '无权限修改该订单');
        }

        if ($info['repairStatus'] > 2) {
            return $this->toError(500, '订单无法被取消');
        }

        // 修改维修订单状态
        $params = [
            'code' => 10054,
            'parameter' => [
                'repairSn'     => $repairSn,
                'repairStatus' => self::REPAIR_CANCEL
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200) {
            // 取消订单后推送状态到租赁方
            $object = new MicroprogramsController();
            $object->changeOrderInfo($info['id']);

            return $this->toSuccess($result['msg']);
        } else {
            return $this->toError(500, $result['msg']);
        }
    }

    /**
     * 辅助方法：获取订单详情
     * @param string $repairSn 维修订单编号
     * @return mixed
     */
    public function getRepairContent($repairSn = null)
    {
        if (empty($repairSn)) {
            return ['status' => false, 'msg' => '维修订单编号不能为空'];
        }

        $params = [
            'code' => 10058,
            'parameter' => [
                'repairSn' => $repairSn
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data'])) {
            return ['status' => true, 'msg' => $result['content']['data']];
        } else {
            return ['status' => false, 'msg' => $result['msg']];
        }
    }

}
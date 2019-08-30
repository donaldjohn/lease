<?php
namespace app\modules\rent;

use app\models\order\ServiceContract;
use app\models\order\VehicleRentOrder;
use app\models\service\StoreVehicle;
use app\modules\BaseController;
use app\services\data\DriverData;

//服务单模块
class ServiceorderController extends BaseController
{
    /**
     * 服务单列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            // 服务单号
            'serviceSn' => 0,
            // 状态 1:未支付,2:存续，3:关闭未结算，4:结算未退款，5:已结束
            'status' => 0,
            // 骑手姓名
            'driverName' => '',
            // 骑手联系电话
            'phone' => '',
            // 订单创建开始时间
            'startTime' => '',
            // 订单创建结束时间
            'endTime' => '',
            // 套餐名称
            'productPackageName' => '',
            // 骑手ID
            'driverId' => 0,
            // 页码
            'pageNum' => [
                'def' => 1,
            ],
            // 页大小
            'pageSize' => [
                'def' => 20,
            ],
            'operatorInsId' => [
                'def' => null,
            ],
            'parentOperatorInsId' => [
                'def' => null,
            ]
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        if ($this->authed->userType == 9) {
            $parameter['operatorInsId'] = $this->authed->insId;
        } else if ($this->authed->userType == 11) {
            $parameter['parentOperatorInsId'] = $this->authed->insId;
        }
        if (false === $parameter){
            return;
        }
        // 查询服务单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10032",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : ['pageNum' => (int)$parameter['pageNum'], 'pageSize' => (int)$parameter['pageSize'], 'total' => 0];
        // 套餐Data对象
        $PackageData = $this->PackageData;
        // 获取骑手idlist
        $driverIds = [];
        foreach ($list as $k => $item) {
            $list[$k]['canBreakUp'] = false;
            // 套餐存续中 && 没有租赁服务 允许解除合约
            if (in_array($item['status'], [ServiceContract::STATUS_PAID_UNBIND, ServiceContract::STATUS_USING])){
                $list[$k]['canBreakUp'] = true;
            }
            $driverIds[] = $item['driverId'];
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 解除服务
    public function RescindContractAction($serviceContractId)
    {
        // 服务单
        $SC = ServiceContract::arrFindFirst([
            'id' => $serviceContractId
        ]);
        if (false == $SC){
            return $this->toError(500, '未查到服务单信息');
        }
        if (!in_array($SC->status, [ServiceContract::STATUS_PAID_UNBIND, ServiceContract::STATUS_USING])){
            return $this->toError(500, '仅【待生效/生效中】可强制解除');
        }
        // 查询车辆绑定关系
        $SV = StoreVehicle::arrFindFirst([
            'service_contract_id' => $serviceContractId
        ]);
        $nowTime = time();
        if ($SV && 0 != $SV->driver_id){
            // 租车单
            $VRO = VehicleRentOrder::arrFindFirst([
                'service_contract_id' => $serviceContractId
            ]);
            $SV->update([
                'rent_status' => StoreVehicle::UN_RENT,
                'driver_id' => 0,
                'service_contract_id' => 0,
                'bind_time' => 0,
                'update_time' => $nowTime,
                'ready_rent_time' => 0,
            ]);
            if ($VRO){
                $VRO->update([
                    'on_operation' => 2,
                    'update_time' => $nowTime,
                    'is_returned' => 2,
                ]);
            }
        }
        // 更新服务单
        $SC->update([
            'status' => ServiceContract::STATUS_FINISHED,
            'end_time' => time(),
            'update_time' => time(),
        ]);
        return $this->toSuccess();
    }

    /**
     * TODO:废弃查看服务单详情
     */
    /*public function OneAction($sn)
    {
        // 查询服务单详情
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10041",
            'parameter' => [
                'serviceSn' => $sn,
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return json_encode($result);
    }*/


    /**
     * 【废弃】后台结算骑手服务单
     */
    /*public function ClosureAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['serviceSn'])){
            return $this->toError(500,'未收到服务单号');
        }
        // 查询服务单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10032",
            'parameter' => [
                'serviceSn' => $request['serviceSn']
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || '200'!=$result['statusCode'] || !isset($result['content']['data'][0])) {
            return $this->toError(500, '服务异常，未查询到服务单');
        }
        $service = $result['content']['data'][0];
        $serviceId = $service['serviceId'];
        $driverId = $service['driverId'];
        // 套餐Data对象
        $PackageData = $this->PackageData;
        if ($PackageData->isContainServiceByPackageId($PackageData->RentServiceType, $service['packageId'])){
            return $this->toError(500, '不可通过后台结算包含租赁的服务单');
        }
        // 非存续状态不提供操作
        if (2!=$service['status']){
            return $this->toError(500, '当前服务单状态不可通过后台结算');
        }
        // 关闭服务单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10045",
            'parameter' => [
                'serviceContractId' => $serviceId,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'系统异常，服务单关闭失败');
        }
        // 查询是否有待支付的支付单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10019",
            'parameter' => [
                'serviceContractId' => $serviceId,
                'status' => 1,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'系统异常，支付单信息查询失败');
        }
        // 如果当前服务单下有待支付账单，返回
        if (isset($result['content']['data']) && count($result['content']['data'])>0){
            return $this->toError(500,'当前服务单有待支付账单，无法结算');
        }
        // 发起结算，生成支付单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10031",
            'parameter' => [
                'serviceContractId' => $serviceId,
                'driverId' => $driverId,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'系统异常，支付单生成失败');
        }
        // 如果有结算账单
        if (null !== $result['content']['businessSn']){
            return $this->toSuccess([], [], 200, '结算成功');
        }
        // 无需结算 生成退款单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10030",
            'parameter' => [
                'serviceContractId' => $serviceId,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'系统异常，退款处理失败');
        }
        // 返回提示
        return $this->toSuccess([], [], 200, '无需结算，已为客户发起退款申请');
    }*/

}

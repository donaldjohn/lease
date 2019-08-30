<?php
namespace app\modules\driversapp;

use app\common\errors\AppException;
use app\models\service\StoreVehicle;
use app\models\service\Vehicle;
use app\models\service\VehicleLockRecord;
use app\models\service\VehicleLockScenes;
use app\modules\BaseController;
use app\services\data\RentWarrantyData;
use app\services\data\RentRepairData;
use app\services\data\DriverData;
use app\services\data\VehicleData;
use app\services\data\ServiceContractData;
use app\services\data\StoreData;
use Phalcon\Logger;
use app\models\order\VehicleWarrantyOrder;
use app\models\order\ServiceContract;
use app\models\order\VehicleRepairOrder;
use app\models\product\ProductSkuRelation;
use app\services\data\MessagePushData;
use app\services\data\BillData;
use app\services\data\CabinetData;

/**
 * 骑手操作
 * Class OperateController
 * @package app\modules\driversapp
 */
class OperateController extends BaseController
{
    /**
     * 骑手APP发起维修申请
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     */
    public function CreaterepairAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        // 查询车辆信息
        $vehicle = (new VehicleData())->getVehicleById($request['vehicleId']);
        // 查询有效联保订单
        $WarrantyOrder = VehicleWarrantyOrder::findFirst([
            'driver_id = :driver_id: and vehicle_id = :vehicle_id: and order_status=2',
            'bind' => ['driver_id'=>$driverId, 'vehicle_id'=>$request['vehicleId']],
            'order' => 'create_at DESC'
        ]);
        if (false===$WarrantyOrder){
            return $this->toError(500, '用户车辆没有有效的联保订单，不可申请维修');
        }
        if (0!=$WarrantyOrder->end_at && time()>$WarrantyOrder->end_at){
            return $this->toError(500, '您的联保服务已过期，不可申请');
        }
        // 如果联保未更新截止时间，则查询租车单
        if (0==$WarrantyOrder->end_at){
            // 查询租赁起止时间
            $RentStartEndTime = (new ServiceContractData())->getRentStartEndTimeByServiceId($WarrantyOrder->service_contract_id);
            // 如果没有租赁 || 租赁过期
            if (false===$RentStartEndTime || time()>$RentStartEndTime['endTime']){
                return $this->toError(500, '您的联保已过期，不可申请');
            }
        }
        $RentRepair = new RentRepairData();
        // 判断是否有未完结维修单
        if ($RentRepair->getUnfinishedRepairByVehicleId($vehicle['id'])){
            return $this->toError(500, '当前车辆有未完成的维修订单，不可重复申请');
        }
        // 向联保方发起维修单
        $res = $RentRepair->CreateRepairOrderToWarranty($vehicle['bianhao'], $request['storeId'], $request['linkName'], $request['linkPhone'], $request['falutInfo'], $vehicle['id']);
        if (false===$res){
            return $this->toError(500, '联保服务异常');
        }
        // 内部保存维修单
        $repair = $RentRepair->CreateRepairOrder($vehicle['id'], $driverId, $WarrantyOrder->id, $request['storeId']);
        if (false===$repair){
            return $this->toError(500, '生成维修单据失败');
        }
        // 记录发起维修日志
        $this->busLogger->recordingOperateLog("【骑手发起维修】id:{$driverId} 姓名:{$this->authed->userName} 车辆编号:{$vehicle['bianhao']}", '发起维修');
        return $this->toSuccess();
   }

    /**
     * 骑手APP取消维修单
     * @param $id 维修单id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function CancelrepairAction($id)
    {
        $driverId = $this->authed->userId;
        $RentRepair = new RentRepairData();
        // 获取维修单信息
        $RepairOrder = $RentRepair->getRepairOrderById($id);
        if (false===$RepairOrder){
            return $this->toError(500, '未查到维修订单信息');
        }
        // 判断是否是骑手本人
        if ($RepairOrder->driver_id != $driverId){
            return $this->toError(500, '订单身份验证失败');
        }
        // 维修状态1待接单2已接单3维修中4待支付5已完成 6已取消
        if (!in_array($RepairOrder->repair_status, [1,2])){
            return $this->toError(500, '当前维修订单不在可取消状态');
        }
        // 获取取消维修单接口配置
        $cancelRepair = $this->config->interface->warranty->cancelRepair;
        // 调用联保接口取消维修单
        $res = $this->curl->sendCurl($this->config->baseUrl.$cancelRepair->uri, [
            'repairSn' => $RepairOrder->repair_sn,
        ], $cancelRepair->method,['secretKey' => $this->config->interface->warranty->secretKey]);
        // 异常抛出
        if (200!=$res['statusCode']){
            return $this->toError(500, '维修服务取消失败');
        }
        // 修改自有数据状态
        $RepairOrder->repair_status = 6;
        $bol = $RepairOrder->save();
        if (false===$bol){
            return $this->toError(500, '单据更新失败');
        }
        // 记录取消维修日志
        $this->busLogger->recordingOperateLog("【骑手取消维修】id:{$driverId} 姓名:{$this->authed->userName} 维修单号:{$RepairOrder->repair_sn}", '取消维修');
        return $this->toSuccess();
    }

    /**
     * 查询维修单详情
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     */
    public function RepairinfoAction($id)
    {
        $driverId = $this->authed->userId;
        $RentRepair = new RentRepairData();
        // 获取维修单信息
        $RepairOrder = $RentRepair->getRepairOrderById($id);
        if (false===$RepairOrder){
            return $this->toError(500, '未查到维修订单信息');
        }
        // 判断是否是骑手本人
        if ($RepairOrder->driver_id != $driverId){
            return $this->toError(500, '订单身份验证失败');
        }
        // 查询联保订单
        $WarrantyOrder = (new RentWarrantyData())->getWarrantyOrderById($RepairOrder->warranty_id);
        if (false===$WarrantyOrder){
            return $this->toError(500, '联保订单查询失败');
        }
        // 查询服务单号
        $ServiceOrder = ServiceContract::getServiceOrderById($WarrantyOrder->service_contract_id);
        if (false===$ServiceOrder){
            return $this->toError(500, '服务单查询失败');
        }
        $data = $RepairOrder->toArray();
        // 向联保方获取维修详情
        $info = (new RentRepairData())->getRepairInfoByRepairSn($RepairOrder->repair_sn);
        $data['serviceSn'] = $ServiceOrder->service_sn;
        $data['vehicleBianhao'] = $info['vehicleId'];
        $data['model'] = $info['skuValues'];
        $data['repairItem'] = $info['repairItem'];
        return $this->toSuccess($data);
    }

    /**
     * 骑手维修单列表
     */
    public function RepairlistAction()
    {
        $driverId = $this->authed->userId;
        $queryArr = [
            'conditions' => 'driver_id = ?1',
            'bind'       => [
                1 => $driverId,
            ],
            'order' => 'id DESC',
        ];
        // 总条数
        $count = VehicleRepairOrder::count($queryArr);
        // 分页
        $limit = isset($_GET['pageSize']) ? $_GET['pageSize'] : 20;
        $pageNum = isset($_GET['pageNum']) ? $_GET['pageNum'] : 1;
        $queryArr['limit'] = $limit;
        $queryArr['offset'] = ($pageNum-1)*$limit;
        // 查询
        $list = VehicleRepairOrder::find($queryArr);
        $meta = [
            'total' => $count,
            'pageSize' => $limit,
            'pageNum' => $pageNum,
        ];
        // 查询车辆信息
        $vehicleIDs = [];
        foreach ($list as $item){
            $vehicleIDs[] = $item->vehicle_id;
        }
        $list = $list->toArray();
        $vehicles = (new VehicleData())->getVehicleByIds($vehicleIDs);
        // 查询车辆图片信息
        $vehicleSKUIds = [];
        $vehicleBianhaos = [];
        foreach ($vehicles as $vehicle){
            $vehicleSKUIds[(string)$vehicle['id']] = $vehicle['productSkuRelationId'];
            $vehicleBianhaos[(string)$vehicle['id']] = $vehicle['bianhao'];
        }
        $skusRes = [];
        if (count($vehicleSKUIds)>0){
            $skusRes = ProductSkuRelation::find([
                'id IN ({id:array})',
                'bind' => [
                    'id' => array_values(array_unique($vehicleSKUIds)),
                ]
            ]);
        }
        $skus = [];
        foreach ($skusRes as $sku){
            $skus[(string)$sku->id] = $sku->toArray();
        }
        // 处理图片
        foreach ($list as $k => $item){
            if (isset($vehicleBianhaos[$item['vehicle_id']])){
                $list[$k]['vehicleBianhao'] = $vehicleBianhaos[$item['vehicle_id']];
            }
            if (isset($vehicleSKUIds[$item['vehicle_id']]) && isset($skus[$vehicleSKUIds[$item['vehicle_id']]])){
                $list[$k]['imgUrl'] = $skus[$vehicleSKUIds[$item['vehicle_id']]]['img_url'];
            }
        }
        return $this->toSuccess($list, $meta);
    }

    /**
     * 为换电单生成支付单
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function ChargingpaybillAction()
    {
        // 获取骑手ID
        $driverId = $this->authed->userId;
        // 查询骑手待支付账单
        $bill = (new BillData())->getUnpaidBillByDriverId($driverId);
        // 如果骑手有待支付账单，返回
        if ($bill){
            return $this->toError(500,'您有尚未付款的支付单，请前往我的支付单处理');
        }
        $parameter['driverId'] = $driverId;
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        // 获取充换电单生成列表
        if (isset($request['idList']) || !empty($request['idList'])){
            $parameter['idList'] = $request['idList'];
        }
        // 调用微服务生成支付单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10044",
            'parameter' => $parameter,
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常，生成支付失败');
        }
        return $this->toSuccess([
            'businessSn' => $result['content']['data']['businessSn'],
        ]);
    }

    // 查询骑手是否有未支付的换电单
    public function UnpaidserviceAction()
    {
        // 获取骑手ID
        $driverId = $this->authed->userId;
        // 查询是否有未支付的换电单 返回待支付换电单号
        $ChargingOrders = (new CabinetData())->getUnpaidChargingOrdersBydriverId($driverId);
        // TODO: 待扩展为查询所有服务待付，分对象返回count
        $data['status'] = 1;
        if ($ChargingOrders){
            $data['status'] = 2;
        }
        return $this->toSuccess($data);
    }

    // 骑手锁车
    public function LockAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? false;
        if (!$vehicleId){
            return $this->toError(500, '参数错误');
        }
        $driverId = $this->authed->userId;
        // 查询关联门店车辆
        $SV = StoreVehicle::arrFindFirst([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
        ]);
        // 没有车辆，返回null
        if (false===$SV){
            return $this->toError(500, '车辆不属于骑手，无权操作！');
        }
        $driverData = new DriverData();
        // 查询是否有有效租车单(逾期)
        $CurrentVRO = $driverData->getCurrentVehicleRentOrderByDriverId($driverId, $vehicleId);
        // 无有效租车单
        if (!$CurrentVRO){
            return $this->toError(500, '您暂无权限锁定当前车辆，请联系店长');
        }
        // 查询骑手当前优先级是否可以锁车
        $levelCheck = $driverData->checkDriverLockVehicleLevel($vehicleId, $driverId);
        if (false==$levelCheck){
            return $this->toError(500, '您暂无权限锁定当前车辆，请联系店长');
        }
        // 锁车
        $driverData->LockVehicleOfDriver($vehicleId, $driverId);
        return $this->toSuccess();
    }

    // 骑手解锁车辆
    public function UnLockAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? false;
        if (!$vehicleId){
            return $this->toError(500, '参数错误');
        }
        $driverId = $this->authed->userId;
        // 查询关联门店车辆
        $SV = StoreVehicle::arrFindFirst([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
        ]);
        // 没有车辆，返回null
        if (false===$SV){
            return $this->toError(500, '车辆不属于骑手，无权操作！');
        }
        $driverData = new DriverData();
        // 查询是否有有效租车单(逾期)
        $CurrentVRO = $driverData->getCurrentVehicleRentOrderByDriverId($driverId, $vehicleId);
        // 无有效租车单
        if (!$CurrentVRO){
            return $this->toError(500, '您暂无权限解锁当前车辆，请联系店长');
        }
        // 查询骑手当前优先级是否可以解锁
        $levelCheck = $driverData->checkDriverLockVehicleLevel($vehicleId, $driverId);
        if (false==$levelCheck){
            return $this->toError(500, '您暂无权限解锁当前车辆，请联系店长');
        }
        // 解锁车辆
        $driverData->UnLockVehicleOfDriver($vehicleId, $driverId);
        return $this->toSuccess();
    }
}

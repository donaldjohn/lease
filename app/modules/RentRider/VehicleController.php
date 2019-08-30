<?php
namespace app\modules\RentRider;


use app\models\order\PayBill;
use app\models\order\ServiceContract;
use app\models\order\VehicleRentOrder;
use app\models\service\StoreVehicle;
use app\models\service\Vehicle;
use app\models\service\VehicleLockQueue;
use app\modules\BaseController;
use app\services\data\RentRepairData;
use app\services\data\RentWarrantyData;

class VehicleController extends BaseController
{
    // 骑手车辆列表
    public function MyVehicleListAction()
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        $driverId = $this->authed->userId;
        $result = $this->CallService('order', 10013, [
            'driverId' => $driverId,
            'parentOperatorInsId' => $parentOperatorInsId,
        ], true);
        $vehicleList = $result['content']['vehicleList'];
        foreach ($vehicleList as $k => $vehicle){
            $vehicleList[$k] = &$vehicle;
            $vehicleId = $vehicle['vehicleId'];
            // 剩余还车时间
            $vehicle['readyRentTime'] = $vehicle['readyRentTime'] + 1800 - time();
            // 无联保，不用走下方
            if (!isset($vehicle['vehicleWarrantyOrderId'])){
                continue;
            }
            // 查询是否有未完结的维修单
            $repairOrder = (new RentRepairData())->getUnfinishedRepairByVehicleId($vehicleId);
            if ($repairOrder){
                // 维修状态1待接单2已接单3维修中4待支付5已完成 6已取消
                $vehicle['repairStatus'] = $repairOrder->repair_status;
                // 未完成维修，还车状态无效
                $vehicle['readyRentTime'] = -1;
            }
            // 若联保无截止时间，查询租赁截止时间
            if (0==$vehicle['vehicleWarrantyOrderEndAt']){
                $VRO = VehicleRentOrder::arrFindFirst([
                    'service_contract_id' => $vehicle['serviceContractId'],
                    'pay_status' => 2,
                ],['columns' => 'MAX(end_time) AS maxEndTime']);
                if ($VRO){
                    $vehicle['vehicleWarrantyOrderEndAt'] = $VRO->maxEndTime;
                }
            }
        }
        return $this->toSuccess($vehicleList);
    }

    // 骑手绑车
    public function BindVehicleAction()
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        $bianhao = $request['bianhao'] ?? null;
        if (empty($bianhao)){
            return $this->toError(500,'未收到有效车辆编号');
        }
        // 查询骑手待绑车合约
        $SC1 = ServiceContract::arrFindFirst([
            'parent_operator_ins_id' => $parentOperatorInsId,
            'driver_id' => $driverId,
            'status' => ServiceContract::STATUS_USING,
        ]);
        if($SC1) {
            return $this->toError(500, "您已绑车，请勿重复绑车");
        }
        // 查询骑手待绑车合约
        $SC = ServiceContract::arrFindFirst([
            'parent_operator_ins_id' => $parentOperatorInsId,
            'driver_id' => $driverId,
            'status' => ServiceContract::STATUS_PAID_UNBIND,
        ]);
        if (false == $SC){
            return $this->toError(500, "您的服务套餐暂未购买，不可使用平台服务，快去选购吧");
        }
        $serviceContractId = $SC->id;
        // 查询车辆
        $vehicle = Vehicle::arrFindFirst([
            'bianhao' => $bianhao,
        ]);
        if (false == $vehicle){
            return $this->toError(500, '未查询到车辆信息');
        }
        $vehicle = $vehicle->toArray();
        if ($vehicle['udid'] == "-1") {
            return $this->toError(500, '该车暂不对外出租，有问题请及时联系店主');
        }
        $vehicleId = $vehicle['id'];
        $vin = $vehicle['vin'];
        // 查询合约套餐车型、联保
        $result = $this->CallService('order', 10045, ['serviceContractId' => $serviceContractId], true);
        $baseCondition = $result['content']['data'][0];
//        if ($vehicle['vehicle_model_id'] != $baseCondition['vehicleModelId']){
//            return $this->toError(500, '当前车型不属于您购买的套餐,不可绑定');
//        }
        $packageId = $baseCondition['packageId'];
        $vehicleRentOrderId = $baseCondition['vehicleRentOrderId'];
        $hasWarranty = $baseCondition['warrantyServiceItemId']??0 > 0 ? true : false;
        // 查询车辆绑定门店
        $SV = StoreVehicle::arrFindFirst(['vehicle_id'=>$vehicle['id']]);
        if (false == $SV){
            return $this->toError(500, '车辆未绑定门店,不可使用');
        }
        if (StoreVehicle::UN_RENT != $SV->rent_status){
            return $this->toError(500, '车辆不处于待租状态，不可绑定');
        }
        $storeId = $SV->store_id;

        // 查询门店-套餐关系
        $result = $this->CallService('order', 10014, [
            "storeId" => $storeId,
            "packageId" => $packageId,
        ], true);
        $packageStoreRelation = $result['content']['packageStoreRelation'];
        if (false == $packageStoreRelation['ok']){
            return $this->toError(500, $packageStoreRelation['tip']);
        }
        // 绑车
        $this->CallService('order', 10021, [
            'rentOrderId' => $vehicleRentOrderId,
            'vehicleId' => $vehicleId,
            'driverId' => $driverId,
            'storeId' => $storeId,
        ], true);
        // 删除锁车队列
        (new VehicleLockQueue())->del($vehicleId);
        // 有联保服务 创建联保订单
        if ($hasWarranty){
            // 创建联保订单
            $bol = (new RentWarrantyData())->CreateWarrantyOrder($vehicleId, $driverId, $serviceContractId, $bianhao, $vin);
            if (false == $bol){
                return $this->toError(500, '联保服务异常');
            }
        }
//       不能发版注释
        $parameter2 = ['driverId' => $driverId,'typeId' => 2,'createTime'=> time()];
        $result2 = $this->CallService('dispatch', 61103, $parameter2, true);
        return $this->toSuccess();
    }

    // 骑手还车
    public function ReturnVehicleAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? null;
        if (empty($vehicleId)){
            return $this->toError(500,'参数错误');
        }
        // 查询骑手-门店车辆关系
        $SV = StoreVehicle::arrFindFirst([
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
        ]);
        if (false === $SV){
            return $this->toError(500, '未查询到骑手车辆关系');
        }
        $serviceContractId = $SV->service_contract_id;
        // 查询是否有未完结的维修单
        $repairOrder = (new RentRepairData())->getUnfinishedRepairByVehicleId($vehicleId);
        if ($repairOrder){
            return $this->toError(500, '您还有未完结的维修单，不可还车');
        }
        // 骑手当前套餐是否有待支付支付单
        $Unpaid = PayBill::arrFindFirstUnpaid([
            'driver_id' => $driverId,
            'service_contract_id' => $serviceContractId,
        ]);
        if ($Unpaid){
            return $this->toError(500, '当前套餐存在待支付账单，不可还车');
        }
        // 查询骑手是否有待结算换电费用
        $result = $this->CallService('order', 10047, [
            'serviceContractId' => $serviceContractId,
        ], true);
        if (($result['content']['chargingCost']['unPayNum'] ?? 0) > 0){
            return $this->toError(500, '您还有未支付的换电单，不可还车');
        }
        // 修改还车时间
        $this->CallService('biz', 10025, [
            "vehicleId" => $vehicleId,
            "readyRentTime" => time(),
        ], true);
        $this->toSuccess();
    }
}
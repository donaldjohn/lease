<?php
namespace app\modules\RentRider;


use app\models\order\PayBill;
use app\models\order\ProductPackage;
use app\models\order\ReturnBill;
use app\models\order\ServiceContract;
use app\models\order\ServicePackageRelation;
use app\models\order\VehicleRentOrder;
use app\models\service\StoreVehicle;
use app\models\service\Vehicle;
use app\modules\BaseController;
use app\services\data\PackageData;
use app\services\data\ServiceContractData;

// 服务合约
class ContractController extends BaseController
{
    // 解约返回类型
    const RescindContractType_OK = 0;
    const RescindContractType_RefundMoney = 1;
    const RescindContractType_ReturnVehicle = 2;
    const RescindContractType_PayBill = 3;

    // 骑手APP我的套餐【服务单】
    public function MyServiceContractAction()
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();

        $driverId = $this->authed->userId;
        $status = $_GET['status'] ?? 2;
        $packageData = new PackageData();
        $serviceList = $packageData->getMyServiceContractList($driverId, $status,$parentOperatorInsId);
        if (empty($serviceList)){
            return $this->toSuccess($serviceList);
        }
        // 服务单idList
        $serviceIds = [];
        foreach ($serviceList as $service){
            $serviceIds[] = $service['id'];
        }
        // 最后有效支付单id
        $PBs = PayBill::arrFind([
            'service_contract_id' => ['IN', $serviceIds],
            'is_delete' => 0,
        ],[
            'group' => 'service_contract_id',
            'columns' => 'service_contract_id, MAX(id) AS id'
        ]);
        $servicePayBillIds = [];
        if ($PBs){
            $PBs = $PBs->toArray();
            foreach ($PBs as $PB){
                $servicePayBillIds[$PB['service_contract_id']] = $PB['id'];
            }
        }
        // 支付单编号【兼容后续流程老接口调用】
        $PBSNs = PayBill::arrFind([
            'id' => ['IN', $servicePayBillIds]
        ]);
        $PayBillSNs = [];
        if ($PBSNs){
            $PBSNs = $PBSNs->toArray();
            foreach ($PBSNs as $PBSN){
                $PayBillSNs[$PBSN['id']] = $PBSN['business_sn'];
            }
        }
        // 租赁单
        $VROs = VehicleRentOrder::arrFind([
            'service_contract_id' => ['IN', $serviceIds],
            'is_delete' => 0,
        ],[
            'group' => 'service_contract_id',
            'columns' => 'service_contract_id, vehicle_id'
        ]);
        $serviceVehicleIds = [];
        if ($VROs){
            $VROs = $VROs->toArray();
            foreach ($VROs as $VRO){
                $serviceVehicleIds[$VRO['service_contract_id']] = $VRO['vehicle_id'];
            }
        }
        // 车辆编号
        $vehicles = [];
        if ($serviceVehicleIds){
            $vs = Vehicle::arrFind([
                'id' => ['IN', array_values($serviceVehicleIds)]
            ]);
            if ($vs){
                $vs = $vs->toArray();
                foreach ($vs as $vehicle){
                    $vehicles[$vehicle['id']] = $vehicle;
                }
            }
        }

        foreach ($serviceList as $k => $service){
            $service['payBillId'] = $servicePayBillIds[$service['id']] ?? 0;
            $service['payBillSN'] = $PayBillSNs[$service['payBillId']] ?? '';
            $vehicleId = $serviceVehicleIds[$service['id']] ?? 0;
            $service['vehicleBianhao'] = $vehicles[$vehicleId]['bianhao'] ?? null;
            $serviceList[$k] = $service;
        }
        return $this->toSuccess($serviceList);
    }

    /**
     * APP发起解约
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \Exception
     */
    public function RescindContractAction()
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        $serviceContractId = $request['serviceContractId'] ?? null;
        if (empty($serviceContractId)){
            return $this->toError(500, '未收到服务合约');
        }
        $backData = [
            'type' => self::RescindContractType_OK
        ];
        // 查询服务单
        $SC = ServiceContract::arrFindFirst([
            'id' => $serviceContractId,
            'driver_id' => $driverId,
            'status' => ['NOT IN', [1,2]],
            'is_delete' => 0,
            'parent_operator_ins_id' => $parentOperatorInsId
        ]);
        if (false == $SC){
            return $this->toError(500, '当前服务单不可操作解约');
        }
        // 查询是否需要解除车辆
        $SV = StoreVehicle::arrFindFirst([
            'driver_id' => $driverId,
            'service_contract_id' => $serviceContractId,
        ]);
        if ($SV){
            $backData['type'] = self::RescindContractType_ReturnVehicle;
            return $this->toSuccess($backData);
        }
        // 查询合约是否有未支付支付单
        $unPayBill = PayBill::arrFindFirst([
            'service_contract_id' => $serviceContractId,
            'pay_status' => PayBill::NotPay,
            'is_delete' => 0,
        ]);
        if ($unPayBill){
            $backData['type'] = self::RescindContractType_PayBill;
            $backData['businessSn'] = $unPayBill->business_sn;
            return $this->toSuccess($backData);
        }
        // 尝试生成结算账
        $result = $this->CallService('order', 10031, [
            'serviceContractId' => $serviceContractId,
            'driverId' => $driverId,
        ], '系统异常，结算失败');
        // 如果有结算账单
        if (null !== $result['content']['businessSn']){
            $backData['type'] = self::RescindContractType_PayBill;
            $backData['businessSn'] = $result['content']['businessSn'];
            return $this->toSuccess($backData);
        }
        // 合约退款
        $result = $this->CallService('order', 10030, [
            'serviceContractId' => $serviceContractId
        ], true);
        // 有生成退款单
        if(null !== $result['content']['data']){
            $backData['type'] = self::RescindContractType_RefundMoney;
            $backData['returnBill'] = (new PackageData())->HandlePrice($result['content']['data'], ['amount', 'depositAmount', 'rentAmount']);
            return $this->toSuccess($backData);
        }
        return $this->toSuccess($backData);
    }

    // 骑手购买套餐签约
    public function SignContractAction()
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        $driverId = $this->authed->userId;
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        $packageId = $request['packageId'] ?? null;
        if (empty($packageId)){
            return $this->toError(500, '套餐id不可为空');
        }
        // 骑手是否有未解除服务单
        $SC = ServiceContract::arrFindFirst([
            'parent_operator_ins_id' => $parentOperatorInsId,
            'driver_id' => $driverId,
            'status' => ['IN', [1,2,3]],
            'is_delete' => 0,
        ]);
        if ($SC){
            return $this->toError(500, '签约失败，您还有尚未解除的套餐。');
        }
        // 查询套餐信息
        $package = (new PackageData())->getPackageById($packageId);
        $serviceItemTypeIds = [];
        foreach ($package['serviceItems'] as $serviceItem){
            $serviceItemTypeIds[] = $serviceItem['serviceItemType'];
        }
        $parameter = [
            // 骑手id
            'driverId' => $driverId,
            // 套餐id
            'packageId' => $package['productPackage']['packageId'],
            // 套餐包含的服务项type list
            'serviceItemTypes' => $serviceItemTypeIds,
            // 租期 天
            'rentPeriod' => $package['productPackage']['rentPeriod'],
            // 租金
            'rentAmount' => $package['productPackage']['packageRent'],
            // 押金
            'depositAmount' => $package['productPackage']['packageDeposit'],
            // 运营商机构id
            'operatorInsId' => $package['productPackage']['operatorInsId'],
            // 加盟商机构id
            'parentOperatorInsId' => $parentOperatorInsId,
        ];
        // 创建契约
        $result = $this->CallService('order', 10015, $parameter, true);

        // 返回支付单号
        return $this->toSuccess([
            'type' => 1,
            'businessSn' => $result['content']['data']['businessSn'],
        ]);
    }

    // 获取骑手各状态合约数量
    public function GetContractNumAction()
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        $driverId = $this->authed->userId;
        $SCs = ServiceContract::arrFind([
            'driver_id' => $driverId,
            'parent_operator_ins_id' => $parentOperatorInsId
            // 'is_delete' => 0
        ],[
            'group' => 'status',
            'columns' => 'status, count(id) AS num'
        ]);
        $data = [];
        if ($SCs){
            $SCs = $SCs->toArray();
            foreach ($SCs As $SC){
                $data[$SC['status']] = $SC['num'];
            }
        }
        return $this->toSuccess($data);
    }

    // 退款明细
    public function ReturnInfoAction($serviceContractId)
    {
        $returnBill = ReturnBill::arrFindFirst([
            'service_contract_id' => $serviceContractId,
        ]);
        if (false == $returnBill){
            return $this->toError(500, '未查询到退款信息');
        }
        $returnBill = $returnBill->toArray();
        PayBill::keyToHump($returnBill);
        // 查询租金
        $VRO = VehicleRentOrder::arrFindFirst([
            'service_contract_id' => $serviceContractId,
        ]);
        if ($VRO){
            $returnBill['payRent'] = $VRO->amount;
            // 查询租赁起止时间
            $returnBill['rentTimeInfo'] = (new ServiceContractData())->getRentStartEndTimeByServiceId($serviceContractId);
        }
        (new PackageData())->HandlePrice($returnBill, ['amount', 'depositAmount', 'rentAmount', 'payRent']);
        return $this->toSuccess($returnBill);
    }

    // 续租
    public function RenewLeaseAction($serviceContractId)
    {
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        // 获取骑手id
        $driverId = $this->authed->userId;
        /*
        // 查询是否服务单
        $SC = ServiceContract::arrFindFirst([
            'id' => $serviceContractId,
        ]);
        if (false == $SC){
            return $this->toError(500, '未查到合约信息，请稍后再试');
        }
        $SC = $SC->toArray();*/
        // 查询续租次数
        $SPR = ServicePackageRelation::arrFindFirst([
            'service_contract_id' => $serviceContractId,
            'status' => 1
        ]);
        if (false == $SPR){
            return $this->toError(500, '未查到有效合约信息，请稍后再试');
        }
        // 查询套餐续租限制
        $package = ProductPackage::arrFindFirst([
            'package_id' => $SPR->package_id,
        ]);
        if (false == $package){
            return $this->toError(500, '套餐信息获取失败');
        }

        if ($package->status != 1 || $package->is_delete == 1) {
            return $this->toError(500, '套餐已下架');
        }
//        if ($SPR->rent_num > $package->max_renew){
//            return $this->toError(500, '您已达到当前合约套餐租赁次数上限，不可续租');
//        }
        // 查询绑车情况
        $SV = StoreVehicle::arrFindFirst([
            'service_contract_id' => $serviceContractId,
        ]);
        if (false == $SV){
            return $this->toError(500, '当前合约未绑车，不可续租');
        }
        // 查询是否有未付账单
        $payBill = PayBill::arrFindFirstUnpaid([
            'service_contract_id' => $serviceContractId,
        ]);
        if ($payBill){
            return $this->toError(500, '您有账单尚未支付，请先前往支付单支付');
        }
        $rentStartEndTime = (new ServiceContractData())->getRentStartEndTimeByServiceId($serviceContractId);
        $rentStartTime = $rentStartEndTime['startTime'] ?? 0;
        if ($rentStartTime == 0 || (time() - $rentStartTime)<3600*24*7){
            return $this->toError(500, '租车时间不足7天，不可续租');
        }

        // 发起续租
        $result = $this->CallService('order', 10016, [
            'driverId' => $driverId,
            'serviceContractId' => $serviceContractId,
        ], true);
        // 新账单编号
        $newBusinessSn = $result['content']['data'];
        // 改为直接返回订单编号
        return $this->toSuccess([
            'businessSn' => $newBusinessSn,
        ]);
    }
}

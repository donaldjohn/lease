<?php
namespace app\modules\RentRider;


use app\models\order\ChargingOrder;
use app\models\order\Deposit;
use app\models\order\PayBill;
use app\models\order\ProductPackage;
use app\models\order\ServiceContract;
use app\models\order\ServicePackageRelation;
use app\models\order\VehicleRentOrder;
use app\modules\BaseController;
use app\services\data\PackageData;
use Phalcon\Paginator\Adapter\QueryBuilder;

class PaybillController extends BaseController
{
    // 支付单列表
    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 支付单列表支持区分不同加盟商
     *
     */
    public function ListAction()
    {
        $insId = $this->appData->getParentOperatorInsId();
        // 获取骑手id
        $driverId = $this->authed->userId;
        $pageSize = $_GET['pageSize'] ?? 20;
        $pageNum = $_GET['pageNum'] ?? 1;

        $builder = $this->modelsManager->createBuilder()
            ->columns('p.id,p.service_contract_id as serviceContractId,p.business_sn as businessSn,p.driver_id as driverId,p.pay_record_id as payRecordId,p.amount,p.pay_time as payTime,p.pay_status as payStatus,p.create_time as createTime,p.update_time as updateTime')
            ->addFrom('app\models\order\PayBill','p')
            ->leftJoin('app\models\order\ServiceContract','p.service_contract_id = s.id','s')
            ->andWhere('s.parent_operator_ins_id = :parent_operator_ins_id: and p.driver_id = :driverId: and p.is_delete = 0 ',['parent_operator_ins_id' => $insId,'driverId' => $driverId])
            ->orderBy('p.create_time DESC');
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();
        $result = $this->dataIntegration($pages);
        (new PackageData())->HandlePrice($result['data']);
        return  $this->toSuccess($result['data'],$result['meta']);


//        $payBillPage = PayBill::arrFindPage($pageSize, $pageNum,[
//            'driver_id' => $driverId,
//            'is_delete' => 0,
//        ], ['order' => 'create_time DESC']);
//
//        (new PackageData())->HandlePrice($payBillPage['list']);
//        return $this->toSuccess($payBillPage['list'], $payBillPage['meta']);
    }

    // 支付单详情
    public function InfoAction($businessSn)
    {
        // 获取骑手id
        $driverId = $this->authed->userId;
        // TODO: 兼容旧式支付单编号入参，后期转为id
        $bill = PayBill::arrFindFirst([
            'business_sn' => $businessSn,
            'id' => $businessSn,
        ], 'or');
        if (false == $bill){
            return $this->toError(500, '支付单不存在');
        }
        $bill = $bill->toArray();
        $payBillId = $bill['id'];
        $serviceContractId = $bill['service_contract_id'];
        // 服务单号
        $SC = ServiceContract::arrFindFirst([
            'id' => $serviceContractId,
        ]);
        $bill['serviceSn'] = $SC->service_sn;
        // 获取关联套餐
        $SPR = ServicePackageRelation::arrFindFirst([
            'service_contract_id' => $serviceContractId,
            'status' => 1,
        ]);
        if (false == $SPR){
            return $this->toError(500, '关联套餐失败');
        }
        $packageId = $SPR->package_id;
        // 查询套餐基本信息
        $package = ProductPackage::arrFindFirst([
            'package_id' => $packageId,
        ]);
        $bill['packageCode'] = $package->product_package_code;
        $bill['packageName'] = $package->product_package_name;
        // 查询关联押金单
        $deposit = Deposit::arrFindFirst([
            'pay_bill_id' => $payBillId,
        ]);
        if ($deposit){
            $bill['depositSn'] = $deposit->deposit_sn;
            $bill['depositAmount'] = $deposit->amount;
        }
        // 租赁单
        $VRO = VehicleRentOrder::arrFindFirst([
            'pay_bill_id' => $payBillId,
        ]);
        if ($VRO){
            $bill['vehicleRentOrderSn'] = $VRO->order_sn;
            $bill['rentAmount'] = $VRO->amount;
        }
        // 换电总计
        $CO = ChargingOrder::arrFindFirst([
            'pay_bill_id' => $payBillId,
        ]);
        if ($CO){
            $bill['chargingSn'] = $CO->charging_sn;
            $bill['chargingAmount'] = $CO->amount;
        }
        PayBill::keyToHump($bill);
        // 支付超时剩余时间
        $bill['remainingTime'] = $bill['createTime']+900-time();
        (new PackageData())->HandlePrice($bill,['amount', 'depositAmount', 'rentAmount', 'chargingAmount']);
        return $this->toSuccess($bill);
    }
}
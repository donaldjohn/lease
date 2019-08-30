<?php
namespace app\modules\RentRider;

use app\models\dispatch\DriversIdentification;
use app\models\order\PayBill;
use app\models\order\ServiceContract;
use app\models\users\Operator;
use app\modules\BaseController;
use app\services\data\PackageData;

// 套餐
class PackageController extends BaseController
{
    // 校验骑手能否购买套餐
    public function BuyPackagePermissionAction()
    {
        $insId = $this->appData->getParentOperatorInsId();
        $driverId = $this->authed->userId;
        $packageId = $_GET['packageId'] ?? null;
        if (empty($packageId)){
            return $this->toError(500, '参数错误');
        }
        $backData = [
            // 1-正常 2-支付单 3-有合约 4-骑手未实名
            'type' => 1,
        ];
        // 骑手是否实名
        $DI = DriversIdentification::arrFindFirst([
            'driver_id' => $driverId
        ]);
        if (false == $DI){
            $backData['type'] = 4;
            return $this->toSuccess($backData);
        }
        // 骑手是否有待支付支付单
        $Unpaid = PayBill::arrFindFirstUnpaid([
            'driver_id' => $driverId,
        ]);
        $builder = $this->modelsManager->createBuilder()
            ->columns('p.id')
            ->addFrom('app\models\order\PayBill','p')
            ->leftJoin('app\models\order\ServiceContract','p.service_contract_id = s.id','s')
            ->andWhere('s.parent_operator_ins_id = :parent_operator_ins_id: and p.driver_id = :driverId: and p.is_delete = 0 and p.pay_status = 1',['parent_operator_ins_id' => $insId,'driverId' => $driverId])
            ->getQuery()
            ->getSingleResult();
        if ($builder){
            $backData['type'] = 2;
            $backData['businessSn'] = $Unpaid->business_sn;
            $backData['tips'] = '您已有待支付订单，您可选择继续上次支付或更换套餐';
            return $this->toSuccess($backData);
        }
        // 骑手是否有未解除服务单
        $SC = ServiceContract::arrFindFirst([
            'driver_id' => $driverId,
            'status' => ['IN', [1,2,3]],
            'is_delete' => 0,
            'parent_operator_ins_id' => $insId
        ]);
        if ($SC){
            $backData['type'] = 3;
            $backData['tips'] = '您还有尚未解除的套餐，不可签约新套餐。';
            return $this->toSuccess($backData);
        }
        return $this->toSuccess($backData);
    }

    // 套餐列表
    public function PackageListAction()
    {
        $insId = $this->appData->getParentOperatorInsId();
        $userType = 11;
        $areaId = $_GET['areaId'] ?? 0;
        if (!($areaId>0)){
            return $this->toError(500, '未获取到区域信息');
        }
        $areaId = substr($areaId, 0, 4) . '00';
        // 获取当前区域可以查到的套餐id列表
        $result = $this->CallService('order', 10012, [
            'areaId' => (int)$areaId,
            'insId' => (int)$insId,
            'userType' => (int)$userType,
        ], true);
        $packageIds = $result['content']['packageIdList'];
        // 获取套餐详情
        $packageData = new PackageData();
        $packages = $packageData->getPackagesAndProductInfoByPackageIds($packageIds);
        // 处理价格
        $packageData->HandlePrice($packages, null, true);
        // 处理运营商名称
        $operatorInsIds = [];
        foreach ($packages as $packageInfo){
            $operatorInsIds[] = $packageInfo['productPackage']['operatorInsId'];
        }
        $operatorNames = $this->getOperatorShortNames($operatorInsIds);
        foreach ($packages as $k => $packageInfo){
            $packages[$k]['productPackage']['operatorShortName'] = $operatorNames[$packageInfo['productPackage']['operatorInsId']] ?? '';
        }
        return $this->toSuccess($packages);
    }

    // 获取运营商名称
    private function getOperatorShortNames($operatorInsIds){
        $operatorInsIds = array_diff(array_unique($operatorInsIds), [0, null]);
        if (empty($operatorInsIds)){
            return [];
        }
        $operators = Operator::arrFind([
            'ins_id' => ['IN', $operatorInsIds]
        ])->toArray();
        $operatorNames = [];
        foreach ($operators as $operator){
            $operatorNames[$operator['ins_id']] = $operator['operator_short_name'];
        }
        return $operatorNames;
    }
}

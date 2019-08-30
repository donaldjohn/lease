<?php
namespace app\modules\RentRider;



class ModuleRouter extends \app\modules\ModuleRouter
{
    /**
     * @throws \Phalcon\Mvc\Router\Exception
     */
    public function initialize()
    {
        $this->FastRoutes([
            // 骑手查看我的套餐【服务单】
            '/MyServiceContract' => ['Contract', 'MyServiceContract'],
            // 骑手购买套餐签约
            'POST /SignContract' => ['Contract', 'SignContract'],
            // APP发起解约
            'POST /RescindContract' => ['Contract', 'RescindContract'],
            // 获取骑手各状态合约数量
            'GET /ContractNum' => ['Contract', 'GetContractNum'],
            // 退款详情
            'GET /ReturnInfo/{serviceContractId:[0-9]+}' => ['Contract', 'ReturnInfo'],
            // 续租
            'POST /RenewLease/{serviceContractId:[0-9]+}' => ['Contract', 'RenewLease'],
            // 校验骑手能否购买套餐
            'GET /BuyPackage/check' => ['Package', 'BuyPackagePermission'],
            // 套餐列表
            'GET /Package' => ['Package', 'PackageList'],
            // 骑手车辆列表
            'GET /Vehicle' => ['Vehicle', 'MyVehicleList'],
            // 骑手绑车
            'POST /BindVehicle' => ['Vehicle', 'BindVehicle'],
            // 骑手还车
            'POST /ReturnVehicle' => ['Vehicle', 'ReturnVehicle'],
            // 支付单列表
            'GET /PayBill' => ['Paybill', 'List'],
            // 支付单详情
            'GET /PayBill/{businessSn}' => ['Paybill', 'Info'],
        ]);
    }

}
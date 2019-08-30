<?php
namespace app\modules\rent;

use app\modules\BaseController;
use app\services\data\DriverData;
use app\services\data\StoreData;
use app\services\data\BillData;
use app\services\data\PackageData;
use app\services\data\VehicleData;
use app\services\data\MessagePushData;
use app\models\order\VehicleRepairOrder;

// 维修单模块
class RepairorderController extends BaseController
{
    /**
     * 联保推送维修单更新接口
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     */
    public function updateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['repairSn']) || empty($request['repairSn'])){
            return $this->toError(500, '参数错误');
        }
        $repairSn = $request['repairSn'];
        $repair = VehicleRepairOrder::findFirst([
            'conditions' => 'repair_sn = ?1',
            'bind'       => [
                1 => $repairSn,
            ]
        ]);
        if (false===$repair){
            return  $this->toError(500, '维修单不存在');
        }
        // 处理钱钱
        if (isset($request['cost'])){
            $repair->dewin_pay = (int)($request['cost']['warrantyPayTotal']*10000);
            $repair->driver_pay = (int)($request['cost']['selfPayTotal']*10000);
            $repair->total_pay = $repair->dewin_pay + $repair->driver_pay;
        }
        // 维修信息
        if (isset($request['repairInfo'])){
            $repair->repair_message = $request['repairInfo'];
        }
        // 维修状态1待接单2已接单3维修中4待支付5已完成 6已取消
        $repair->repair_status = $request['status'];
        // 根据状态判断是更新相关时间
        switch ($request['status']) {
            case 3:
                $repair->repair_start_at = time();
                break;
            case 4:
                $repair->repair_end_at = time();
                break;
            case 5:
                $repair->pay_at = time();
                break;
            case 6:
                $repair->cancel_at = time();
                break;
        }
        $bol = $repair->save();
        if (!$bol){
            return  $this->toError(500, '处理失败');
        }
        // 获取消息通知模版
        $MessagePushData = new MessagePushData();
        $EventCode = $MessagePushData->getRepairEventCodeByRepairStatus($repair->repair_status);
        // 如果存在对应模版，发送消息
        if ($EventCode){
            // 获取车辆编号
            $vehicle = (new VehicleData())->getVehicleById($repair->vehicle_id);
            // 获取门店名称
            $store = (new StoreData())->getStoreById($repair->store_id);
            // 组装数据
            $data = [
                'repairId' => $repair->id,
                'repairSn' => $repairSn,
                'bianhao' => $vehicle['bianhao'],
                'storeName' => $store['storeName'],
            ];
            //发送通知 消息失败不影响流程
            $MessagePushData->SendMessageToDriverV2($repair->driver_id, MessagePushData::DW_DRIVER_APP, $EventCode, $data);
        }
        return $this->toSuccess();
    }




    /**
     *  维修单列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            // 联保单号
            'repairSn' => 0,
            'serviceSn' => 0,
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
        // 查询押金单列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "11051",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 处理金额 和时间
        (new PackageData())->HandlePrice($list, ['totalPay']);
        foreach ($list as $k => $item) {
            $list[$k]['createAt'] = (0 == $item['createAt']) ? '-' : date('Y-m-d H:i:s', $item['createAt']);
        }

        // 成功返回
        return $this->toSuccess($list, $meta);
    }


}

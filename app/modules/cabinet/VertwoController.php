<?php
namespace app\modules\cabinet;

use app\models\order\ServiceContract;
use app\modules\BaseController;
use app\services\data\BillData;
use app\services\data\CabinetData;

// 换电柜二期接口
class VertwoController extends BaseController {

    private $PollingTips = [
        '0' => '成功',
        '1' => '命令已被接收，正在处理',
        '2' => '充换电柜正忙',
        '3' => '没有满电柜',
        '4' => '空门打开失败',
        '5' => '未检测到放入的电池',
        '6' => '满电柜门打开失败',
        '7' => '骑手未取出电池',
        '8' => '当前柜门未关上，请先关闭柜门',
        '11' => '访问超时，请重新扫码',
        '255' => '未知错误',
    ];

    // 骑手扫码查询换电柜版本
    public function findVersionAction()
    {
        $fields = [
            'qrcode' => '柜组二维码编号不可为空'
        ];
        $request = $this->request->getJsonRawBody(true);
        $parameter = $this->getArrPars($fields, $request);
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->charging,[
            'code' => 32783,
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200' || !isset($result['content']['result'])) {
            return $this->toError(500, $result['msg']);
        }
        return $this->toSuccess([
            'version' => 'oldVersion' == $result['content']['result'] ? 1 : 2,
        ]);
    }

    // 骑手开换电柜门
    public function OpenRoomAction()
    {
        $driverId = $this->authed->userId;
        // TODO: 下方为复制世钦原有代码,未优化
        // 查询骑手待支付账单
        $bill = (new BillData())->getUnpaidBillByDriverId($driverId);
        // 如果骑手有待支付账单，返回支付单编号
        if ($bill){
            return $this->toSuccess([
                'status' => 3,
                'businessSn' => $bill['business_sn'],
            ]);
        }
        // 查询骑手合约
        $SC = ServiceContract::arrFindFirst([
            'driver_id' => $driverId,
            'status' => ServiceContract::STATUS_USING,
        ]);
        if (false == $SC){
            return $this->toError(500, "您的服务套餐暂未购买，不可使用平台服务，快去选购吧");
        }
        $serviceContractId = $SC->id;

        // 获取服务单的状态和拥有的服务类型
        $result = $this->CallService('order', 10048, ['serviceContractId'=>$serviceContractId],true);
        $serviceContractContainItem = $result['content']['data'];

        if (ServiceContract::STATUS_USING != $serviceContractContainItem['serviceContractStatus']){
            return $this->toError(500, '您的服务套餐暂未购买，不可使用平台服务，快去选购吧');
        }
        if (false == $serviceContractContainItem['hasCharging']){
            return $this->toError(500, '没有换电服务类型，不能提供更换电池服务');
        }
        if ($serviceContractContainItem['hasRent']
            && $serviceContractContainItem['vehicleRentOrderEndTime'] < time()){
            return $this->toError(500, '租车订单已经过期，不能换电池');
        }
        // 有未支付的换电单
        if ($serviceContractContainItem['unpaidChargingOrderNum'] > 0){
            return $this->toSuccess([
                'status' => 2,
            ]);
        }
        // TODO: 下方为二期换电柜内容
        $fields = [
            'qrcode' => '柜组二维码编号不可为空'
        ];
        $request = $this->request->getJsonRawBody(true);
        $parameter = $this->getArrPars($fields, $request);


        // 查询换电价格
        $price = (new CabinetData())->getChargingPriceByQRCode($parameter['qrcode'], 2);
        if (is_null($price)){
            return $this->toError(500, '该区域未设置换电价格，请联系客服');
        }

        $parameter['driverId'] = $driverId;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->chargingHttp,[
            'code' => 32769,
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,$result['msg']);
        }
        return $this->toSuccess([
            'status' => 1,
        ]);
    }

    // 骑手轮询结果接口
    public function PollingUpshotAction()
    {
        $driverId = $this->authed->userId;
        $fields = [
            'qrcode' => '柜组二维码编号不可为空'
        ];
        $request = $this->request->getJsonRawBody(true);
        $parameter = $this->getArrPars($fields, $request);
        $parameter['driverId'] = $driverId;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->charging,[
            'code' => 32782,
            'parameter' => $parameter
        ],"post");
        $status = $result['content']['status'] ?? -1;
        // step: 1-正在处理，继续轮询 2-换电完成 3-失败异常
        $data = [
            'step' => 3,
            'status' => $status,
            'tip' => $this->PollingTips[$status] ?? $result['msg'] ?? '未知异常',
        ];
        // 换电完成(微服务生成订单)
        if (0 == $status){
            $data['price'] = (new CabinetData())->getChargingPriceByQRCode($parameter['qrcode'],2);
            $data['step'] = 2;
        }
        if (1 == $status){
            $data['step'] = 1;
        }
        return $this->toSuccess($data);
    }


    /** 复制世钦代码
     * 辅助方法：查询当前服务契约整合信息
     * @param int $driverId  门店
     * @return mixed
     */
    private function orderInfo($driverId)
    {
        if ($driverId == 0) {
            return false;
        }

        // API请求所需参数封装
        $params = [
            'code' => 10036,
            'parameter' => [
                'driverId' => $driverId
            ]
        ];

        // 请求微服务获取换电柜异常列表
        $result = $this->curl->httpRequest($this->Zuul->order, $params, "POST");

        // 判断结果，并返回
        if ($result['statusCode'] == 200 && isset($result['content']['productPackage'])) {
            return $result['content'];
        } else {
            return false;
        }
    }
    /**复制世钦代码
     * 辅助方法：查询当前服务契约整合信息
     * @param int $driverId  门店
     * @return mixed
     */
    private function orderInfoContent($driverId)
    {
        if ($driverId == 0) {
            return false;
        }

        // API请求所需参数封装
        $params = [
            'code' => 10027,
            'parameter' => [
                'driverId' => $driverId
            ]
        ];

        // 请求微服务获取换电柜异常列表
        $result = $this->curl->httpRequest($this->Zuul->order, $params, "POST");

        // 判断结果，并返回
        if ($result['statusCode'] == 200 && isset($result['content']['rentOrder'])) {
            return $result['content'];
        } else {
            return false;
        }
    }
    /**复制世钦代码
     * 辅助方法：获取换电订单价格
     * @param int $userId  用户ID
     * @return mixed
     */
    public function getPrice($userId)
    {
        if ($userId == 0) {
            return false;
        }

        // API请求所需参数封装
        $params = [
            'code' => 10036,
            'parameter' => [
                'driverId' => $userId
            ]
        ];

        // 获取服务订单综合信息
        $result = $this->curl->httpRequest($this->Zuul->order, $params, "POST");

        // 判断结果，并返回
        if ($result['statusCode'] == 200 && isset($result['content']['productPackage'])) {
            // 存在契约订单信息，并通过packageIds获取商品套餐详情列表
            $packageIds[] = $result['content']['productPackage']['packageId'];

            $params = [
                'code' => 10008,
                'parameter' => [
                    'packageIds' => $packageIds
                ]
            ];

            // 判断获取商品套餐详情列表的结果
            $result = $this->curl->httpRequest($this->Zuul->order, $params, "post");
            if ($result['statusCode'] != 200 || !isset($result['content']['productPackageDetails'][0])) {
                return false;
            }

            // 判断商品套餐详情中是否存在换电服务类型
            $count = null;
            foreach ($result['content']['productPackageDetails'][0]['serviceItems'] as $key => $value) {
                if ($value['serviceItemType'] == 2) {
                    $count = $value['servicePrice'];
                }
            }

            if ($count === null) {
                return false;
            } else {
                return $count;
            }
        } else {
            return false;
        }
    }
    // 换电记录
    public function recordAction()
    {
        return $this->PenetrateTransfer(32773);
    }

    // 操作日志
    public function findOperationLogAction()
    {
        return $this->PenetrateTransfer(32774);
    }

    // 换电柜管理
    public function findChargingManageAction()
    {
        return $this->PenetrateTransfer(32775);
    }

    // 仓门管理
    public function findRoomManageAction()
    {
        return $this->PenetrateTransfer(32776);
    }

    // 电池管理
    public function findBatteryManageAction()
    {
        return $this->PenetrateTransfer(32777);
    }

    // 异常信息管理
    public function findAbnormalAction()
    {
        return $this->PenetrateTransfer(32778);
    }

    // 编辑网点
    public function editChargingAction()
    {
        return $this->PenetrateTransfer(32772);
    }

    // 解除网点
    public function delChargingAction()
    {
        return $this->PenetrateTransfer(32779);
    }

    // 异常信息处理
    public function handleAbnormalAction()
    {
        return $this->PenetrateTransfer(32780);
    }

    // 新增换电柜操作日志
    public function addOperationLogAction()
    {
        return $this->PenetrateTransfer(32781);
    }

    // 请求开门、开始充电、停止充电指令
    public function pubOperationAction()
    {
        return $this->PenetrateTransfer(32770, null, 'chargingHttp');
    }

    // 恢复门锁状态为正常状态
    public function resetLockAction()
    {
        return $this->PenetrateTransfer(32785);
    }

    // 32786-新增推送
    public function AddPushAction()
    {
        return $this->PenetrateTransfer(32786);
    }
    // 32787-编辑推送（启用禁用编辑同一个接口）
    public function EditPushAction()
    {
        return $this->PenetrateTransfer(32787);
    }
    // 32789-查询推送记录
    public function FindPushRecordingAction()
    {
        return $this->PenetrateTransfer(32789);
    }
    // 32790-查询推送管理界面
    public function FindPushListAction()
    {
        return $this->PenetrateTransfer(32790);
    }
    // 32791-查询推送详情
    public function PushInfoAction()
    {
        return $this->PenetrateTransfer(32791);
    }
    // 32788-查询内部所有用户
    public function InternalUserAction()
    {
        return $this->PenetrateTransfer(32788);
    }

    /**
     * 接口透传
     * @param $code 接口代码
     * @param null $parameter 接口参数【默认取JsonBody】
     * @param string $serviceName 服务名称
     * @param bool $DoNotHandleResult 是否直接处理结果
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    private function PenetrateTransfer($code, $parameter=null, $serviceName='charging', $DoNotHandleResult=false)
    {
        $parameter = $parameter ?? $this->request->getJsonRawBody(true);
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->$serviceName,[
            'code' => $code,
            'parameter' => $parameter
        ],"post");
        if ($DoNotHandleResult){
            return $result;
        }
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $data = $result['content']['data'] ?? [];
        $meta = $result['content']['pageInfo'] ?? [];
        return $this->toSuccess($data, $meta);
    }
}
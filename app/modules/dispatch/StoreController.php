<?php
namespace app\modules\dispatch;


use app\models\MyBaseModel;
use app\models\service\VehicleUsage;
use app\services\auth\AuthService;
use app\models\cabinet\Cabinet;
use app\models\order\PayBill;
use app\models\service\Vehicle;
use app\modules\BaseController;
use app\services\data\RentRepairData;
use app\services\data\RentWarrantyData;
use app\services\data\MessagePushData;
use app\services\data\DriverData;
use app\services\data\VehicleData;
use app\models\order\VehicleRepairOrder;
use app\models\service\StoreVehicle;

//门店模块
class StoreController extends BaseController
{
    const QRCODE_BIND   = 1;
    const QRCODE_RETURN = 2;
    const QRCODE_ERROR  = 3;

    /**
     * 查询门店
     * code：10057
     */
    public function listAction()
    {
        $fields = [
            // 店名
            'storeName' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (false === $parameter){
            return;
        }
        if ($this->authed->insId>0){
            $parameter['insId'] = $this->authed->insId;
        }
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => 10057,
            'parameter' => $parameter
        ],"post");
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'未获取到信息');
        }
        $list = $result['content']['stores'];
        //分页返回
        $meta['total'] = $result['content']['pageInfo']['total'];
        $meta['pageNum'] = $result['content']['pageInfo']['pageNum'];
        $meta['pageSize'] = $result['content']['pageInfo']['pageSize'];
        return $this->toSuccess($list,$meta);
    }



    /**
     * 获取当前登陆用户所属门店下的所有车辆
     * @return mixed
     */
    public function vehicleAction()
    {
        // 获取当前用户信息
        $user = $this->authed;

        // 通过用户ID获取门店信息
        $result = $this->userStore($user->userId);
        if ($result['status'] != 1) {
            return $this->toError(500, '当前用户没有所属门店信息');
        }
        $store = $result['data'];

        // 获取当前用户所属门店下的所有车辆
        $params = [
            "code" => 10024,
            "parameter" => [
                "storeId" => $store['id'],
            ]
        ];

        // 请求微服务接口提交换电柜组状态
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        // 判断结果返回
        if ($result['statusCode'] == 200 && isset($result['content']['data']) && count($result['content']['data']) > 0) {
            $list = $result['content']['data'];
            foreach ($list as $key => $value) {
                $list[$key]['vehicleInfo'] = $this->searchVehicle($value['vehicleId'])['data'];
                if (!empty($value['driverId'])) {
                    $list[$key]['driverInfo'] = $this->searchDriver($value['driverId'])['data'];
                } else {
                    $list[$key]['driverInfo'] = [];
                }
            }
            return $this->toSuccess($list);
        } else {
            return $this->toError(500, '数据获取失败');
        }
    }


    /**
     * 获取车辆的详细信息
     * @param int $vehicleId 车辆ID
     * @return mixed
     */
    private function searchVehicle($vehicleId = 0)
    {
        // 对参数进行有效性检测
        if ($vehicleId <= 0) {
            return ['status' => 0, 'data' => [], 'msg' => '车辆ID不能为空'];
        }

        // 调用微服务接口获取对应的车辆详情
        $params = [
            "code" => 60005,
            "parameter" => [
                "vehicleId" => $vehicleId,
            ]
        ];

        // 请求微服务接口提交换电柜组状态
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['VehicleDO'])) {
            return ['status' => 1, 'data' => $result['content']['VehicleDO'], 'msg' => '车辆信息获取成功'];
        } else {
            return ['status' => 0, 'data' => [], 'msg' => '车辆信息获取失败'];
        }
    }


    /**
     * 获取骑手的详细信息
     */
    private function searchDriver($driverId = 0, $insId = 0)
    {
        // 对参数进行有效性检测
        if ($driverId <= 0) {
            return ['status' => 0, 'data' => [], 'msg' => '骑手ID不能为空'];
        }

        // 调用微服务接口获取对应的车辆详情
        $driverList[] = $driverId;
        $params = [
            "code" => 60014,
            "parameter" => [
                "idList" => $driverList,
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->dispatch, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['driversDOS'][0])) {
            return ['status' => 1, 'data' => $result['content']['driversDOS'][0], 'msg' => '车辆信息获取成功'];
        } else {
            return ['status' => 0, 'data' => [], 'msg' => '车辆信息获取失败'];
        }
    }


    /**
     * 批量绑定车辆
     * @param array vehicleList 车辆绑定信息数组
     * @return mixed
     */
    public function BindAction()
    {
        // 对传参进行有效性验证
        $reqest = $this->request->getJsonRawBody();
        $vehicleList = isset($reqest->vehicleList) ? $reqest->vehicleList : '';

        if (empty($reqest->vehicleList)) {
            return $this->toError(500, '车辆列表不能为空');
        }

        // 封装参数中的车辆数组
        $list = [];
        foreach ($vehicleList as $key => $value) {
            $list[$key]['storeId'] = $this->userStore($this->authed->userId)['data']['id'];
            $list[$key]['vehicleId'] = $value->vehicleId;
            $list[$key]['rentStatus'] = 1;
            $list[$key]['driverId'] = $value->driverId;
        }

        $result = $this->CallService('biz', 10023, [
            "data" => $list
        ], true);
        return $this->toSuccess();
    }


    /**
     * 通过用户ID获取门店信息
     * @param int id 用户
     * @return mixed
     */
    private function userStore($userId = 0)
    {
        // 对参数进行验证
        if ($userId == 0) {
            return ['status' => 0, 'data' => [], 'msg' => '用户ID不能为空'];
        }

        // 调用微服务接口获取用户所属的机构信息
        $params = [
            "code" => 10058,
            "parameter" => [
                "userId" => $userId,
            ]
        ];

        // 请求微服务接口提交换电柜组状态
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['store'])) {
            return ['status' => 1, 'data' => $result['content']['store'][0], 'msg' => '获取信息成功'];
        } else {
            return ['status' => 0, 'data' => [], 'msg' => '门店信息获取失败'];
        }
    }


    /**
     * 扫码获取车辆信息并进行判断
     * @author Lishiqin
     * @param string bianhao 得威二维码编号
     * @return mixed
     */
    public function QrcodeInfoAction() {

        // 判断二维码编号的有效性
        $request = $this->request->get();
        $bianhao = isset($request['bianhao']) ? $request['bianhao'] : '';
        if (empty($bianhao)) {
            return $this->toError(500, '二维码不合法');
        }

        // 通过用户ID获取门店信息
        $result = $this->userStore($this->authed->userId);
        if ($result['status'] != 1) {
            return $this->toError(500, '当前用户没有所属门店信息');
        }
        $store = $result['data'];

        // 封装二维码数组
        $qrcode[] = $bianhao;
        $params = [
            'code' => 60012,
            'parameter' => [
                'bianhaoList' => $qrcode
            ]
        ];

        // 请求微服务接口获得二维码信息
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $params, "post");

        if ($result['statusCode'] == 200 && count($result['content']['vehicleDOS']) > 0) {
            $vehicle = $result['content']['vehicleDOS'][0];
            // TODO: 避免小程序和原有代码过多修改，直接拦截获取纯邮管车辆信息
            $VUs = VehicleUsage::arrFind([
                'vehicle_id' => $vehicle['id'],
            ])->toArray();
            if ($VUs){
                $attributes = [];
                foreach ($VUs as $VU){
                    $attributes[] = $VU['use_attribute'];
                }
                if (in_array(4, $attributes) && !in_array(2,$attributes)){
                    return $this->toError(500, '纯邮管车辆,不可操作');
                }
            }
            // TODO: 避免小程序修改，替换返回骑手id
            $SV = StoreVehicle::arrFindFirst(['vehicle_id'=>$vehicle['id']]);
            if ($SV){
                $vehicle['driverId'] = $SV->driver_id;
            }

            $qrcodeStatus = $this->vehicleStoreBind($vehicle['id'], $store['id'])['status'];
            switch ($qrcodeStatus) {
                case 0:
                    $vehicle['qrcodeStatus'] = 0;
                    break;
                case 1:
                    $vehicle['qrcodeStatus'] = 1;
                    break;
                case 2:
                    $vehicle['qrcodeStatus'] = 2;
                    break;
                case 3:
                    $vehicle['qrcodeStatus'] = 3;
                    break;
                default:
                    $vehicle['qrcodeStatus'] = 4;
            }
            return $this->toSuccess($vehicle);
        } else {
            return $this->toError(500, "未查到二维码信息");
        }
    }


    /**
     * 门店车辆还车操作
     * @param $qrcode
     * @return mixed
     */
    public function returnVehicleAction()
    {
        /**
         * 根据骑手所在门店推送到对应app
         * 门店机构ID = $this->authed->userId
         */
        $appCode = $this->appData->getAppCodeByStoreInsId($this->authed->insId);

        // TODO:避免小程序修改，暂不调整输入，后期支持多套餐需重写
        $request = $this->request->getJsonRawBody(true);
        $driverId = $request['driverId'] ?? null;
        if (empty($driverId)) {
            return $this->toError(500, '骑手Id不能为空');
        }
        // 通过用户ID获取门店信息
        $result = $this->userStore($this->authed->userId);
        if ($result['status'] != 1) {
            return $this->toError(500, '当前用户没有所属门店信息');
        }
        $store = $result['data'];
        $storeId = $store['id'];
        $storeName = $store['storeName'];

        // 查询骑手-门店车辆关系
        $SV = StoreVehicle::arrFindFirst([
            'store_id' => $storeId,
            'driver_id' => $driverId,
        ]);
        if (false === $SV){
            return $this->toError(500, '未查到绑车关系');
        }
        $vehicleId = $SV->vehicle_id;
        $serviceContractId = $SV->service_contract_id;
        // 查询是否有未完成维修单
        if ((new RentRepairData())->getUnfinishedRepairByVehicleId($vehicleId)){
            return $this->toError(500, '车辆未完成维修');
        }
        // 骑手当前套餐是否有待支付支付单
        $Unpaid = PayBill::arrFindFirstUnpaid([
            'driver_id' => $driverId,
            'service_contract_id' => $serviceContractId,
        ]);
        if ($Unpaid){
            return $this->toError(500, '骑手有待付账单');
        }
        // 查询骑手是否有待结算换电费用
        $result = $this->CallService('order', 10047, [
            'serviceContractId' => $serviceContractId,
        ], true);
        if (($result['content']['chargingCost']['unPayNum'] ?? 0) > 0){
            return $this->toError(500, '骑手待结算换电');
        }
        // 请求微服务确认还车
        $result = $this->CallService('order', 10035, [
            'serviceContractId' => $serviceContractId
        ]);
        if (200 != $result['statusCode']){
            return $this->toError(500, '还车失败');
        }
        // TODO 推送骑手还车消息
        $Vehicle = (new VehicleData())->getVehicleById($vehicleId);
        $data = [
            'bianhao' => $Vehicle['bianhao'],
            'vehicle_id' => $vehicleId,
            'storeName' => $storeName,
        ];

        (new MessagePushData())->SendMessageToDriverV2($driverId, $appCode, MessagePushData::EVENT_RETURNVEHICLE, $data);
        // 如果有联保订单，关闭联保订单
        $RentWarrantyData = new RentWarrantyData();
        $RentWarrantyData->EndServiceWarranty($serviceContractId);
        return $this->toSuccess();
    }

    /**
     * 获取车辆是否已被门店绑定
     * @param int $vehicleId 车辆ID
     * @param int $storeId 门店ID
     * @return mixed 0 请求错误  1 没有记录  2 不属于当前门店  3 准备还车  4 已经绑定门店
     */
    private function vehicleStoreBind($vehicleId = 0, $storeId = 0) {
        // 参数有效性验证
        if ($vehicleId == 0 || $storeId == 0) {
            return ['status' => 0, 'data' => '参数不能为空'];
        }

        $params = [
            "code" => 10024,
            "parameter" => [
                "vehicleId" => $vehicleId,
            ]
        ];

        // 请求微服务接口提交换电柜组状态
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        // 判断结果
        if ($result['statusCode'] == 200 && isset($result['content']['data']) && count($result['content']['data']) > 0) {
            // 判断车辆是否属于当前门店
            if ($result['content']['data'][0]['storeId'] == $storeId) {
                if ($result['content']['data'][0]['readyRentTime'] < time()
                && (time() - $result['content']['data'][0]['readyRentTime']) < 1800) {
                    return ['status' => 3, 'data' => $result['content']['data']];
                } else {
                    return ['status' => 4, 'data' => $result['content']['data']];
                }

            } else {
                return ['status' => 2, 'data' => '车辆不属于当前门店'];
            }
        } else {
            return ['status' => 1, 'data' => '获取数据失败'];
        }
    }

    /**
     * 门店信息获取
     * @return mixed
     */
    public function infoAction() {
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $store['userName'] = $this->authed->userName;
        return $this->toSuccess($store);
    }

    /**
     * 门店登陆有效性验证
     * @return mixed
     */
    public function loginCheckAction() {
        if (isset($this->authed->userId) && !empty($this->authed->userId)) {
            return $this->toSuccess();
        } else {
            return $this->toError(500, '登陆超时，重新登陆');
        }
    }

    /**
     * 门店解绑车辆
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function UnbindvehicleAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['vehicleId']) || empty($request['vehicleId'])){
            return $this->toError(500, '参数错误');
        }
        $vehicleId = $request['vehicleId'];
        // 查询车辆是否有骑手绑定
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10024",
            'parameter' => [
                'vehicleId' => $vehicleId,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '系统异常-10024');
        }
        if (!isset($result['content']['data'][0])){
            return $this->toError(500, '车辆未绑定门店');
        }
        if ($result['content']['data'][0]['driverId']>0){
            return $this->toError(500, '车辆已绑定骑手，不可解绑门店');
        }
        // 删除门店车辆关系
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10060",
            'parameter' => [
                'vehicleId' => $vehicleId,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '系统异常-10060');
        }
        return $this->toSuccess();
    }

    // 附近门店 TODO:预废弃
    public function NearbystoreAction()
    {
        if (!isset($_GET['lng']) || !isset($_GET['lat'])){
            return $this->toError(500, '参数错误');
        }
        // 参数
        $par = [
            'lng' => $_GET['lng'],
            'lat' => $_GET['lat'],
            // 状态： 1启用 2禁用
            'userStatus' => 1,
        ];
        // 门店名称搜索
        if (isset($_GET['storeName']) && !empty($_GET['storeName'])){
            $par['storeName'] = $_GET['storeName'];
        }
        //调用微服务接口查询
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10055',
            'parameter' => $par
        ],"post");
        if (200!=$result['statusCode']){
            return $this->toError(500,$result['msg']);
        }
        $businessScopeMap = [
            '1' => '维修',
            '2' => '租赁',
            '3' => '充换电',
        ];
        $data = [];
        foreach ($result['content']['stores'] as $store) {
            // 只保留客户端指定的类型
            if (empty($store['storeType']) || (isset($_GET['type'])  && !in_array($_GET['type'], $store['storeType']))){
                continue;
            }
            $list['id'] = $store['id'];
            $list['storeName'] = $store['storeName'];
            $list['lat'] = $store['lat'];
            $list['lng'] = $store['lng'];
            $list['address'] = $store['address'];
            $list['phone'] = ''==$store['linkPhone'] ? 0 : $store['linkPhone'];
            // 经营范围
            $ls = [];
            foreach ($store['storeType'] as $j) {
                if (isset($businessScopeMap[(string)$j])){
                    $ls[] = $businessScopeMap[(string)$j];
                }
            }
            $list['businessScope'] = implode(',', $ls);
            // 经营时间 暂无
            $list['bussinessHours'] = '08:00 - 20:00';
            // 门头照地址
            $list['imgUrl'] = $store['imgUrl'];
            $data[] = $list;
        }
        // 无数据不可给APP空数组
        if ([]==$data){
            $data = null;
        }
        return $this->toSuccess($data);
    }

    // 获取门店收益总计
    public function getStoreIncomeAction()
    {
        $fields = [
            'year' => '请选择年份',
            'month' => '请选择月份',
        ];
        $param = $this->getArrPars($fields, $_GET);
        $monthDate = $param['year'] . '-' . $param['month'] ;
        $timeStart = strtotime("{$monthDate}-01 00:00:00");
        $timeEnd = strtotime("{$monthDate}-01 23:59:59 +1 month -1 day");
        // 获取门店id
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $storeId = $store['id'] ?? 0;
        if (!($storeId > 0)){
            return $this->toError(500, '门店信息异常');
        }
        // 获取门店租车总金额
        $RentAmountRes  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\VehicleRentOrder','vro')
            ->andWhere('vro.store_id = :store_id: AND vro.pay_status=2 AND vro.is_delete=0',['store_id' => $storeId])
            ->andWhere('vro.pay_time >= :timeStart: AND vro.pay_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ])
            ->columns('SUM(vro.amount) as RentAmount')->getQuery()->execute()->toArray();
        $RentAmount = $RentAmountRes[0]['RentAmount'];
        // 获取门店换电总金额
        $RentAmountRes  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\ChargingOrder','co')
            ->andWhere('co.store_id = :store_id: AND co.is_delete=0',['store_id' => $storeId])
            ->andWhere('co.create_time >= :timeStart: AND co.create_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ])
            ->columns('SUM(co.amount) as ChargingAmount')->getQuery()->execute()->toArray();
        $ChargingAmount = $RentAmountRes[0]['ChargingAmount'];
        // 获取门店押金总金额
        $DepositAmountRes  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\Deposit','d')
            ->addFrom('app\models\order\ServiceContract','sc')
            ->andWhere('sc.id = d.service_contract_id AND d.status = 2')
            ->andWhere('sc.store_id = :store_id:',['store_id' => $storeId])
            ->andWhere('d.pay_time >= :timeStart: AND d.pay_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ])
            ->columns('SUM(d.amount) as DepositAmount')->getQuery()->execute()->toArray();
        $DepositAmount = $DepositAmountRes[0]['DepositAmount'];
        // 获取门店退款总金额
        $ReturnBillAmountRes  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\ReturnBill','rb')
            ->addFrom('app\models\order\ServiceContract','sc')
            // 退款状态，默认1：待审核 2：审核成功 3：审核失败 4：退款成功
            ->andWhere('sc.id = rb.service_contract_id AND rb.status = 4')
            ->andWhere('sc.store_id = :store_id:',['store_id' => $storeId])
            ->andWhere('rb.return_time >= :timeStart: AND rb.return_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ])
            ->columns('SUM(rb.amount) as ReturnBillAmount')->getQuery()->execute()->toArray();
        $ReturnBillAmount = $ReturnBillAmountRes[0]['ReturnBillAmount'];
        // 处理金额
        $RentAmount = round($RentAmount/10000, 2);
        $ChargingAmount = round($ChargingAmount/10000, 2);
        $DepositAmount = round($DepositAmount/10000, 2);
        $ReturnBillAmount = round($ReturnBillAmount/10000, 2);
        $data = [
            'AllAmount' => $RentAmount + $ChargingAmount + $DepositAmount,
            'TotalRevenue' => $RentAmount + $ChargingAmount + $DepositAmount,
            'TotalDisbursement' => $ReturnBillAmount,
            'RentAmount' => $RentAmount,
            'ChargingAmount' => $ChargingAmount,
            'DepositAmount' => $DepositAmount,
            'ReturnBillAmount' => $ReturnBillAmount,
        ];
        return $this->toSuccess($data);
    }

    // 门店租车收益
    public function getStoreRentVehicleIncomeAction()
    {
        $pageSize = $_GET['pageSize'] ?? 20;
        $pageNum = $_GET['pageNum'] ?? 1;
        $fields = [
            'year' => '请选择年份',
            'month' => '请选择月份',
        ];
        $param = $this->getArrPars($fields, $_GET);
        $monthDate = $param['year'] . '-' . $param['month'] ;
        $timeStart = strtotime("{$monthDate}-01 00:00:00");
        $timeEnd = strtotime("{$monthDate}-01 23:59:59 +1 month -1 day");
        // 获取门店id
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $storeId = $store['id'] ?? 0;
        if (!($storeId > 0)){
            return $this->toError(500, '门店信息异常');
        }
        $model  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\VehicleRentOrder','vro')
            ->andWhere('vro.store_id = :store_id: AND vro.pay_status=2 AND vro.is_delete=0',['store_id' => $storeId])
            ->andWhere('vro.pay_time >= :timeStart: AND vro.pay_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ]);
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(*) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        $list = $model->columns('vro.driver_id, vro.amount, vro.pay_status AS payStatus, vro.pay_time AS payTime')
            ->orderBy('vro.pay_time DESC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()->execute()->toArray();
        // 获取骑手idList
        $driverIdList = [];
        foreach ($list as $item){
            $driverIdList[] = $item['driver_id'];
        }
        $driverRealNames = [];
        if (!empty($driverIdList)){
            $driverRealNames = (new DriverData())->getDriverRealNameByIds($driverIdList);
        }
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        // 处理租赁金额
        foreach ($list as $k => $item){
            $list[$k]['amount'] = round($item['amount']/10000, 2);
            $list[$k]['realName'] = $driverRealNames[$item['driver_id']] ?? '';
        }
        return $this->toSuccess($list,$meta);
    }

    // 门店换电列表
    public function getStoreChargingIncomeAction()
    {
        $pageSize = $_GET['pageSize'] ?? 20;
        $pageNum = $_GET['pageNum'] ?? 1;
        $fields = [
            'year' => '请选择年份',
            'month' => '请选择月份',
        ];
        $param = $this->getArrPars($fields, $_GET);
        $monthDate = $param['year'] . '-' . $param['month'] ;
        $timeStart = strtotime("{$monthDate}-01 00:00:00");
        $timeEnd = strtotime("{$monthDate}-01 23:59:59 +1 month -1 day");
        // 获取门店id
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $storeId = $store['id'] ?? 0;
        if (!($storeId > 0)){
            return $this->toError(500, '门店信息异常');
        }
        $model  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\ChargingOrder','co')
            ->andWhere('co.store_id = :store_id: AND co.is_delete=0',['store_id' => $storeId])
            ->andWhere('co.create_time >= :timeStart: AND co.create_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ]);
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(*) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        $list = $model->columns('co.driver_id, co.amount, co.cabinet_id, co.pay_status AS payStatus, co.create_time AS createTime, co.qrcode')
                ->orderBy('co.create_time DESC')
                ->limit($pageSize, ($pageNum-1)*$pageSize)
                ->getQuery()->execute()->toArray();
        // 获取骑手idList
        $driverIdList = [];
        // 获取换电柜idList
//        $cabinetIdList =[];
        foreach ($list as $item){
            $driverIdList[] = $item['driver_id'];
//            $cabinetIdList[] = $item['cabinet_id'];
        }
        $driverRealNames = [];
        if (!empty($driverIdList)){
            $driverRealNames = (new DriverData())->getDriverRealNameByIds($driverIdList);
        }
//        $cabinetQRCode = [];
//        if (!empty($cabinetIdList)){
//            $cabinetQRCode = $this->getCabinetQRCodeByIds($cabinetIdList);
//        }
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        // 处理换电金额
        foreach ($list as $k => $item){
            $list[$k]['amount'] = round($item['amount']/10000, 2);
            $list[$k]['realName'] = $driverRealNames[$item['driver_id']] ?? '';
            // $list[$k]['qrcode'] = $cabinetQRCode[$item['cabinet_id']] ?? '';
        }
        return $this->toSuccess($list,$meta);
    }

    // 门店押金列表
    public function getStoreDepositIncomeAction()
    {
        $pageSize = $_GET['pageSize'] ?? 20;
        $pageNum = $_GET['pageNum'] ?? 1;
        $fields = [
            'year' => '请选择年份',
            'month' => '请选择月份',
        ];
        $param = $this->getArrPars($fields, $_GET);
        $monthDate = $param['year'] . '-' . $param['month'] ;
        $timeStart = strtotime("{$monthDate}-01 00:00:00");
        $timeEnd = strtotime("{$monthDate}-01 23:59:59 +1 month -1 day");
        // 获取门店id
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $storeId = $store['id'] ?? 0;
        if (!($storeId > 0)){
            return $this->toError(500, '门店信息异常');
        }
        $model  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\Deposit','d')
            ->addFrom('app\models\order\ServiceContract','sc')
            ->andWhere('sc.id = d.service_contract_id AND d.status = 2')
            ->andWhere('sc.store_id = :store_id:',['store_id' => $storeId])
            ->andWhere('d.pay_time >= :timeStart: AND d.pay_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ]);
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(*) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        $list = $model->columns('sc.driver_id, d.amount, d.status,  d.pay_time AS payTime, d.create_time AS createTime')
            ->orderBy('d.pay_time DESC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()->execute()->toArray();
        // 获取骑手idList
        $driverIdList = [];
        foreach ($list as $item){
            $driverIdList[] = $item['driver_id'];
        }
        $driverRealNames = [];
        if (!empty($driverIdList)){
            $driverRealNames = (new DriverData())->getDriverRealNameByIds($driverIdList);
        }
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        // 处理换电金额
        foreach ($list as $k => $item){
            $list[$k]['amount'] = round($item['amount']/10000, 2);
            $list[$k]['realName'] = $driverRealNames[$item['driver_id']] ?? '';
        }
        return $this->toSuccess($list,$meta);
    }

    // 门店退款列表
    public function getStoreReturnBillAction()
    {
        $pageSize = $_GET['pageSize'] ?? 20;
        $pageNum = $_GET['pageNum'] ?? 1;
        $fields = [
            'year' => '请选择年份',
            'month' => '请选择月份',
        ];
        $param = $this->getArrPars($fields, $_GET);
        $monthDate = $param['year'] . '-' . $param['month'] ;
        $timeStart = strtotime("{$monthDate}-01 00:00:00");
        $timeEnd = strtotime("{$monthDate}-01 23:59:59 +1 month -1 day");
        // 获取门店id
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $storeId = $store['id'] ?? 0;
        if (!($storeId > 0)){
            return $this->toError(500, '门店信息异常');
        }
        $model  = $this->modelsManager->createBuilder()
            ->addFrom('app\models\order\ReturnBill','rb')
            ->addFrom('app\models\order\ServiceContract','sc')
            // 退款状态，默认1：待审核 2：审核成功 3：审核失败 4：退款成功
            ->andWhere('sc.id = rb.service_contract_id AND rb.status = 4')
            ->andWhere('sc.store_id = :store_id:',['store_id' => $storeId])
            ->andWhere('rb.return_time >= :timeStart: AND rb.return_time <= :timeEnd:',[
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ]);
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(*) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        $list = $model->columns('sc.driver_id, rb.amount, rb.return_time AS returnTime, rb.deposit_amount AS depositAmount, rb.rent_amount AS rentAmount')
            ->orderBy('rb.return_time DESC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()->execute()->toArray();
        // 获取骑手idList
        $driverIdList = [];
        foreach ($list as $item){
            $driverIdList[] = $item['driver_id'];
        }
        $driverRealNames = [];
        if (!empty($driverIdList)){
            $driverRealNames = (new DriverData())->getDriverRealNameByIds($driverIdList);
        }
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        // 处理换电金额
        foreach ($list as $k => $item){
            $list[$k]['amount'] = round($item['amount']/10000, 2);
            $list[$k]['depositAmount'] = round($item['depositAmount']/10000, 2);
            $list[$k]['rentAmount'] = round($item['rentAmount']/10000, 2);
            $list[$k]['realName'] = $driverRealNames[$item['driver_id']] ?? '';
        }
        return $this->toSuccess($list,$meta);
    }

    // 门店租车数量统计
    public function VehicleRentQuantityAction()
    {
        // 获取门店id
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $storeId = $store['id'] ?? 0;
        if (!$storeId){
            return $this->toError(500, '门店信息异常，请尝试重新登录');
        }
        $totalVehicle = StoreVehicle::count($this->arrToQuery([
            'store_id' => $storeId,
            'vehicle_id' => ['>',0]
        ]));
        $rentVehicle = StoreVehicle::count($this->arrToQuery([
            'store_id' => $storeId,
            'vehicle_id' => ['>',0],
            'rent_status' => ['IN', [2,3]],
        ]));
        $data = [
            'totalVehicle' => $totalVehicle,
            'rentVehicle' => $rentVehicle,
        ];
        return $this->toSuccess($data);
    }

    // 获取换电柜QRCode 通过换电柜idList
    private function getCabinetQRCodeByIds($cabinetIdList){
        if (!is_array($cabinetIdList)){
            $cabinetIdList = [$cabinetIdList];
        }
        // 去除0 null和重复值
        $cabinetIdList = array_values(array_unique(array_diff($cabinetIdList,[0, null])));
        if (empty($cabinetIdList)){
            return [];
        }
        $cabinets = Cabinet::arrFind([
            'id' => ['IN', $cabinetIdList]
        ], 'and', [
            'columns' => 'id, qrcode'
        ]);
        $cabinetQRCode = [];
        foreach ($cabinets as $cabinet){
            $cabinetQRCode[$cabinet['id']] = $cabinet['qrcode'];
        }
        return $cabinetQRCode;
    }

    /**
     * 每天的汇总记录
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     */
    public function InstallCountAction()
    {
        $json['endTime'] = (int)$this->request->getQuery('endTime','int',0);
        $json['startTime'] = $this->request->getQuery('startTime','int',0);
        $json['querySource'] = $this->request->getQuery('querySource','int',2);

        // 获取当前用户信息
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $json['storeId'] = $store['id'] ?? 0;
        if (!($json['storeId'] > 0)){
            return $this->toError(500, '门店信息异常');
        }

        $json = array_filter($json);
        $data = [
            'parameter' => $json,
            'code' => 11059,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : [] ,
            isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : []);
    }

    /**
     * 门店安装记录
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     */
    public function EverydayRecordAction()
    {
        $json['installTime'] = (int)$this->request->getQuery('installTime','int',0);

        // 获取当前用户信息
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $json['storeId'] = $store['id'] ?? 0;
        if (!($json['storeId'] > 0)){
            return $this->toError(500, '门店信息异常');
        }

        $json = array_filter($json);
        $data = [
            'parameter' => $json,
            'code' => 11060,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : [] ,
            isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : []);
    }

    /**
     * 通过车架号获取快递公司信息
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     */
    public function CompanyAction()
    {
        $json['vin'] = $this->request->getQuery('vin','string','');
        if (!$json['vin']) {
            return $this->toError(500, '请扫描车架号信息');
        }
        // 获取当前用户信息
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $json['storeId'] = $store['id'] ?? 0;
        if (!($json['storeId'] > 0)){
            return $this->toError(500, '门店信息异常');
        }

        $json = array_filter($json);
        $data = [
            'parameter' => $json,
            'code' => 11057,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : []);
    }

    /**
     * 获取快递公司的品牌ID
     */
    public function BrandAction()
    {
        $json['insId'] = $this->request->getQuery('insId','int','');
        if (!$json['insId']) {
            return $this->toError(500, '缺少快递公司ID');
        }

        $data = [
            'parameter' => $json,
            'code' => 11067,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : []);
    }

    /**
     * 获取品牌下的型号ID
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\MicroException
     */
    public function ProductSkuAction()
    {
        $json['brandId'] = $this->request->getQuery('brandId','int',0);
        $json['insId'] = $this->request->getQuery('insId','int',0);
        if (!$json['brandId']) {
            return $this->toError(500, '请选择品牌ID');
        }
        if (!$json['insId']) {
            return $this->toError(500, '缺少快递公司ID');
        }

        $data = [
            'parameter' => $json,
            'code' => 11068,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : []);
    }

    /**
     * 三码或者四码合一
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\AppException
     * @throws \app\common\errors\MicroException
     */
    public function CombineAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'insId', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'udid', 'type' => 'string', 'parameter' => ['default' => true]],
            ['key' => 'vin', 'type' => 'string', 'parameter' => ['default' => true]],
//            ['key' => 'model', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'deviceModelId', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'vehicleModelId', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'qrcode', 'type' => 'string', 'parameter' => ['default' => true]],
            ['key' => 'plateNum', 'type' => 'string', 'parameter' => ['default' => false]],
//            ['key' => 'productId', 'type' => 'number', 'parameter' => ['default' => true]],
//            ['key' => 'productSkuRelationId', 'type' => 'number', 'parameter' => ['default' => true]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $result['content']['storeId'] = $store['id'] ?? 0;
        if (!( $result['content']['storeId'] > 0)){
            return $this->toError(500, '门店信息异常');
        }

        $data = [
            'parameter' =>  $result['content'],
            'code' => 11065,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }

    /**
     * 硬件检测
     */
    public function CheckDeviceAction()
    {
        $json['udid'] = $this->request->getQuery('udid','string','');
        $json['deviceModelId'] = $this->request->getQuery('deviceModelId','int','');
        if (!$json['udid']) {
            return $this->toError(500, '请扫描设备号');
        }
        if (!$json['deviceModelId']) {
            return $this->toError(500, '识别不到设备号');
        }
        $raw_token = $this->request->getHeader(AuthService::JWT_HEADER_KEY);
        $json['token'] = $raw_token;
        $this->logger->info('token'. $raw_token);
        $json = array_filter($json);
        $data = [
            'parameter' => $json,
            'code' => 11066,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : []);
    }
}

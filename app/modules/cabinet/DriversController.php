<?php
namespace app\modules\cabinet;

use app\common\errors\AppException;
use app\common\logger\FileLogger;
use app\models\CabinetAllRoom;
use app\models\order\ServiceContract;
use app\models\service\StoreVehicle;
use app\models\Cabinet;
use app\models\CabinetRoom;
use app\modules\BaseController;
use app\services\data\StoreData;
use function PHPSTORM_META\map;
use PHPUnit\Framework\Constraint\IsFalse;
use app\services\data\CabinetData;
use app\services\data\BillData;

/**
 * Class AdminController
 * 换电柜管理后台API类
 * @Author Lishiqin
 * @package app\modules\microprograms
 */
class DriversController extends BaseController {

    // Error 文案
    const ERROR_MSG = [
        101 => '尊敬的用户，检测未有关闭的仓门，请检查仓门关闭状态，确认关闭后在进行操作。',
        102 => '尊敬的用户，此换电柜未有满电电池，请扫描其他换电柜。',
        103 => '尊敬的用户，此换电柜处于异常状态，请联系400-006-006处理。',
        104 => '尊敬的用户，仓门打开失败，请重新扫码。',
        105 => '尊重的用户，当前查询不到该柜组编号的任何记录',
        106 => '尊敬的用户，您的账号暂未绑定任何门店,请绑定门店后在进行操作。',
        201 => '尊敬的用户，此换电柜正在被使用，请等待前一位用户操作完成，再继续扫码使用，谢谢合作。',
        202 => '尊敬的用户，您的操作超时，如需要继续操作，请重新扫码。',
    ];

    // 轮询请求类型
    const ACTION_TYPE_ONE   = 1; // 获取空仓是否正常打开
    const ACTION_TYPE_TWO   = 2; // 获取空仓是否放入电池并开始充电，柜门是否已经关闭，完成后开启满电柜门并判断是否已经正常打开
    const ACTION_TYPE_THREE = 3; // 获取满电仓是否已经关闭柜门

    // 轮询请求状态
    const ACTION_STATUS_ONE   = 1; // 没有符合完成本次请求条件，继续轮询
    const ACTION_STATUS_TWO   = 2; // 已经符合请求条件，并继续等待后续条件完成
    const ACTION_STATUS_THREE = 3; // 完成所有条件，结束轮询
    const ACTION_STATUS_FOUR  = 4; // 发生异常情况，结束轮询

    // 仓门状态
    const DOOR_STATUS_OPEN  = 0;  // 开门
    const DOOR_STATUS_CLOSE = 1;  // 关门
    const DOOR_STATUS_ERROR = -1; // 异常

    // 电池状态
    const BATTERY_FULL     = 0;  // 满电
    const BATTERY_CHARGING = 1;  // 充电中
    const BATTERY_ERROR    = -1; // 异常

    // 指令操作类型
    const OPERATION_OPEN_DOOR      = 1;
    const OPERATION_CHARGING_START = 2;
    const OPERATION_CHARGING_END   = 3;

    // 电池操作类型
    const BATTERY_PUSH = 1;
    const BATTERY_PULL = 2;

    // 省市区三级
    const DEEP_PROVINCE = 1;
    const DEEP_CITY     = 2;
    const DEEP_AREA     = 3;

    // 柜子操作状态
    const ROOM_OPEN_DEFAULT = 1;
    const ROOM_PUSH_EMPTY = 2;
    const ROOM_OPEN_FULL = 3;
    const ROOM_PULL_FULL = 4;
    const ROOM_NO_USE = 5;

    // 返回页面
    const PAGE_OPEN = 1;
    const PAGE_PUSH = 2;
    const PAGE_PULL = 3;
    const PAGE_END = 4;
    const PAGE_TIMEOUT = 5;
    const PAGE_ERROR = 6;

    /** TODO:预废弃接口 新接口 \driversapp\IndexController.php#StoreMapAction
     * 骑手首页地图信息获取API
     * @method POST
     * province 当前省份（必填）
     * city     当前城市（必填）
     */
//    public function MapAction()
//    {
//        $request = $this->request->getJsonRawBody();
//        $province = isset($request->province) ? $request->province : '';
//        $city     = isset($request->city) ? $request->city : '';
//
//        if (empty($province) && empty($city)) {
//            return $this->toError(500, '省市不能为空');
//        }
//
//        $provinceId = $this->seachArea($province, self::DEEP_PROVINCE);
//        $cityId       = $this->seachArea($city, self::DEEP_CITY);
//        // 获取门店数据
//        $result = $this->curl->httpRequest($this->Zuul->user, [
//            'code' => 10057,
//            'parameter' => [
//                'provinceId'  => $provinceId,
//                'cityId'      => $cityId,
//                // 是否启用 1启用 2禁用
//                'userStatus'      => 1,
//                'pageSize'    => 100,
//                'pageNum'     => 1
//            ]
//        ], "POST");
//        // 失败返回
//        if (200!=$result['statusCode']){
//            return $this->toError(500, '服务异常-10057');
//        }
//        // 定义侧边栏内容
//        $bar = [
//            [
//                'type' => StoreData::RentService,
//                'name' => '租车',
//                'objName' => 'RentPoint',
//                'action' => false,
//            ],
//            [
//                'type' => StoreData::BatteryService,
//                'name' => '充电',
//                'objName' => 'BatteryPoint',
//                'action' => true,
//            ],
//            [
//                'type' => StoreData::RepairService,
//                'name' => '维修',
//                'objName' => 'RepairPoint',
//                'action' => false,
//            ],
//        ];
//        if ('com.e_dewin.android.lease.rider.shunfeng'==$this->request->getHeader('packageName')){
//            $bar = [
//                [
//                    'type' => StoreData::RepairService,
//                    'name' => '维修',
//                    'objName' => 'RepairPoint',
//                    'action' => true,
//                ],
//            ];
//        }
//        // 为侧边栏属性创建空数组
//        foreach ($bar as $value){
//            $list[$value['objName']] = [];
//        }
//        // 如果没有数据
//        if (!isset($result['content']['stores'][0]) || !isset($list)){
//            return $this->toSuccess([
//                'bar' => $bar,
//                'list' => $list,
//            ]);
//        }
//        $storeList = $result['content']['stores'];
//        // 门店经营类型1：维修 2:租赁 3：充换电 改为从bar中动态获取可用范围
//        $businessScopeMap = [];
//        foreach ($bar as $item){
//            $businessScopeMap[(string)$item['type']] = $item['name'];
//        }
//        // 门店idList
//        $storeIdList = array_map(function ($v){
//            return $v['id'];
//        }, $storeList);
//        $storeData = new StoreData();
//        // 查询门店可用车辆
//        $storeUseVehicle = $storeData->getVehicleNumByStoreIds($storeIdList, StoreData::NotRented);
//        /*
//        $businessScopeMap = [
//            '1' => '维修',
//            '2' => '租赁',
//            '3' => '充换电',
//        ];*/
//        // 查询门店可用电池数量
//        $storeFullBattery = $storeData->getFullBatteryNumByStoreIds($storeIdList);
////        if (isset($businessScopeMap[(string) StoreData::BatteryService])){
////        }
//        // 定义字段
//        $fields = ['id','storeName','address','lng','lat','imgUrl','linkMan','linkPhone','linkTel','storeType'];
//        // 处理门店数据
//        foreach ($storeList as $value){
//            // 如果门店没有类型 过
//            if (!isset($value['storeType'])) continue;
//            $tmp = [];
//            // 存储预定义字段
//            foreach ($fields as $field){
//                $tmp[$field] = $value[$field];
//            }
//            // 经营时间暂无
//            $tmp['bussinessHours'] = '08:00 - 20:00';
//
//            // 处理门店经营范围 及 换电租车信息
//            $ls = [];
//            foreach ($value['storeType'] as $j) {
//                if (isset($businessScopeMap[(string)$j])){
//                    $ls[] = $businessScopeMap[(string)$j];
//                }
//            }
//            // 经营范围描述
//            $tmp['businessScope'] = implode(',', $ls);
//            // 维修服务
//            if (in_array(StoreData::RepairService, $value['storeType'])){
//                $tmp['repair'] = 0; // 兼容APP
//            }
//            // 租赁服务
//            if (in_array(StoreData::RentService, $value['storeType'])){
//                $tmp['vehicle'] = $storeUseVehicle[$value['id']] ?? 0;
//            }
//            // 换电服务
//            if (in_array(StoreData::BatteryService, $value['storeType'])){
//                $tmp['battery'] = $storeFullBattery[$value['id']] ?? 0;
//            }
//
//            // 放到对应门店对象
//            foreach ($bar as $item){
//                if (in_array($item['type'], $value['storeType'])){
//                    $list[$item['objName']][] = $tmp;
//                }
//            }
//        }
//        return $this->toSuccess([
//            'bar' => $bar,
//            'list' => $list,
//        ]);
//    }

//
//    /**
//     * 骑手扫码开启柜门API
//     * @method POST
//     * @param string qrcode 换电柜编号（必填）
//     * @return mixed
//     */
//    public function ScanAction()
//    {
//        $driverId = $this->authed->userId;
//        $request = $this->request->getJsonRawBody();
//        $qrcode = isset($request->qrcode) ? $request->qrcode : '';
//
//        if (!isset($this->authed->userId) || $this->authed->userId <= 0) {
//            return $this->toError(500, '获取用户信息失败');
//        }
//        // 查询骑手待支付账单
//        $bill = (new BillData())->getUnpaidBillByDriverId($driverId);
//        // 如果骑手有待支付账单，返回支付单编号
//        if ($bill){
//            return $this->toSuccess([
//                'status' => 3,
//                'businessSn' => $bill['business_sn'],
//            ]);
//        }
//        // 查询是否有未支付的换电单 返回待支付换电单号
//        $ChargingOrders = (new CabinetData())->getUnpaidChargingOrdersBydriverId($driverId);
//        if ($ChargingOrders){
//            return $this->toSuccess([
//                'status' => 2,
////                'chargingSn' => $ChargingOrders[0]['chargingSn'],
//            ]);
//        }
//        $order = $this->orderInfo($this->authed->userId);
//
//        // 判断是否有契约订单信息
//        if ($order == false) {
//            return $this->toError(500, '没有契约订单，不能更换电池');
//        }
//
//        // 判断套餐是否存续
//        if (2!=$order['serviceContract']['status']){
//            return $this->toError(500,'服务套餐不在存续状态，不可使用服务');
//        }
//
//        if (!isset($order['productPackage']['packageId']) || empty($order['productPackage']['packageId'])) {
//            return $this->toError(500,'没有服务套餐，不能更换电池');
//        }
//
//        // 存在契约订单信息，并通过packageIds获取商品套餐详情列表
//        $packageIds[] = $order['productPackage']['packageId'];
//        $params = [
//            'code' => 10008,
//            'parameter' => [
//                'packageIds' => $packageIds
//            ]
//        ];
//
//        // 判断获取商品套餐详情列表的结果
//        $result = $this->curl->httpRequest($this->Zuul->order, $params, "post");
//
//        if ($result['statusCode'] != 200 || !isset($result['content']['productPackageDetails'][0]['serviceItems'][0])) {
//            return $this->toError(500, '没有换电服务类型，不能提供更换电池服务');
//        }
//
//        // 判断商品套餐详情中是否存在换电服务类型
//        $rule = false;
//        foreach ($result['content']['productPackageDetails'][0]['serviceItems'] as $key => $value) {
//            if ($value['serviceItemType'] == 2) {
//                $rule = true;
//            }
//        }
//        if (!$rule) {
//            return $this->toError(500, '没有换电服务类型，不能更换电池');
//        }
//
//        $order = $this->orderInfoContent($this->authed->userId);
//
//        // 针对有租车订单的客户进行判断
//        if (isset($order['rentOrder'])) {
//            if ($order['rentOrder']['payStatus'] != 2) {
//                return $this->toError(500, '租车订单未支付，不能换电池');
//            }
//
//            if ($order['rentOrder']['vehicleId'] <= 0) {
//                return $this->toError(500, '租车订单未绑定车辆，不能换电池');
//            }
//
//            if ($order['rentOrder']['endTime'] < time()) {
//                return $this->toError(500, '租车订单已经过期，不能换电池');
//            }
//        }
//
//        // 传递的参数有效性判断
//        if (!$qrcode) {
//            return $this->toError(500, '二维码不能为空');
//        }
//
//        $doorsInfo = $this->checkDoors($qrcode);
//        if (!$doorsInfo) {
//            return $this->toError(500, '请关闭所有柜门，重新扫码');
//        }
//
//        $cabinet = $this->cabinetInfo($qrcode);
//        if ($cabinet['status']) {
//            if ($cabinet['data']['heartbeatTime'] + 600 < time() || $cabinet['data']['status'] == 2) {
//                return $this->toError(500, '换电柜暂停服务，请重新扫码');
//            }
//        } else {
//            return $this->toError(500, '换电柜状态获取失败');
//        }
//
//
//        // 判断默认的空柜是否正常
//        $result = $this->openDefaultRoom($qrcode);
//        if (!$result['status']) {
//            return $this->toError(500, $result['msg']);
//        }
//
//        $roomNum = $result['roomNum'];
//
//        // 判断是否有可用的满电柜
//        $fullRoomNum = $this->openFullRoom($qrcode);
//        if (!$fullRoomNum) {
//            return $this->toError(500, '没有可用的满电电池，无法提供服务');
//        }
//
//        // 打开默认的柜门
//        $result = $this->roomController($roomNum, $qrcode, self::OPERATION_OPEN_DOOR, self::BATTERY_PUSH);
//
//        if (!$result) {
//            return $this->toError(500, '柜门打开失败');
//        }
//
//
//        $data['status'] = 1;
//        $data['roomNum'] = $roomNum;
//        return $this->toSuccess($data);
//    }
//
//    /**
//     * 骑手扫码轮询接口API
//     * @method POST
//     * @param int    roomNum    柜门编号（必填）
//     * @param string qrcode     换电柜编号（必填）
//     * @param int    status     当前轮询状态码 1（默认） 未满足条件继续轮询 2 有后续操作需要继续等待 3 条件完成结束本阶段 4 条件异常结束本阶段（必填）
//     * @param int    actionType 当前阶段 1 等待空柜门打开 2 等待骑手放入电池并开始充电后打开满电柜门 3 满电柜电池取出后是否已经关门（必填）
//     * @return mixed
//     */
//    public function ResultAction()
//    {
//        $request    = $this->request->getJsonRawBody();
//        $roomNum    = isset($request->roomNum) ? $request->roomNum : 0;
//        $qrcode     = isset($request->qrcode) ? $request->qrcode : '';
//        $status     = isset($request->status) ? $request->status : 0;
//        $actionType = isset($request->actionType) ? $request->actionType : 0;
//
//        if (empty($qrcode) || $roomNum == 0) {
//
//            return $this->toError(500, '换电柜编号或柜门编号不能为空');
//        }
//
//        if ($status == 0 || $actionType == 0) {
//            return $this->toError(500, '当前请求类型或状态无效');
//        }
//
//        // 根据请求的类型进行相应的处理
//        switch ($actionType) {
//            // 等待空柜门打开
//            case self::ACTION_TYPE_ONE:
//                $this->actionTypeOne($roomNum, $qrcode, $status);
//                break;
//            // 等待骑手放入电池并开始充电后打开满电柜门
//            case self::ACTION_TYPE_TWO:
//                $this->actionTypeTwo($roomNum, $qrcode, $status);
//                break;
//            // 满电柜电池取出后是否已经关门
//            case self::ACTION_TYPE_THREE:
//                $this->actionTypeThree($roomNum, $qrcode, $status);
//                break;
//            default:
//                return $this->toError(500, '请求类型无效');
//        }
//
//    }
//
//
//    /**
//     * 阶段一：判断扫码换电的空柜是否已经打开
//     * @param int    $roomNum 柜门编号
//     * @param string $qrcode  换电柜编号
//     * @param int    $status  当前请求状态
//     * @return mixed
//     */
//    private function actionTypeOne($roomNum, $qrcode, $status)
//    {
//        $data['status'] = $status;
//        $data['roomNum'] = $roomNum;
//
//        // 判断请求状态有效性
//        if ($status != self::ACTION_STATUS_ONE) {
//            return $this->toError(500, '请求状态无效');
//        }
//
//        // 获取柜子状态
//        $room = $this->roomStatus($qrcode, $roomNum);
//
//        // 业务判断【异常】：物理锁或充电状态出现 -1
//        if ($room['doorStatus'] == self::DOOR_STATUS_ERROR || $room['batteryStatus'] == self::BATTERY_ERROR) {
//            $data['status'] = self::ACTION_STATUS_FOUR;
//            $data['msg'] = '柜子物理锁或充电发生异常，打开失败';
//        }
//
//        // 业务判断【完成】：柜子已经打开且充电状态为已满
//        if ($room['doorStatus'] == self::DOOR_STATUS_OPEN && $room['batteryStatus'] == self::BATTERY_FULL) {
//
//            // 柜子断电指令
////            $result = $this->roomController($roomNum, $qrcode, self::OPERATION_CHARGING_END, self::BATTERY_PUSH);
////            if (!$result) {
////                return $this->toError(500, '柜子断开充电失败');
////            }
//
//
//            // 修改旧的默认空柜子的记录信息
//            $params = [
//                'roomNum' => $roomNum,
//                'openTime' => time(),
//                'getTime' => time()
//            ];
//            if (!$this->roomUpdate($qrcode, $params)) {
//                $data['status'] = self::ACTION_STATUS_FOUR;
//                $data['msg'] = '柜子状态修改失败';
//            } else {
//                $data['status'] = self::ACTION_STATUS_THREE;
//            }
//        }
//
//        // 返回结果
//        return $this->toSuccess($data);
//    }
//
//    /**
//     * 阶段二：判断骑手是否放入空电池关闭柜门并进入充电状态，发送开启满电柜门并判断柜门是否打开
//     * @param int    $roomNum 柜门编号
//     * @param string $qrcode  换电柜编号
//     * @param int    $status  当前请求状态
//     * @return mixed
//     */
//    private function actionTypeTwo($roomNum, $qrcode, $status)
//    {
//        $data['status'] = $status;
//        $data['roomNum'] = $roomNum;
//
//        // 获取柜子状态
//        $room = $this->roomStatus($qrcode, $roomNum);
//
//        switch ($status) {
//            // 判断骑手是否关闭仓门且电池开始充电
//            case self::ACTION_STATUS_ONE:
//                // 业务判断【异常】：物理锁或充电状态出现 -1
//                if ($room['doorStatus'] == self::DOOR_STATUS_ERROR || $room['batteryStatus'] == self::BATTERY_ERROR) {
//                    $data['status'] = self::ACTION_STATUS_FOUR;
//                    $data['msg'] = '柜子物理锁或充电发生异常';
//                }
//
//                // 如果门已经关闭，开启充电指令
//                if ($room['doorStatus'] == self::DOOR_STATUS_CLOSE && $room['batteryStatus'] == self::BATTERY_FULL) {
//                    $this->roomController($roomNum, $qrcode, self::OPERATION_CHARGING_START, self::BATTERY_PUSH);
//                }
//
//                // 业务判断【完成】：柜子已经关闭且开始充电
//                if ($room['doorStatus'] == self::DOOR_STATUS_CLOSE && $room['batteryStatus'] == self::BATTERY_CHARGING) {
//                    $data['status'] = self::ACTION_STATUS_TWO;
//
//                    // 记录旧的柜门编号
//                    $oldRoomNum = $roomNum;
//
//                    // 获取可以打开的满电柜子
//                    $roomNum = $this->openFullRoom($qrcode);
//                    if (!$roomNum) {
//                        $data['status'] = self::ACTION_STATUS_FOUR;
//                        $data['msg'] = '没有可用的满电柜子';
//                    }
//
//                    // 创建换电订单
//                    $result = $this->order($qrcode);
//                    if ($result['status'] == 0) {
//                        return $this->toError(500, $result['msg']);
//                    } else {
//                        // 打开默认的柜门
//                        $result = $this->roomController($roomNum, $qrcode, self::OPERATION_OPEN_DOOR, self::BATTERY_PULL);
//
//                        if (!$result) {
//                            $data['status'] = self::ACTION_STATUS_FOUR;
//                            $data['msg'] = '柜子打开指令失败';
//                        }
//                    }
//
//                    // 修改旧的默认空柜子的记录信息
//                    $params = [
//                        'roomNum' => $oldRoomNum,
//                        'isEmpty' => 0,
//                        'putTime' => time(),
//                        'closeTime' => time()
//                    ];
//
//                    if (!$this->roomUpdate($qrcode, $params)) {
//                        $data['status'] = self::ACTION_STATUS_FOUR;
//                        $data['msg'] = '柜子状态修改失败';
//                    } else {
//                        // 修改新的默认空柜子的记录信息
//                        $params = [
//                            'roomNum' => $roomNum,
//                            'isEmpty' => 1,
//                        ];
//                        if (!$this->roomUpdate($qrcode, $params)) {
//                            $data['status'] = self::ACTION_STATUS_FOUR;
//                            $data['msg'] = '柜子状态修改失败';
//                        } else {
//                            $data['roomNum'] = $roomNum;
//                        }
//                    }
//                }
//                break;
//
//            // 判断满电得仓门是否打开
//            case self::ACTION_STATUS_TWO:
//                // 业务判断【异常】：物理锁或充电状态出现 -1
//                if ($room['doorStatus'] == self::DOOR_STATUS_ERROR || $room['batteryStatus'] == self::BATTERY_ERROR) {
//                    $data['status'] = self::ACTION_STATUS_FOUR;
//                    $data['msg'] = '柜子物理锁或充电发生异常';
//                }
//                // 业务判断【完成】：柜子已经打开且为充满电状态
//                if ($room['doorStatus'] == self::DOOR_STATUS_OPEN && $room['batteryStatus'] == self::BATTERY_FULL) {
//
//                    // 断开充电状态
////                    $result = $this->roomController($roomNum, $qrcode, self::OPERATION_CHARGING_END, self::BATTERY_PULL);
////                    if (!$result) {
////                        return $this->toError(500, '柜子断开充电失败');
////                    }
//
//                    // 修改柜子的记录信息
//                    $params = [
//                        'roomNum' => $roomNum,
//                        'openTime' => time(),
//                        'getTime' => time()
//                    ];
//                    if (!$this->roomUpdate($qrcode, $params)) {
//                        $data['status'] = self::ACTION_STATUS_FOUR;
//                        $data['msg'] = '柜子状态修改失败';
//                    }
//
//                    $data['status'] = self::ACTION_STATUS_THREE;
//                }
//                break;
//            default:
//                return $this->toError(500, '请求状态无效');
//        }
//
//        // 返回结果
//        return $this->toSuccess($data);
//    }
//
//    /**
//     * 阶段三：判断骑手取走满电电池后是否关闭柜门
//     * @param int    $roomNum 柜门编号
//     * @param string $qrcode  换电柜编号
//     * @param int    $status  当前请求状态
//     * @return mixed
//     */
//    private function actionTypeThree($roomNum, $qrcode, $status)
//    {
//        $data['status'] = $status;
//        $data['roomNum'] = $roomNum;
//
//        // 判断请求状态有效性
//        if ($status != self::ACTION_STATUS_ONE) {
//            return $this->toError(500, '请求状态无效');
//        }
//
//        // 获取柜子状态
//        $room = $this->roomStatus($qrcode, $roomNum);
//
//        // 业务判断【异常】：物理锁或充电状态出现 -1
//        if ($room['doorStatus'] == self::DOOR_STATUS_ERROR || $room['batteryStatus'] == self::BATTERY_ERROR) {
//            $data['status'] = self::ACTION_STATUS_FOUR;
//            $data['msg'] = '柜子物理锁或充电发生异常';
//        }
//
//        // 业务判断【完成】：柜门已经关闭且为充满电状态
//        if ($room['doorStatus'] == self::DOOR_STATUS_CLOSE && $room['batteryStatus'] == self::BATTERY_FULL) {
//            // 修改柜子的记录信息
//            $params = [
//                'roomNum' => $roomNum,
//                'closeTime' => time(),
//            ];
//            if (!$this->roomUpdate($qrcode, $params)) {
//                $data['status'] = self::ACTION_STATUS_FOUR;
//                $data['msg'] = '柜子状态修改失败';
//            } else {
//                $data['status'] = self::ACTION_STATUS_THREE;
//                $data['price'] = $this->getPrice($this->authed->userId) / 10000;
//            }
//        }
//
//        // 返回结果
//        return $this->toSuccess($data);
//
//    }

    /**
     * 服务开通城市接口
     * @return mixed
     */
    public function CityAction()
    {
        $insId = $this->appData->getParentOperatorInsId();
        if ($insId == null) {
            return $this->toError(500,'当前APP服务不可用');
        }
        // 查询租赁业务区域
        $rentAreas =  $this->modelsManager->createBuilder()
            ->columns('a.area_id  AS code, a.area_name AS city, ap.area_name AS province')
            ->addfrom('app\models\service\RentArea','ra')
            ->Join('app\models\service\Area', 'a.area_id=ra.area_id AND a.area_deep=2','a')
            ->leftJoin('app\models\service\Area', 'ap.area_id=a.area_parent_id','ap')
            ->where('ra.ins_id = :ins_id:',['ins_id' => $insId])
            ->getQuery()
            ->execute()
            ->toArray();
        return $this->toSuccess($rentAreas);
    }
//
//    /**
//     * 辅助方法：对柜子操作指令
//     * @param int    $roomNum    柜门编号
//     * @param string $qrcode     换电柜编号
//     * @param int    $operation  动作指令 1：开门， 2：开始充电，3：停止充电
//     * @param int    $action     操作指令 1：放入电池  2：取出电池
//     * @return mixed
//     */
//    private function roomController($roomNum = 0, $qrcode = '', $operation = 0, $action = 0)
//    {
//        // 参数有效性判断
//        if ($roomNum == 0 || empty($qrcode)) {
//            return $this->toError(500, '要操作的柜子信息不完整');
//        }
//
//        if ($operation != 0) {
//            $params = [
//                'code' => 30006,
//                'parameter' => [
//                    'roomNum'   => $roomNum,
//                    'qrcode'    => $qrcode,
//                    'operation' => $operation,
//                    'action'    => $action
//                ]
//            ];
//        }
//
//        // 判断状态，返回结果
//        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");
//
//        if ($result['statusCode'] == 200) {
//            return true;
//        } else {
//            return false;
//        }
//    }
//
//    /**
//     * 辅助方法：获取换电柜所有柜子状态
//     * @param string $qrcode  换电柜编号
//     * @return mixed
//     */
//    private function cabinetInfo($qrcode)
//    {
//        // 参数有效性判断
//        if (empty($qrcode)) {
//            return $this->toError(500, '二维码不能为空');
//        }
//
//        // 调用微服务，获取换电柜所有柜子信息
//        $params = [
//            'code' => 30004,
//            'parameter' => [
//                'qrcode' => $qrcode
//            ]
//        ];
//
//        // 判断状态，返回结果
//        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");
//        if ($result['statusCode'] == 200 && isset($result['content']['cabinetList'][0])) {
//            $cabinet = $result['content']['cabinetList'][0];
//            return ['status' => true, 'data' => $cabinet];
//        } else {
//            return ['status' => false, 'data' => ''];
//        }
//    }

    /**
     * 辅助方法：获取换电柜所有柜子状态
     * @param string $qrcode  换电柜编号
     * @return mixed
     */
    private function allRoom($qrcode)
    {
        // 参数有效性判断
        if (empty($qrcode)) {
            return $this->toError(500, '二维码不能为空');
        }

        // 调用微服务，获取换电柜所有柜子信息
        $params = [
            'code' => 30001,
            'parameter' => [
                'qrcode' => $qrcode
            ]
        ];

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");

        if ($result['statusCode'] == 200 && isset($result['content']['roomstatusDOs'][0])) {
            $roomList = $result['content']['roomstatusDOs'];
            return $roomList;
        } else {
            return $this->toError(500, '获取换电柜信息失败');
        }
    }
//
//    /**
//     * 辅助方法：判断柜子是否有未关闭的仓门
//     * @param string $qrcode  换电柜编号
//     * @return mixed
//     */
//    private function checkDoors($qrcode)
//    {
//        $status = true;
//        // 参数有效性判断
//        if (empty($qrcode)) {
//            return $this->toError(500, '二维码不能为空');
//        }
//
//        // 调用微服务，获取换电柜所有柜子信息
//        $params = [
//            'code' => 30001,
//            'parameter' => [
//                'qrcode' => $qrcode
//            ]
//        ];
//
//        // 判断状态，返回结果
//        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");
//
//        if ($result['statusCode'] == 200 && isset($result['content']['roomstatusDOs'][0])) {
//            $roomList = $result['content']['roomstatusDOs'];
//            foreach ($roomList as $key => $value) {
//                if ($value['doorStatus'] == 0) {
//                    $status = false;
//                }
//            }
//        }
//        return $status;
//    }
//
//    /**
//     * 辅助方法：判断开启默认柜子
//     * @return mixed
//     */
//    private function openDefaultRoom($qrcode)
//    {
//        $roomList = $this->allRoom($qrcode);
//
//        $roomNum = 0;
//        $msg = '柜组异常，暂时无法提供服务';
//        $status = false;
//
//        foreach ($roomList as $key => $value) {
//            if ($value['isEmpty'] == 1) {
//                if ($value['doorStatus'] == 1) {
//                    if ($value['batteryStatus'] == 0) {
//                        $roomNum  = $value['roomNum'];
//                        $status   = true;
//                    } else {
//                        $msg = '电池充电异常，无法使用该柜子';
//                    }
//                } else {
//                    $msg = '请先关闭仓门，重新扫码';
//                }
//            }
//        }
//
//        return ['status' => $status, 'roomNum' => $roomNum, 'msg' => $msg];
//    }
//
//    /**
//     * 辅助方法：判断开启某个满电的柜子
//     * @return mixed
//     */
//    private function openFullRoom($qrcode)
//    {
//        $roomList = $this->allRoom($qrcode);
//
//        $roomNum = 0;
//        $lastTime = time();
//        foreach ($roomList as $key => $value) {
//            if ($value['isEmpty'] != 1 && $value['doorStatus'] == 1 && $value['batteryStatus'] == 0) {
//                if ($lastTime >= $value["putTime"]) {
//                    $roomNum  = $value['roomNum'];
//                    $lastTime = $value['putTime'];
//                }
//            }
//        }
//        if ($roomNum > 0) {
//            return $roomNum;
//        } else {
//            return false;
//        }
//    }
//
//    /**
//     * 辅助方法：更新换电柜柜子状态
//     * @param string $qrcode  换电柜编号
//     * @param array  $params  更新的参数
//     * @return mixed
//     */
//    private function roomUpdate($qrcode, $param)
//    {
//        // 参数有效性判断
//        if (empty($qrcode)) {
//            return $this->toError(500, '二维码不能为空');
//        }
//
//        $list[] = $param;
//        // 调用微服务，获取换电柜编号对应的数据
//        $params = [
//            "code" => 30005,
//            "parameter" => [
//                "qrcode"    => $qrcode,
//                "data"      => $list,
//                "type"      => 2
//            ]
//        ];
//
//        // 请求微服务接口提交换电柜组状态
//        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");
//
//        $result['statusCode'] = isset($result['statusCode']) ? $result['statusCode'] : "没有状态";
//
//
//        // 判断结果返回
//        if ($result['statusCode'] == 200) {
//            return true;
//        } else {
//            return false;
//        }
//    }
//
//    /**
//     * 辅助方法：获取换电柜某个柜子的状态
//     * @param string $qrcode  换电柜编号
//     * @return mixed
//     */
//    private function roomStatus($qrcode, $roomNum)
//    {
//        $allRoom = $this->allRoom($qrcode);
//        foreach ($allRoom as $key => $value) {
//            if ($value['roomNum'] == $roomNum) {
//                return $value;
//            }
//        }
//
//    }

    /**
     * 辅助方法：通过省市名称获取区域id
     * @param string $name  省市名称
     * @param string $deep  区域层级
     * @return mixed
     * @throws AppException
     */
    /*private function seachArea($name, $deep)
    {
        $params = [
            'code' => 10022,
            'parameter' => [
                'areaName'   => $name,
                'areaDeep'   => $deep
            ]
        ];

        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");

        if ($result['statusCode'] == 200 && isset($result['content']['data'][0])) {
            return $result['content']['data'][0]['areaId'];
        } else {
            throw new AppException([500, '未查到省市信息']);
        }
    }*/
//
//    /**
//     * 辅助方法：获取门店可用电池数量
//     * @param storeId int $storeId  门店
//     * @return mixed
//     */
//    private function storeBattery($storeId)
//    {
//        // API请求所需参数封装
//        $params['code'] = 30004;
//        $params['parameter']['pageSize'] = 50;
//        $params['parameter']['pageNum']  = 1;
//
//        // 通过门店名称获取门店ID
//        $params['parameter']['storeId'][] = $storeId;
//
//        // 请求微服务获取换电柜异常列表
//        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");
//
//        // 判断结果，并返回
//        if ($result['statusCode'] == 200 && isset($result['content']['cabinetList'][0])) {
//            $battery = 0;
//            if (is_array($result['content']['cabinetList'])) {
//                foreach ($result['content']['cabinetList'] as $value) {
//                    $battery += $value['maxNum'];
//                }
//            }
//            return $battery;
//        } else {
//            return 0;
//        }
//    }

    /**
     * 辅助方法：查询当前服务契约整合信息
     * @param int $driverId  门店
     * @return mixed
     */
    private function orderInfoContent($driverId = 0)
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

    /**
     * 辅助方法：查询当前服务契约整合信息
     * @param int $driverId  门店
     * @return mixed
     */
    private function orderInfo($driverId = 0)
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
//
//    /**
//     * 辅助方法：门店车辆数量统计
//     * @param int $storeId  门店ID
//     * @return mixed
//     */
//    private function storeVehicle($storeId)
//    {
//        $params = [
//            "code" => 10024,
//            "parameter" => [
//                "storeId" => $storeId,
//            ]
//        ];
//
//        // 请求微服务接口提交换电柜组状态
//        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
//
//        // 判断结果返回
//        if ($result['statusCode'] == 200 && isset($result['content']['data']) && isset($result['content']['data'][0])) {
//            $count = 0;
//            foreach ($result['content']['data'] as $key => $value) {
//                if ($value['driverId'] == 0) {
//                    $count += 1;
//                }
//            }
//            return $count;
//        } else {
//            return 0;
//        }
//    }
//
//    /**
//     * 辅助方法：创建换电订单
//     * @param int $qrcode  换电柜编号
//     * @return mixed
//     */
//    private function order($qrcode = 0)
//    {
//        if ($qrcode == 0) {
//            return false;
//        }
//
//        // API请求所需参数封装
//        $params['code']     = 30004;
//        $params['parameter']['pageSize'] = 10;
//        $params['parameter']['pageNum']  = 1;
//        $params['parameter']['qrcode']    = $qrcode;
//
//        // 请求微服务获取换电柜列表
//        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");
//
//        // 判断结果，并返回
//        if ($result['statusCode'] == 500 && count($result['content']['cabinetList']) <= 0) {
//            return $this->toError(500, '获取门店信息失败');
//        }
//
//        $cabinet = $result['content']['cabinetList'][0];
//
//        // 获取服务套餐电池租赁价格
//        $order = $this->orderInfo($this->authed->userId);
//
//        // 存在契约订单信息，并通过packageIds获取商品套餐详情列表
//        $packageIds[] = $order['productPackage']['packageId'];
//        $params = [
//            'code' => 10008,
//            'parameter' => [
//                'packageIds' => $packageIds
//            ]
//        ];
//
//        // 判断获取商品套餐详情列表的结果
//        $result = $this->curl->httpRequest($this->Zuul->order, $params, "post");
//
//        // 判断商品套餐详情中是否存在换电服务类型
//        $count = 0;
//        foreach ($result['content']['productPackageDetails'][0]['serviceItems'] as $key => $value) {
//            if ($value['serviceItemType'] == 2) {
//                $count = $value['servicePrice'];
//            }
//        }
//
//        if (!isset($order['vehicleRentOrderList']) || !isset($order['vehicleRentOrderList'][0]) || $order['vehicleRentOrderList'][0]['vehicleId'] == 0) {
//            $vehicleId = 0;
//        } else {
//            $vehicleId = $order['vehicleRentOrderList'][0]['vehicleId'];
//        }
//
//        // API请求所需参数封装
//        $params = [
//            'code' => 10020,
//            'parameter' => [
//                'serviceContractId' => $order['serviceContract']['id'],
//                'cabinetId'         => $cabinet['id'],
//                'fullCabinetNo'     => 0,
//                'emptyCabinetNo'    => 0,
//                'vehicleId'         => $vehicleId,
//                'driverId'          => $this->authed->userId,
//                'storeId'           => $cabinet['storeId'],
//                'amount'            => $count
//            ]
//        ];
//
//        // 请求微服务生成换电订单
//        $result = $this->curl->httpRequest($this->Zuul->order, $params, "POST");
//
//        // 判断结果，并返回
//        if ($result['statusCode'] == 200) {
//            return ['status' => 1, 'msg' => '订单生成成功'];
//        } else {
//            return ['status' => 0, 'msg' => '生成订单失败'];
//        }
//    }

    /**
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

    /**
     * 【新接口】骑手更换电池请求接口
     * @param string qrcode
     * @return mixed
     */
    public function qrcodeAction() {
        $driverId = $this->authed->userId;

        $request = $this->request->getJsonRawBody();
        $qrcode = isset($request->qrcode) ? $request->qrcode : '';

        if (!isset($this->authed->userId) || $this->authed->userId <= 0) {
            return $this->toError(500, '获取用户信息失败');
        }

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
            return $this->toError(500, "您尚无生效的服务套餐，不可使用服务");
        }
        $serviceContractId = $SC->id;

        // 获取服务单的状态和拥有的服务类型
        $result = $this->CallService('order', 10048, ['serviceContractId'=>$serviceContractId],true);
        $serviceContractContainItem = $result['content']['data'];

        if (ServiceContract::STATUS_USING != $serviceContractContainItem['serviceContractStatus']){
            return $this->toError(500, '服务套餐不在生效状态，不可使用服务');
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

        // 传递的参数有效性判断
        if (!$qrcode) {
            return $this->toError(500, '二维码不能为空');
        }
        // 查询换电价格
        $price = (new CabinetData())->getChargingPriceByQRCode($qrcode, 1);
        if (is_null($price)){
            return $this->toError(500, '该区域未设置换电价格，请联系客服');
        }

        // 获取二维码对应柜子默认柜门信息
        $query = CabinetRoom::query()->where('qrcode = :qrcode:', ['qrcode' => $qrcode])->execute()->toArray();
        if (count($query) != 1) {
            return $this->toError(500, '获取默认柜子信息失败');
        }
        $room = $query[0];
        $page = self::PAGE_OPEN;
        if ($room['driver_id'] != $driverId) {
            switch ($room['status']) {
                case 1:
                    if (time() - $room['action_time'] < 10) {
                        return $this->toError(500, self::ERROR_MSG[201]);
                    }
                    break;
                case 2:
                    if (time() - $room['action_time'] < 60) {
                        return $this->toError(500, self::ERROR_MSG[201]);
                    }
                case 3:
                    return $this->toError(500, self::ERROR_MSG[201]);
                case 4:
                    if (time() - $room['action_time'] < 60) {
                        return $this->toError(500, self::ERROR_MSG[201]);
                    }
                    break;
                case 5:
                    break;
                default:
                    return $this->toError(500, '柜子状态值异常');
                    break;
            }
            $result = $this->changeBattery($qrcode);
            if (!$result['status']) {
                return $this->toError(500, $result['msg']);
            } else {
                return $this->toSuccess(['status' => 1, 'roomNum' => 1, 'page' => $page]);
            }
        } else {
            $result = $this->changeBattery($qrcode);
            if (!$result['status']) {
                return $this->toError(500, $result['msg']);
            } else {
                return $this->toSuccess(['status' => 1, 'roomNum' => 1, 'page' => $page]);
            }
        }
    }

    /**
     * 【新】请求换电微服务
     */
    public function changeBattery($qrcode = null) {

        $driverId = $this->authed->userId;
        if (empty($qrcode)) {
            return ['status' => false, 'msg' => '参数不能为空'];
        }

        $params = [
            'code' => 20002,
            'parameter' => [
                'qrcode' => $qrcode,
                'driverId' => $driverId
            ]
        ];

        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");

        if ($result['statusCode'] == 200) {
            return ['status' => true, 'msg' => $result['msg']];
        } else {
            if (isset(self::ERROR_MSG[$result['statusCode']])) {
                $msg = self::ERROR_MSG[$result['statusCode']];
            } else {
                $msg = "扫码开门失败";
            }

            return ['status' => false, 'msg' => $msg];
        }
    }

    /**
     * 【新接口】骑手更换电池轮询接口
     * @param string qrcode
     * @return mixed
     */
    public function roomAction() {
        $driverId = $this->authed->userId;

        // 请求参数验证qrcode是否有效
        $request = $this->request->getJsonRawBody();
        $qrcode = isset($request->qrcode) ? $request->qrcode : 0;
        if ($qrcode == 0) {
            return $this->toError(500, '二维码不能为空');
        }

        // 获取二维码对应柜子默认柜门信息
        $query = CabinetRoom::query()->where('qrcode = :qrcode:', ['qrcode' => $qrcode])->execute()->toArray();
        if (count($query) != 1) {
            return $this->toError(500, '获取柜子信息失败');
        }
        $room = $query[0];
        // 增加日志
        $this->logger->info("【骑手更换电池轮询状态】骑手id:{$driverId} , QRCode:{$qrcode} " . json_encode($room));
        // 默认返回参数
        $price = 0; // 换电价格

        // 业务判断
        switch ($room['status']) {
            // 默认柜子等待打开
            case 1:
                if ((time() - $room['action_time']) < 20) {
                    $page = self::PAGE_OPEN;
                } else {
                    $page = self::PAGE_TIMEOUT;
                }
                break;
            case 2:
                if (time() - $room['action_time'] < 60) {
                    $page = self::PAGE_PUSH;
                } else {
                    $page = self::PAGE_TIMEOUT;
                }
                break;
            case 3:
                if (time() - $room['action_time'] < 60) {
                    $page = self::PAGE_PUSH;
                } else {
                    $page = self::PAGE_TIMEOUT;
                }
                break;
            case 4:
                if (time() - $room['action_time'] < 60) {
                    $page = self::PAGE_PULL;
                } else {
                    $page = self::PAGE_END;
                }
                break;
            case 5:
                $page = self::PAGE_END;
                $price = (new CabinetData())->getChargingPriceByQRCode($qrcode, 1);
                break;
            default:
                return $this->toError(500, '柜子状态值异常');
                break;
        }

        return $this->toSuccess(['page' => $page, 'price' => $price, 'roomNum' => $room['room_num']]);
    }
}
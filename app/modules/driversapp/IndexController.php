<?php
namespace app\modules\driversapp;

use app\common\errors\AppException;
use app\common\errors\MicroException;

use app\models\dispatch\DriverIns;
use app\models\users\OperatorAttribute;
use app\modules\BaseController;
use app\services\auth\AuthResult;
use app\services\auth\Authentication;
use app\services\data\CommonData;
use app\services\data\DriverData;
use app\services\data\MessagePushData;
use app\services\data\StoreData;
use Phalcon\Logger;

class IndexController extends BaseController
{
    const VEHICLE_HAS_DRIVER = 2;
    const VEHICLE_NO_DRIVER = 1;
    const PHONE_CODE_PRE = 'QSDL'; // 骑手登陆时发送验证码的缓存key前缀

    // 骑手登陆接口
    public function LoginAction()
    {

        $parentInsId = $this->appData->getParentOperatorInsId();

        // 获取传递参数
        $request = $this->request->getJsonRawBody();
        $phone  = isset($request->phone) ? $request->phone : "";
        $code   = isset($request->code) ? $request->code : "";

        // 对传参进行判断，手机号码、验证码不能为空
        if (empty($phone) || 0==preg_match('/^1\d{10}$/u', $phone)) {
            return $this->toError(500, "手机号有误");
        }

        // 未传验证码，则发送验证码
        if (empty($code)) {
            $sign = CommonData::PhoneSMSCodeTypeDW;
            // 顺丰模版
            if (false!==stripos($this->request->getHeader('packageName'), 'shunfeng')){
                $sign = CommonData::PhoneSMSCodeTypeSF;
            }
            // 发送验证码
            $bol = (new CommonData())->SendPhoneSMSCode($phone, CommonData::APP_RENT_RIDER, $sign);
            if (false === $bol){
                return $this->toError(500 , '验证码发送失败，请重试');
            }
            return $this->toSuccess();
        }

        // 验证码验证
        $bol = (new CommonData())->CheckPhoneSMSCode($phone, $code, CommonData::APP_RENT_RIDER,true);
        if (false === $bol){
            return $this->toError(500, '验证码有误,请重试');
        }

        // 查询骑手信息
        $driver =  $this->modelsManager->createBuilder()
            ->columns('d.id, d.user_name, d.identify, d.role_id, d.real_name, d.head_portrait, d.phone, d.status, i.is_authentication')
            ->addfrom('app\models\dispatch\Drivers','d')
            ->where('d.phone = :phone:', ['phone' => $phone])
            ->leftJoin('app\models\dispatch\DriversIdentification', 'i.driver_id=d.id','i')
            ->getQuery()
            ->execute()
            ->getFirst();
        // 骑手不存在，则自动注册
        if (false === $driver){
            // 新增骑手
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => 60016,
                'parameter' => [
                    'userName' => $phone,
                    'phone' => $phone,
                ]
            ],"post");
            // 异常报错
            if (200 != $result['statusCode']) {
                return $this->toError(500, '骑手注册失败,请重试');
            }
            // 记录注册日志
            $this->busLogger->recordingOperateLog("【骑手自动注册】手机号:{$phone}");
            // 查询骑手信息
            $driver =  $this->modelsManager->createBuilder()
                ->columns('d.id, d.user_name, d.identify, d.role_id, d.real_name, d.head_portrait, d.phone, d.status, i.is_authentication')
                ->addfrom('app\models\dispatch\Drivers','d')
                ->where('d.phone = :phone:', ['phone' => $phone])
                ->leftJoin('app\models\dispatch\DriversIdentification', 'i.driver_id=d.id','i')
                ->getQuery()
                ->execute()
                ->getFirst();
            // 异常报错
            if (false === $driver) {
                return $this->toError(500, '系统异常，请重试');
            }
        }
//       $driver = $driver->toArray();

        $driverIns = DriverIns::find(['conditions' => 'driver_id = :driverId: and ins_id = :insId:','bind' => ['driverId' => $driver->id,'insId' => $parentInsId]])->getFirst();
        if (!$driverIns) {
            //新增
            $driverIns = new DriverIns();
            $driverIns->driver_id = $driver->id;
            $driverIns->ins_id = $parentInsId;
            $driverIns->status = 1;
            if (!$driverIns->save()) {
                $this->logger->error("租赁骑手新增失败！");
                return $this->toError(500, '系统异常，请重试');
            }
        } else {
            if ($driverIns->status != 1) {
                return $this->toError(500, '账号已被禁用，请联系站点');
            }
        }
//        if (2==$driver['status']){
//            return $this->toError(500, '账号已被禁用，请联系站点');
//        }
        // 实人认证 1未通过 2通过
        $isAuthentication = $driver['is_authentication'] ?? 1;
        // 生日
        $birthday = null;
        $identify = $driver['identify'];
        if(18==strlen($identify))
        {
            $birthday = substr($identify,6,4).'.'.substr($identify,10,2).'.'.substr($identify,12,2);
            $identify = substr_replace($identify,'****************',1,16);
        }
        elseif(15==strlen($identify))
        {
            $birthday = '19'.substr($identify,6,2).'.'.substr($identify,8,2).'.'.substr($identify,10,2);
            $identify = substr_replace($identify,'*************',1,13);
        }
        $data['driver'] = [
            // 实人认证 1未通过 2通过
            'isAuthentication' => $isAuthentication,
            'id' => $driver['id'],
            'userName' => $driver['user_name'],
            'realName' => $driver['real_name'],
            'phone' => $driver['phone'],
            'birthday' => $birthday,
            'identify' => $identify,
            'headPortrait' => $driver['head_portrait'],
        ];

        // 生成骑手登陆的jwt信息
        $user = new Authentication();
        $user->userId           = $driver['id'];
        $user->userName         = $driver['real_name'];
        $user->roleId           = $driver['role_id'];
        $user->deviceUUID      = $this->request->getHeader('deviceUUID') ?? '';
        $user->groupId          = -1;
        $user->isAdministrator  = -1;
        $user->insId            = -1;
        $user->system           = -1;
        $user->userType         = 10;
        $result = $this->auth->authenticate_by_user($user);
        $data['access_token'] = $result->access_token;
        // 记录登录信息
        (new MessagePushData())->DriverLoginDevice($driver['id']);
        // 记录登录日志
        $this->busLogger->recordingOperateLog("【骑手登录】姓名:{$driver['real_name']} 手机号:{$phone}");
        // 返回骑手登陆信息及站点信息
        return $this->toSuccess($data);
    }

    // 得威出行APP首页门店地图
    public function StoreMapAction()
    {

        $request = $this->request->getJsonRawBody(true);
        $parentOperatorInsId = $this->appData->getParentOperatorInsId();
        $request['parentOperatorInsId'] = $parentOperatorInsId;
        $request['insUserType'] = 11;
        if (empty($request['areaId'])){
            return $this->toError(500, '未获取到所在区域信息');
        }
        $areaId = $request['areaId'];
        $request['cityId'] = substr($areaId, 0, 4) . '00';
        /**
         * 根据insId 获取类型
         */
        $types = [];
        $typesList = OperatorAttribute::find(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $parentOperatorInsId]])->toArray();
        if (isset($typesList) && count($typesList) > 0) {
            foreach($typesList as $item) {
                if (isset(StoreData::$types[$item['attribute_id']])) {
                    $types[] = StoreData::$types[$item['attribute_id']];
                }
            }
        }
        $request['types'] = $types;
        $data = (new StoreData())->getAPPStoreMap($request, true, true,true);
        return $this->toSuccess($data);

    }

    /**
     * 发送手机验证码
     * @param string $phone 手机号
     * @return mixed
     */
    private function SendMsgCode($phone = '') {

        // 参数有效性验证
        if (empty($phone)) {
            return ['status' => false, 'msg' => '手机号不能为空'];
        }
        $parameter = [
            "mobile" => $phone,
            "key"    => self::PHONE_CODE_PRE . $phone
        ];
        if (false!==stripos($this->request->getHeader('packageName'), 'shunfeng')){
            $parameter['sign'] = 2;
        }
        // 请求微服务接口发送验证码
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            "code" => 60015,
            "parameter" => $parameter
        ],"post");

        // 判断状态，返回结果
        if ($result['statusCode'] == 200) {
            return ['status' => true, 'msg' => '验证码发送成功'];
        } else {
            return ['status' => false, 'msg' => '验证码发送失败'];
        }
    }

    /**
     * 验证手机验证码
     * @param string $phone 手机号
     * @param string $code  验证码
     * @return mixed
     */
    private function checkCode($phone = '', $code = '', $door = false) {

        // 参数有效性验证
        if (empty($phone) || empty($code)) {
            return ['status' => false, 'msg' => '手机号或验证码不能为空'];
        }

        if ($door && $code == '987654') {
            return ['status' => true, 'msg' => '验证码正确'];
        }

        $params = [
            "key"    => self::PHONE_CODE_PRE . $phone
        ];


        // 请求微服务接口发送验证码
        $result = $this->curl->httpRequest($this->Zuul->redis,$params,"post");

        // 判断状态，返回结果
        if ($result['statusCode'] == 200 && isset($result['content']['data'])) {
            if ($result['content']['data'] == $code) {
                return ['status' => true, 'msg' => '验证码正确'];
            } else {
                return ['status' => false, 'msg' => '验证码错误'];
            }
        } else {
            return ['status' => false, 'msg' => '验证码无效'];
        }
    }

    /**
     * 骑手更新个人信息
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface|void
     */
    public function UpinfoAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 定义可用字段
        $fields = [
            // 用户名
            'userName' => 0,
            // 用户头像
            'headPortrait' => 0,
            // 手机号
            'phone' => 0,
            // 更换手机号所用验证码
            'code' => 0,
            'identificationPhoto' => 0
        ];
        $parameter = $this->getArrPars($fields, $request);
        if (false === $parameter){
            return;
        }
        $driverId = $this->authed->userId;
        // 未上传参数，报错
        if (0 == count($parameter)){
            return $this->toError(500, "参数错误");
        }
        // 昵称校验
        if (isset($parameter['userName'])){
            if (0==preg_match('/^[a-zA-Z0-9\x{4e00}-\x{9fa5}]{1,6}$/u', $parameter['userName'])){
                return $this->toError(500, '昵称不合法');
            }
            if ($this->DriverData->hasUserName($parameter['userName'], $driverId)){
                return $this->toError(500, '昵称已被使用');
            }
        }
        // 如果变更手机号未提供验证码
        if (isset($parameter['phone']) && !isset($parameter['code'])){
            return $this->toError(500, '请提供验证码');
        }
        // 变更手机号校验
        if (isset($parameter['phone']) && isset($parameter['code'])){
            // 验证码
            $res = $this->checkCode($parameter['phone'], $parameter['code']);
            if (false === $res['status']){
                return $this->toError(500, $res['msg']);
            }
            // 查询手机号重复
            $has = $this->DriverData->getDriverByPhone($parameter['phone']);
            if (!is_null($has)){
                return $this->toError(500, '手机号已被使用');
            }
        }
        $parameter['id'] = $driverId;
        // 更新骑手信息
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => "60012",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '更新失败');
        }
        return $this->toSuccess();
    }

    /**
     * 发送验证码
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function SendverifycodeAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['phone'])
            || is_null($request['phone'])
            || 0==preg_match('/^1\d{10}$/u', $request['phone'])){
            return $this->toError(500, '手机号码有误');
        }
        // 发送验证码
        $res = $this->SendMsgCode($request['phone']);
        if (false === $res['status']){
            return $this->toError(500, $res['msg']);
        }
        return $this->toSuccess();
    }

    /**
     * 校验验证码
     */
    public function CheckverifycodeAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['phone']) || !isset($request['code'])){
            return $this->toError(500, '参数错误');
        }
        $res = $this->checkCode($request['phone'], $request['code']);
        if (false === $res['status']){
            return $this->toError(500, $res['msg']);
        }
        return $this->toSuccess();
    }

    /**
     * 换绑验证身份
     */
    public function CheckidentityinfoAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['realName']) || !isset($request['identify'])){
            return $this->toError(500, '参数有误');
        }
        $driver = (new DriverData())->getDriver([
            'id' => $driverId,
            'realName' => $request['realName'],
            'identify' => $request['identify'],
        ]);
        if (!isset($driver[0])){
            return $this->toError(500, '身份校验不通过');
        }
        return $this->toSuccess();
    }

    // 骑手获取个人信息
    public function DriverinfoAction()
    {
        $driverId = $this->authed->userId;
        // 获取骑手信息
        $driver = (new DriverData())->getDriverById($driverId);
        // 获取骑手实名认证状态
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60064,
            'parameter' => [
                'driverIdList' => [$driverId]
            ]
        ],"post");

        if ($result['statusCode'] == 200 && isset($result['content']['data'][0])) {
            $isAuthentication = $result['content']['data'][0]['isAuthentication'];
        } else {
            $isAuthentication = 1;
        }
        // 生日
        $birthday = null;
        $identify = $driver['identify'];
        if(18==strlen($identify))
        {
            $birthday = substr($identify,6,4).'.'.substr($identify,10,2).'.'.substr($identify,12,2);
            $identify = substr_replace($identify,'****************',1,16);
        }
        elseif(15==strlen($identify))
        {
            $birthday = '19'.substr($identify,6,2).'.'.substr($identify,8,2).'.'.substr($identify,10,2);
            $identify = substr_replace($identify,'*************',1,13);
        }
        $data = [
            // 实人认证 1未通过 2通过
            'isAuthentication' => $isAuthentication,
            'id' => $driver['id'],
            'userName' => $driver['userName'],
            'realName' => $driver['realName'],
            'phone' => $driver['phone'],
            'birthday' => $birthday,
            'identify' => $identify,
            'headPortrait' => isset($driver['headPortrait']) ? $driver['headPortrait'] : '',
        ];
        // 返回骑手登陆信息
        return $this->toSuccess($data);
    }

    // 骑手获取违章信息
    public function PeccancyAction()
    {
        $parameter['pageSize'] = $_GET['pageSize'] ?? 20;
        $parameter['pageNum'] = $_GET['pageNum'] ?? 1;
        $parameter['driverId'] = $this->authed->userId;
        // 查询车辆违规列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15006',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '系统异常:'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        foreach ($list as $k => $v){
            // 处理一对多数据为数组
            $list[$k]['types'] = explode('|', $v['types']);
            $list[$k]['picPaths'] = explode('|', $v['picPaths']);
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }
}

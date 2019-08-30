<?php
namespace app\modules\postofficeapp;

use app\common\errors\AppException;
use app\models\dispatch\DriverLicence;
use app\models\dispatch\Drivers;
use app\models\dispatch\RegionDrivers;
use app\models\service\Area;
use app\models\service\PostofficeVehicleLock;
use app\models\service\PostofficeVehicleLog;
use app\models\service\RegionVehicle;
use app\models\service\Vehicle;
use app\models\service\VehicleLockQueue;
use app\models\users\Association;
use app\models\users\Company;
use app\models\users\Institution;
use app\models\users\User;
use app\models\users\UserInstitution;
use app\modules\BaseController;
use app\services\data\CommonData;
use app\services\data\DriverData;
use app\services\data\PostOfficeData;
use app\services\data\UserData;
use app\services\auth\Authentication;
use app\services\data\MessagePushData;
use app\services\data\VehicleData;
use Phalcon\Exception;

class DriverController extends PostofficebaseController
{
    // 骑手登录
    public function LoginAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody();
        $phone  = $request->phone ?? '';
        $code   = $request->code ?? '';
        // 对传参进行判断，手机号码、验证码不能为空
        if (empty($phone) || 0==preg_match('/^1\d{10}$/u', $phone)) {
            return $this->toError(500, "手机号码格式错误");
        }
        $bol = (new CommonData())->CheckPhoneSMSCode($phone, $code, CommonData::APP_POSTAL_RIDER, true);
        if (false === $bol){
            return $this->toError(500, '验证码有误,请重试');
        }
        // 查询骑手信息
        $driver =  $this->getDriverInfo($phone);
        if (false === $driver){
            //注册骑手
            if (!$this->addDriver($phone)) {
                return $this->toError(500, '注册失败');
            }
            $driver =  $this->getDriverInfo($phone);
//            return $this->toError(500, '手机号码不在系统内');
        }
        $driver = $driver->toArray();
        if (2==$driver['status']){
            return $this->toError(500, '账号已被禁用，请联系站点');
        }
        if (!($driver['ins_id'] > 0)){
            $cityId = '';
            $needAuth = true;
        } else {
            // 校验快递公司是否被禁用
            $UI = UserInstitution::arrFindFirst([
                'ins_id' => $driver['ins_id'],
                'is_admin' => 1
            ]);
            if ($UI){
                $user = User::arrFindFirst([
                    'id' => $UI->user_id,
                ]);
                if ($user && $user->user_status == 2){
                    return $this->toError(500, '快递公司已被禁用，不可登录');
                }
            }
            $cityId = (new PostOfficeData())->getPostOfficeCityIdByExpressInsId($driver['ins_id']);
            $needAuth = (new PostOfficeData())->getPostOfficeSystemParam($cityId, PostOfficeData::RealAuthentication);
        }
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
        // 查询快递公司名称
        $driver['expressName'] = $this->userData->getExpressNamesByInsId($driver['ins_id']);
        $data['driver'] = [
            // 实人认证 1未通过 2通过
            'isAuthentication' => $isAuthentication,
            'id' => $driver['id'],
            'userName' => $driver['user_name'],
            'realName' => $driver['real_name'],
            'phone' => $driver['phone'],
            'sex' => $driver['sex'],
            'birthday' => $birthday,
            'identify' => $identify,
            'headPortrait' => $driver['head_portrait'],
            'expressName' => $driver['expressName'],
            'siteName' => $driver['region_name'] ?? '',
            'cityId' => $cityId,
            'needAuth' => $needAuth
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

    // 骑手登录获取短信验证码
    public function GetSMSCodeCodeAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody();
        $phone  = $request->phone ?? '';
        // 对传参进行判断，手机号码、验证码不能为空
        if (empty($phone) || 0==preg_match('/^1\d{10}$/u', $phone)) {
            return $this->toError(500, "手机号有误");
        }
//        $driver = Drivers::arrFindFirst([
//            'phone' => $phone
//        ]);
//        if (false === $driver){
//            return $this->toError(500, '手机号码不存在');
//        }
        $bol = (new CommonData())->SendPhoneSMSCode($phone, CommonData::APP_POSTAL_RIDER);
        if (false === $bol){
            return $this->toError(500 , '短信发送失败，请重试');
        }
        return $this->toSuccess();
    }

    // 骑手注册
    public function RegistrationAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        $phone  = $request['phone'] ?? '';
        $pwd  = '123456';
        // 手机号码校验
        if (empty($phone) || 0==preg_match('/^1\d{10}$/u', $phone)) {
            return $this->toError(500, "手机号有误");
        }
        // 手机号码是否存在
        $driver = Drivers::arrFindFirst([
            'phone' => $phone
        ]);
        if ($driver){
            return $this->toError(500, "手机号已存在");
        }
        // 新增骑手
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60016,
            'parameter' => [
                'userName' => $phone,
                'phone' => $phone,
                'password' => $this->security->hash($pwd),
            ]
        ],"post");
        // 异常报错
        if (200 != $result['statusCode']) {
            return $this->toError(500, '骑手注册失败,请重试');
        }
        // 记录注册日志
        $this->busLogger->recordingOperateLog("【骑手注册】手机号:{$phone}");
        return $this->toSuccess();
    }

    // 重置密码
    public function ResetPasswordAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody();
        $phone  = $request->phone ?? '';
        $code  = $request->phone ?? '';
        $pwd  = $request->phone ?? '';
        // 手机号码是否存在
        $driver = Drivers::arrFindFirst([
            'phone' => $phone
        ]);
        if (false === $driver){
            return $this->toError(500, '骑手未注册');
        }
        $bol = (new CommonData())->CheckPhoneSMSCode($phone, $code);
        if (false === $bol){
            return $this->toError(500, '验证码有误,请重试');
        }
        // 更新骑手信息
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => "60012",
            'parameter' => [
                'id' => $driver->id,
                'password' => $pwd,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '更新失败');
        }
        return $this->toSuccess();
    }

    // 骑手绑车
    // TODO: 11-28 汪洋变更绑车逻辑时 同时部分文案变更
    // https://wt-box.worktile.com/public/51d1dd31-019f-4065-861e-4f7bd5979fbb
    public function BindVehicleAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['bianhao'])){
            return $this->toError(500,'未收到有效车辆编号');
        }
        $bianhao = $request['bianhao'];
        $driverId = $this->authed->userId;
        // 查询骑手当前城市
        $cityId = (new DriverData())->getCityIdByDriverId($driverId);
        // 查询驾照校验开关
        $needDriversLicense = (new PostOfficeData())->getPostOfficeSystemParam($cityId, PostOfficeData::BindVehicleNeedDriversLicense);
        if ($needDriversLicense){
            // 查询骑手驾照分
            $licenceScore = $this->getDriverLicenceFractionBydriverId($driverId);
            // 无驾照 || 驾照分不足
            if (false==$licenceScore || $licenceScore <= 0){
                if (false==$licenceScore){
                    $licenceScore = 0;
                }
                return $this->toError(500, "驾照分不足(当前为{$licenceScore})，无法绑车");
            }
        }

        // 查询骑手快递公司站点
        $RD = RegionDrivers::arrfindFirst([
            'driver_id' => $driverId,
        ]);
        if (false===$RD){
            return $this->toError(500, '骑手未绑定快递公司，不可操作');
        }
        // 查询骑手是否在邮管有绑车
        $hasVehicle = RegionVehicle::arrFindFirst([
            'driver_id' => $driverId,
        ]);
        // 已绑定车辆，不可继续绑定
        if ($hasVehicle){
            return $this->toError(500, '您已绑定邮管车辆，不能再进行车辆绑定操作');
        }
        // 查询车辆信息
        $vehicle = Vehicle::arrFindFirst([
            'bianhao' => $bianhao,
        ]);
        if (false===$vehicle){
            return $this->toError(500, '车辆不存在，请扫描正确的二维码');
        }
        $vehicleId = $vehicle->id;
        // 查询车辆快递公司关系
        $RV = RegionVehicle::arrFindFirst([
            'ins_id' => $RD->ins_id,
            'vehicle_id' => $vehicleId,
        ]);
        if (false===$RV){
            return $this->toError(500, '车辆不属于当前公司，请联系管理员');
        }
        // 未绑定站点
        if (!($RV->region_id > 0)){
            return $this->toError(500, '站点未绑定，联系站长绑定后操作');
        }
        if ($RV->driver_id > 0) {
            return $this->toError(500, "该车辆已被骑手绑定");
        }
        // 判断车辆是否已绑定骑手
//        if ($RV->driver_id > 0){
//            $bindedDriver =  Drivers::arrFindFirst([
//                'id' => $RV->driver_id
//            ]);
//            $bindedDriverName = $bindedDriver->real_name ?? '';
//            return $this->toError(500, "该车辆已被骑手“{$bindedDriverName}”绑定，如需绑定，请联系对方解绑车辆");
//        }
        // 开启事务
        $this->dw_service->begin();
        $this->dw_dispatch->begin();
        // 更新车辆站点骑手绑定关系
        $RV->bind_status = 2;
        $RV->driver_id = $driverId;
        $RV->bind_time = time();
        $RV->update_time = time();
        $bol = $RV->save();
        if (false===$bol){
            // 事务回滚
            $this->dw_service->rollback();
            $this->dw_dispatch->rollback();
            $this->logger->error($RV->getMessages()[0]->getMessage());
            return $this->toError(500, '系统异常，绑定失败');
        }
        // 更新骑手站点关系
        if ($RD->region_id != $RV->region_id){
            $RD->region_id = $RV->region_id;
            $bol = $RD->save();
            if (false===$bol){
                // 事务回滚
                $this->dw_service->rollback();
                $this->dw_dispatch->rollback();
                $this->logger->error($RD->getMessages()[0]->getMessage());
                return $this->toError(500, '系统异常，绑定失败');
            }
        }
        // 车辆信息修改
        $vehicle->driver_bind = 2;
        $vehicle->driver_id = $driverId;
        $vehicle->update_time = time();
        $bol = $vehicle->update();
        if (false===$bol){
            // 事务回滚
            $this->dw_service->rollback();
            $this->dw_dispatch->rollback();
            return $this->toError(500, '系统异常，绑定失败');
        }
        // 提交事务
        $this->dw_dispatch->commit();
        $this->dw_service->commit();
        // 删除锁车队列
        $bol = (new VehicleLockQueue())->del($vehicleId);
        // 记录操作日志
        $tip = $bol ? '成功' : '失败';
        $delLock = "【锁车队列删除{$tip}】场景:小哥助手绑车,车辆id:{$vehicleId},骑手id:{$driverId}";
        $this->logger->info($delLock);
        // 解锁车辆
        $VehicleData = new VehicleData();
        $VehicleData->UnLock($vehicle->id,
            "【小哥助手绑车解锁】骑手id:{$driverId},车辆编号:{$vehicle->bianhao},udid:{$vehicle->udid}");
        return $this->toSuccess();
    }

    // 年检任务
    public function InspectionInfoAction()
    {
        $driverId = $this->authed->userId;
        // 获取骑手绑定快递公司车辆
        $RV = RegionVehicle::arrFindFirst([
            'driver_id' => $driverId,
        ]);
        if (false === $RV){
            return $this->toError(500, '尊敬的用户，您未绑定车辆，不能进行年检任务');
        }
        $RV = $RV->toArray();
        // 获取年检详情
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11017,
            'parameter' => [
                'driverId' => $driverId,
                'vehicleId' => $RV['vehicle_id'],
            ]
        ],"post");
        // 异常报错 部分code放行给前端处理
        if (200 != $result['statusCode'] && !in_array($result['statusCode'], [1024, 1026])) {
            $tmp = [
                '1021' => '尊敬的用户，您已经完成过对一辆车的年检,本年度不能重复对其它车辆进行年检',
                '1022' => '当前车辆没有所属的快递公司',
                '1023' => '该车辆所属的快递公司没有快递协会',
                // '1024' => '尊敬的用户，您绑定的车辆暂无年检任务',
                '1025' => '外观审核失败,外观审核已超期,请联系管理员',
                // '1026' => '该车辆今年年检已完成',
                '1027' => '外观审核待上传,外观审核已超期,请联系管理员',
            ];
            $tip = $tmp[$result['statusCode']] ?? $result['msg'];
            return $this->toError(500, $tip);
        }
        $data = [
            'yearlyCheckTask' => $result['content']['yearlyCheckTask'] ?? null,
            'serviceCode' => $result['statusCode'],
        ];
        return $this->toSuccess($data);
    }

    // 提交年检
    public function SubmitInspectionAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'yearlyCheckTaskId' => '无效的年检任务',
            'yearlyCheckItemImgs' => '请上传图片',
        ];
        $parameter = $this->getArrPars($fields, $request);
        $parameter['driverId'] = $this->authed->userId;
        // 提交年检
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11018,
            'parameter' => $parameter
        ],"post");
        // 异常报错
        if (200 != $result['statusCode']) {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        return $this->toSuccess();
    }

    /**
     * 车辆详情
     */
    public function InfoAction() {
        $json = ['driverId' => $this->authed->userId];
        $result = $this->userData->common($json, $this->Zuul->vehicle,30002);
        $meta = '';
        $result = $result['data'];
        if(empty($result)) {
            return $this->toSuccess(null,$meta);
        }
        return $this->toSuccess($result,$meta);
    }

    /**
     * 解绑车辆
     */
    public function UntiedVehicleAction() {
        $json = ['driverId' => $this->authed->userId];
        $result = $this->userData->postCommon($json, $this->Zuul->vehicle,30003);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 骑手驾照
     */
    public function LicenceDetailAction() {
        $id = $this->authed->userId;
        $builder = $this->modelsManager->createBuilder()
            ->columns('dl.id,dl.driver_id,dl.has_licence,dl.licence_num,dl.valid_starttime,dl.valid_starttime,dl.valid_endtime,dl.licence_score,dl.get_time,dl.front_img,dl.back_img,d.real_name,d.sex,dl.ins_id,dl.ins_name,d.identification_photo,rd.ins_id as regionInsId')
            ->addFrom('app\models\dispatch\DriverLicence','dl')
            ->leftJoin('app\models\dispatch\Drivers','d.id = dl.driver_id','d')
            ->leftJoin('app\models\dispatch\RegionDrivers','rd.driver_id = dl.driver_id','rd')
            ->andWhere('d.id = :id:',['id' => $id])
            ->getQuery()
            ->getSingleResult();
        if (!$builder || empty($builder)) {
            $builder = null;
            return $this->toSuccess($builder);
        }
        /**
         * 发证单位
         */
        $cityId = '';
        if (!empty($builder->ins_id)) {
            $association = Association::findFirst(['conditions' =>'ins_id = :ins_id:','bind' => ['ins_id' => $builder->ins_id]]);
            if ($association && $association->getCityId()) {
                $cityId = $association->getCityId();
                $area = Area::findFirst(['conditions' =>'area_id = :area_id:','bind' => ['area_id' => $association->getCityId()]]);
            }
        }

        if (isset($area)) {
            $builder->cityName = $area->area_name;
        } else {
            $builder->cityName = '';
        }

        /**
         * 骑手目前的单位（快递公司）
         */
        if (!empty($builder->regionInsId)) {
            $company = Company::findFirst(['conditions' =>'ins_id = :ins_id:','bind' => ['ins_id' => $builder->regionInsId]]);
            if ($company && $company->getCompanyName()) {
                $builder->companyName = $company->getCompanyName();
                $builder->companyId = $company->getInsId();
                $builder->companyCityId = $company->getCityId();
            } else {
                $builder->companyName = '';
                $builder->companyId = '';
                $builder->companyCityId = '';
            }
        } else {
            $builder->companyName = '';
            $builder->companyId = '';
            $builder->companyCityId = '';
        }

        if ($builder->companyCityId != $cityId) {
            return $this->toError(500,'骑手驾照所在城市和当前城市不匹配!');
        }

        return $this->toSuccess($builder);
    }

    // 骑手锁车
    public function LockVehicleAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? 0;
        if(!($vehicleId>0)){
            return $this->toError(500, '车辆参数有误');
        }
        $driverId = $this->authed->userId;
        // 查询车辆是否属于骑手
        $RV = RegionVehicle::arrFindFirst([
            'driver_id' => $driverId,
            'vehicle_id' =>$vehicleId,
        ]);
        if (false===$RV){
            return $this->toError(500, '非法操作，车辆不属于当前骑手');
        }
        // 查询车辆信息
        $vehicle = Vehicle::arrFindFirst(['id'=>$vehicleId]);
        if (false===$vehicle){
            return $this->toError(500, '无效的车辆信息');
        }
        if (empty($vehicle->udid)) {
            return $this->toError(500, '无效的设备');
        }
        // 查询骑手信息
        $driver = Drivers::arrFindFirst([
            'id' => $driverId,
        ]);
        if (false == $driver){
            return $this->toError(500, '骑手信息异常');
        }
        // 记录邮管锁车日志
        $bol = (new PostofficeVehicleLog())->create([
            'vehicle_id' => $vehicleId,
            'operator_name' => $driver->real_name,
            'operator_id' => $driverId,
            'operator_type' => PostofficeVehicleLog::OPERATOR_TYPE_DRIVER,
            'operate_description' => '骑手锁车',
            'status' => 1,
            'create_time' => time(),
        ]);
        if (false == $bol){
            return $this->toError(500, '操作失败，请重试');
        }
        $VehicleData = new VehicleData();
        $result = $VehicleData->Lock($vehicle->id,
            "【小哥助手骑手锁车】骑手id:{$driverId},车辆编号:{$vehicle->bianhao},udid:{$vehicle->udid}");
        if ($result == false ) {
            return $this->toError(500,"锁车失败");
        }
        return $this->toSuccess();
    }
    // 骑手解锁车辆
    public function UnLockVehicleAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? 0;
        $driverId = $this->authed->userId;
        if(!($vehicleId>0)){
            return $this->toError(500, '车辆参数有误');
        }
        // 查询【驾照无分锁车】是否开启
        $driverData = new DriverData();
        $cityId = $driverData->getCityIdByDriverId($driverId);
        if ($driverData->getCityLicenseLockCarSwitchByCityId($cityId)){
            // 查询骑手当前是否有解锁权限
            // 查询骑手驾照分
            $licenceScore = $this->getDriverLicenceFractionBydriverId($driverId);
            // 无驾照 || 驾照分不足
            if (false==$licenceScore || $licenceScore <= 0){
                // 查询锁车计划
                $lockPlan = PostofficeVehicleLock::arrFindFirst([
                    'driver_id' => $driverId,
                    'status' => 1,
                ]);
                if (false == $lockPlan){
                    return $this->toError(500, '解锁失败，尊敬的用户，您的驾照分已小于零分，请联系所属快递公司，重新进行考试获取分数。');
                }
            }
        }
        // 查询车辆是否属于骑手
        $RV = RegionVehicle::arrFindFirst([
            'driver_id' => $driverId,
            'vehicle_id' =>$vehicleId,
        ]);
        if (false===$RV){
            return $this->toError(500, '非法操作，车辆不属于当前骑手');
        }
        // 查询车辆信息
        $vehicle = Vehicle::arrFindFirst(['id'=>$vehicleId]);
        if (false===$vehicle){
            return $this->toError(500, '无效的车辆信息');
        }
        if (empty($vehicle->udid)) {
            return $this->toError(500, '无效的设备');
        }
        // 查询骑手信息
        $driver = Drivers::arrFindFirst([
            'id' => $driverId,
        ]);
        if (false == $driver){
            return $this->toError(500, '骑手信息异常');
        }
        // 记录邮管锁车日志
        $bol = (new PostofficeVehicleLog())->create([
            'vehicle_id' => $vehicleId,
            'operator_name' => $driver->real_name,
            'operator_id' => $driverId,
            'operator_type' => PostofficeVehicleLog::OPERATOR_TYPE_DRIVER,
            'operate_description' => '骑手解锁',
            'status' => 1,
            'create_time' => time(),
        ]);
        if (false == $bol){
            return $this->toError(500, '操作失败，请重试');
        }
        $VehicleData = new VehicleData();
        $result = $VehicleData->UnLock($vehicle->id,
            "【小哥助手骑手解锁】骑手id:{$driverId},车辆编号:{$vehicle->bianhao},udid:{$vehicle->udid}");
        if ($result == false ) {
            return $this->toError(500,"解锁失败");
        }
        return $this->toSuccess();
    }

    // 骑手信息
    public function DriverInfoAction()
    {
        $driverId = $this->authed->userId;
        // 调用服务端
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 10010,
            'parameter' => [
                'driverId' => $driverId,
            ]
        ],"post");
        // 失败返回
        if (200 != $result['statusCode']) {
            $tips = [
                '10000' => '骑手不存在'
            ];
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        return $this->toSuccess($result['content']);
    }

    // 查询骑手当前驾照分数
    private function getDriverLicenceFractionBydriverId($driverId)
    {
        // 查询骑手所属快递公司-》所属快递协会-》关联骑手驾照
        $RD = RegionDrivers::arrFindFirst([
            'driver_id' => $driverId,
        ]);
        $expressInsId = $RD->ins_id;
        // 查询关联快递协会id
        $associationInsId = (new UserData())->getParentInsIdByInsId($expressInsId);
        // 查询骑手驾照分
        $licence = DriverLicence::arrFindFirst([
            'driver_id'=>$driverId,
            'ins_id' => $associationInsId,
            'valid_starttime' => ['<', time()],
            'valid_endtime' => ['>', time()],
        ]);
        return false==$licence ? false : $licence->getLicenceScore();
    }

    /**
     * 新增骑手信息
     * @param $phone
     * @return bool|\Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\MicroException
     */
    private function addDriver($phone)
    {
        // 手机号码是否存在
        $driver = Drivers::arrFindFirst([
            'phone' => $phone
        ]);
        if ($driver){
            throw new Exception("手机号已存在");
        }
        // 新增骑手
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60016,
            'parameter' => [
                'userName' => $phone,
                'phone' => $phone,
                'password' => $this->security->hash('123456'),
            ]
        ],"post");
        // 异常报错
        if (200 != $result['statusCode']) {
            return false;
        }
        // 记录注册日志
        $this->busLogger->recordingOperateLog("【骑手注册】手机号:{$phone}");
        return true;
    }

    /**
     * 获取骑手注册信息
     * @param $phone
     * @return mixed
     */
    private function getDriverInfo($phone) {
        $driver =  $this->modelsManager->createBuilder()
            ->columns('d.id, d.user_name, d.identify, d.role_id, d.real_name,d.sex, d.head_portrait, d.phone, d.status, i.is_authentication, rd.region_id, rd.ins_id, r.region_name')
            ->addfrom('app\models\dispatch\Drivers','d')
            ->where('d.phone = :phone:', ['phone' => $phone])
            ->leftJoin('app\models\dispatch\DriversIdentification', 'i.driver_id=d.id','i')
            ->leftJoin('app\models\dispatch\RegionDrivers', 'rd.driver_id=d.id','rd')
            ->leftJoin('app\models\dispatch\Region', 'r.id=rd.region_id AND r.region_type=2','r')
            ->getQuery()
            ->execute()
            ->getFirst();
        return $driver;
    }

    // 骑手发起实人认证
    public function RPBioIDPersonCertAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'name' => '请输入姓名',
            'identificationNumber' => '请输入身份证号',
        ];
        $parameter = $this->getArrPars($fields, $request);
        if (!$this->isIdCard($parameter['identificationNumber'])){
            return $this->toError(500, '身份证号有误，请检查输入');
        }
        // type 1-正常 2-身份证已被使用
        $backData = [
            'type' => 1,
        ];
        // 查询是否身份证是否已被使用
        $hasDriver = Drivers::arrFindFirst([
            'identify' => $parameter['identificationNumber'],
            'id' => ['!=', $driverId],
        ]);
        if ($hasDriver){
            $backData['type'] = 2;
            return $this->toSuccess($backData);
        }
        $result = $this->PenetrateTransferToService('biz', 10027, $parameter, true);
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $backData['ticketId'] = $result['content']['ticketId'];
        $backData['token'] = $result['content']['token'];
        return $this->toSuccess($backData);
    }

    // 实人认证后更新骑手
    public function RPBioIDPersonCertEndAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['ticketId'])){
            return $this->toError(500,'未收到ticketId');
        }
        (new DriverData())->PersonCertEndUpdateDriverInfo($driverId, $request['ticketId'], 'IDCardIdentify');
        return $this->toSuccess();
    }
}

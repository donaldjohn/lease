<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\dispatch\Drivers;
use app\models\dispatch\RegionDrivers;
use app\models\order\ServiceContract;
use app\models\order\VehicleRentOrder;
use app\models\service\StoreVehicle;
use app\models\service\Vehicle;
use app\models\service\VehicleLockRecord;
use app\models\service\VehicleLockScenes;


class DriverData extends BaseData
{
    // 获取多条骑手信息 通过idlist
    public function getDriverByIds($ids, $Convert=false)
    {
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0])));
        $drivers = $this->getDriver([
            'idList' => $ids,
        ], $Convert);
        if ($Convert){
            $tmp = [];
            foreach ($drivers as $driver){
                $tmp[(string)$driver['id']] = $driver;
            }
            $drivers = $tmp;
        }
        return $drivers;
    }

    // 获取单条骑手信息 通过id
    public function getDriverById($id)
    {
        $drivers = $this->getDriverByIds([$id]);
        if (count($drivers)!=1) {
            throw new DataException([500, '未获取到骑手信息']);
        }
        $driver = $drivers[0];
        return $driver;
    }

    // 查询骑手 通过条件
    public function getDriver($where, $Convert=false)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60014,
            'parameter' => $where,
        ],"post");
        if (!isset($result['statusCode']) || '200' != $result['statusCode']) {
            throw new DataException([500, '未获取到骑手信息']);
        }
        $drivers = $result['content']['driversDOS'];
        if ($Convert){
            $tmp = [];
            foreach ($drivers as $driver){
                $tmp[(string)$driver['id']] = $driver;
            }
            $drivers = $tmp;
        }
        return $drivers;
    }

    // 查询骑手通过 手机号
    public function getDriverByPhone($phone=null, $Convert=false, $all=false)
    {
        if (is_null($phone)){
            return null;
        }
        // 获取数据
        $drivers = $this->getDriver([
            'phone' => $phone,
        ], $Convert);
        // 是否要全部
        if ($all){
            return $drivers;
        }
        // 查不到返回null
        return isset($drivers[0]) ? $drivers[0] : null;
    }

    // 查询用户名是否存在
    public function hasUserName($userName=null, $excludeId=null)
    {
        // 获取数据
        $drivers = $this->getDriver([
            'userName' => $userName,
        ]);
        // 微服务对于userName是模糊搜索，需自行判断
        foreach ($drivers as $driver){
            if ($userName==$driver['userName'] && $driver['id']!=$excludeId) return true;
        }
        return false;
    }

    // 查询骑手当前有效租车单
    public function getCurrentVehicleRentOrderByDriverId($driverId, $vehicleId=null)
    {
        $data = [
            'driver_id' => $driverId,
            'start_time' => ['<', time()],
            'end_time' => ['>', time()],
            'pay_status' => 2,
            'is_delete' => 0,
        ];
        if (null !== $vehicleId){
            $data['vehicle_id'] = $vehicleId;
        }
        // 查询当前骑手车辆是否有有效租车单
        $VRO = VehicleRentOrder::arrFindFirst($data);
        return $VRO ?: false;
    }

    // 查询区域骑手关系 通过 骑手Ids
    public function getRegionDriverSByDriverIds($DriverIds, $arr=true, $insId=null)
    {
        if (is_array($DriverIds)){
            $data['driver_id'] = ['IN', $DriverIds];
        }else{
            $data['driver_id'] = $DriverIds;
        }
        if(!is_null($insId)){
            $data['ins_id'] = $insId;
        }
        $RDS = (new RegionDrivers())->arrFind($data);
        return $arr ? $RDS->toArray() : $RDS;
    }

    // 查询区域id 通过 骑手Id
    public function getRegionIdByDriverId($DriverId, $insId=null)
    {
        $data['driver_id'] = $DriverId;
        if(!is_null($insId)){
            $data['ins_id'] = $insId;
        }
        $RD = (new RegionDrivers())->arrFindFirst($data);
        if (false===$RD){
            return false;
        }
        return $RD->getRegionId();
    }

    // 获取骑手的真实姓名 通过ids
    public function getDriverRealNameByIds($driverIdList){
        if (!is_array($driverIdList)){
            $driverIdList = [$driverIdList];
        }
        // 去除0 null和重复值
        $driverIdList = array_values(array_unique(array_diff($driverIdList,[0, null])));
        if (empty($driverIdList)){
            return [];
        }
        $drivers = Drivers::arrFind([
            'id' => ['IN', $driverIdList]
        ], 'and', [
            'columns' => 'id, real_name AS realName'
        ])->toArray();
        $driverRealNames = [];
        foreach ($drivers as $driver){
            $driverRealNames[$driver['id']] = $driver['realName'];
        }
        return $driverRealNames;
    }

    // TODO: 删除骑手所有邮管业务关系
    public function DelPostOfficeDriverRelation($DriverId)
    {
        // 查询骑手-快递公司/区域/站点关系
        $RDS = $this->getRegionDriverSByDriverIds($DriverId, false);
        // 删除骑手-快递公司/区域/站点关系
        foreach ($RDS as $RD){
            $bol = $RD->delete();
            if (false === $bol){
                return $bol;
            }
        }
        // 查询有绑定车辆的骑手区域车辆绑定关系
        $VehicleData = new VehicleData();
        $RVS = $VehicleData->getRegionVehicleSByDriverIds($DriverId, false);
        // 获得车辆ID
        $bindVehicleIds = [];
        foreach ($RVS as $RV){
            $bindVehicleIds[] = $RV->vehicle_id;
        }
        // 删除骑手-快递公司车辆绑定
        $bol = $RVS->update([
            'bind_status' => 1,
            'driver_id' => 0,
            'bind_time' => 0,
            'update_time' => time(),
        ]);
        if (false === $bol){
            return $bol;
        }
        // 删除邮管车辆-骑手关系
        if(count($bindVehicleIds) > 0){
            // 查询车辆信息
            $VehicleS = Vehicle::find([
                'id IN ({Ids:array})',
                'bind' => [
                    'Ids' => $bindVehicleIds
                ]
            ]);
            // 更新车辆绑定关系
            $bol = $VehicleS->update([
                'driver_bind' => 1,
                'driver_id' => 0,
                'update_time' => time(),
            ]);
            if (false===$bol){
                return $bol;
            }
        }
        return true;
    }

    // 骑手违章扣分
    public function PeccancyDeductionScore($peccancyId)
    {
        //调用微服务
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 15020,
            'parameter' => [
                'peccancyId' => $peccancyId
            ],
        ],"post");
        $res = $result['content']['result'] ?? [];
        // 扣分数
        $processScore = $res['processScore'] ?? 0;
        return $processScore;
    }

    // 发送违章通知
    public function SendPeccancyNotification($mobile, $plateNum, $stringDate, $peccancyType)
    {
        //调用微服务
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 10100,
            'parameter' => [
                'msgType' => 1,
                'mobile' => $mobile,
                'plateNum' => $plateNum,
                'stringDate' => $stringDate,
                'peccancyType' => $peccancyType,
            ],
        ],"post");
        return $result;
    }

    // 查询城市违章短信发送开关
    public function getCityPeccancySMSSwitchByCityId($cityId)
    {
        return $this->getCityParamSwitchByCityId($cityId, 'noteNotice');
    }

    // 查询城市驾照无分锁车开关
    public function getCityLicenseLockCarSwitchByCityId($cityId)
    {
        return $this->getCityParamSwitchByCityId($cityId, 'lockCar');
    }

    // 查询城市系统参数开关
    private function getCityParamSwitchByCityId($cityId, $paramName)
    {
        // 获取违章锁车开关
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15018',
            'parameter' => [
                'cityId' => $cityId,
//                'paramName' => 'lockCar/noteNotice',
                'paramName' => $paramName,
            ]
        ],"post");
        // 参数值，1：打开，0：未打开
        $status = $result['content']['data']['value'] ?? 0;
        return $status ? true : false;
    }

    // 获取骑手所在城市
    public function getCityIdByDriverId($driverId)
    {
        // 查询骑手所属快递公司-》所属快递协会-》关联骑手驾照
        $RD = RegionDrivers::arrFindFirst([
            'driver_id' => $driverId,
        ]);
        if (false === $RD) {
            throw new DataException([500, '骑手未绑定快递公司']);
        }
        $insId = $RD->ins_id;
        return (new PostOfficeData())->getPostOfficeCityIdByExpressInsId($insId);
    }

    // 查询骑手是否能够操作解/锁车
    public function checkDriverLockVehicleLevel($vehicleId, $driverId)
    {
        // 查询车辆最后的解/锁车记录
        $vehicleLockRecord = VehicleLockRecord::arrFindFirst(['vehicle_id'=>$vehicleId], ['order'=>'create_time DESC']);
        if (false == $vehicleLockRecord){
            return true;
        }
        // 如为解锁操作，不校验
        if (2 == $vehicleLockRecord->action){
            return true;
        }
        // 查询骑手绑车时间
        $SV = StoreVehicle::arrFindFirst([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
        ]);
        // 骑手绑车时间 大于 最后锁车时间 无需校验
        if ($SV){
            if ($SV->bind_time > $vehicleLockRecord->create_time){
                return true;
            }
        }
        $scenesId = $vehicleLockRecord->scenes_id;
        // 查询骑手锁车优先级 和 最后的锁车场景优先级
        $VLSs = VehicleLockScenes::arrFind([
            'id' => $scenesId,
            'code' => VehicleLockScenes::CODE_DRIVER_LOCK,
        ], 'or');
        $VLSs = $VLSs->toArray();
        $driverLockLevel = 0;
        $compareLockLevel = 0;
        foreach ($VLSs as $VLS){
            if ($scenesId == $VLS['id']){
                $compareLockLevel = $VLS['level'];
            }
            if (VehicleLockScenes::CODE_DRIVER_LOCK == $VLS['code']){
                $driverLockLevel = $VLS['level'];
            }
        }
        return $driverLockLevel<$compareLockLevel ? false : true;
    }

    // 骑手解锁车辆通过udid
    public function UnLockVehicleOfDriver($vehicleId, $driverId)
    {
        // 查询场景id
        $scenesId = VehicleLockScenes::getScenesIdByScenesCode(VehicleLockScenes::CODE_DRIVER_LOCK);
        // 插入解锁记录
        VehicleLockRecord::createUnLockRecord($vehicleId, $scenesId, [
            'driver_id' => $driverId
        ]);
        // 发起解锁
        $VehicleData = new VehicleData();
        $bol = $VehicleData->UnLock($vehicleId, "【骑手解锁】骑手id:{$driverId}");
        if (false==$bol){
            throw new DataException([500, $VehicleData->getLockErrorMsg()]);
        }
    }

    // 骑手锁车
    public function LockVehicleOfDriver($vehicleId, $driverId)
    {
        // 查询场景id
        $scenesId = VehicleLockScenes::getScenesIdByScenesCode(VehicleLockScenes::CODE_DRIVER_LOCK);
        // 插入锁车记录
        VehicleLockRecord::createLockRecord($vehicleId, $scenesId, [
            'driver_id' => $driverId
        ]);
        // 发起锁车
        $VehicleData = new VehicleData();
        $bol = $VehicleData->Lock($vehicleId, "【骑手锁车】骑手id:{$driverId}");
        if (false==$bol){
            throw new DataException([500, $VehicleData->getLockErrorMsg()]);
        }
    }

    // 骑手实人认证后更新资料

    /**
     * @param $driverId
     * @param $ticketId
     * @param $biz 认证场景标识:RealManIdentify(RPBasic认证方案的场景标识)    IDCardIdentify(RPBioID认证方案场景标识)
     * @return bool
     * @throws DataException
     */
    public function PersonCertEndUpdateDriverInfo($driverId, $ticketId, $biz)
    {
        // 查询认证结果
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10026",
            'parameter' => [
                'biz' => $biz,
                'ticketId' => $ticketId
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'],$result['msg']]);
        }
        // 认证状态(-1 未认证, 0 认证中, 1 认证通过, 2 认证不通过)
        $status = $result['content']['status'];

        if (1 != $status){
            throw new DataException([500,'处理认证失败']);
        }
        // 更新骑手表的信息
        $upRes = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => "60012",
            'parameter' => [
                'insId' => '-1', // 接口必传，否则报错
                'id' => $driverId,
                'realName' => $result['content']['realPersonMaterial']['name'],
                'identify' => $result['content']['realPersonMaterial']['identificationNumber'],
                'imgOppositeUrl' => $result['content']['realPersonMaterial']['idCardFrontPic'],
                'imgFrontUrl' => $result['content']['realPersonMaterial']['idCardBackPic'],
                // m 代表男性，f 代表女性 性别:1男 2女
                'sex' => 'm'==$result['content']['realPersonMaterial']['sex'] ? 1 : 2,
            ]
        ],"post");
        // 失败返回
        if ($upRes['statusCode'] != '200') {
            throw new DataException([500, '骑手信息更新失败']);
        }
        // 查询用户是否已经有实名认证信息
        $info = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => "60064",
            'parameter' => [
                'driverIdList' => [$driverId]
            ]
        ],"post");
        if (200 != $info['statusCode']){
            throw new DataException([$info['statusCode'], $info['msg']]);
        }
        // 骑手认证信息
        $params = [
            'driverId'              => $driverId,
            'identificationNumber'  => $result['content']['realPersonMaterial']['identificationNumber'],
            'isAuthentication'      => 2,
            'isGetmaterials'        => 2,
            'idCardType'            => $result['content']['realPersonMaterial']['idCardType'],
            'address'               => $result['content']['realPersonMaterial']['address']['province']['text']
                .$result['content']['realPersonMaterial']['address']['city']['text']
                .$result['content']['realPersonMaterial']['address']['area']['text']
                .$result['content']['realPersonMaterial']['detail'],
            'idCardFrontPic'        => $result['content']['realPersonMaterial']['idCardFrontPic'],
            'idCardBackPic'         => $result['content']['realPersonMaterial']['idCardBackPic'],
            'facePic'               => $result['content']['realPersonMaterial']['facePic'],
            'ethnicGroup'           => $result['content']['realPersonMaterial']['ethnicGroup'],
            'getmaterialsTime'      => time(),
            'idCardStartDate'       => $result['content']['realPersonMaterial']['idCardStartDate'],
            'idCardExpiry'          => $result['content']['realPersonMaterial']['idCardExpiry'],
            'sex'                   => $result['content']['realPersonMaterial']['sex'],
            'provinceId'            => $result['content']['realPersonMaterial']['address']['province']['value'],
            'cityId'                => $result['content']['realPersonMaterial']['address']['city']['value'],
        ];
        // 判断骑手是否存在认证信息记录 有 - 修改同步信息  无 - 新增记录
        if (isset($info['content']['data'][0])) {
            // 更新骑手的信息
            $params['id'] = $info['content']['data'][0]['id'];
            // 请求微服务接口，更新骑手认证信息记录
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => "60062",
                'parameter' => $params
            ],"post");
        } else {
            // 请求微服务接口新增骑手认证记录
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => "60061",
                'parameter' => $params
            ],"post");
        }
        if (200 != $result['statusCode']) {
            throw new DataException($result['statusCode'], $result['msg']);
        }
        return true;
    }

}

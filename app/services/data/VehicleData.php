<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\service\DeviceModel;
use app\models\service\PostofficeVehicleLog;
use app\models\service\Vehicle;
use app\models\service\VehicleLockQueue;
use app\models\service\StoreVehicle;
use app\models\service\RegionVehicle;
use app\models\service\YearlycheckTask;
use app\models\users\User;
use Phalcon\Paginator\Adapter\QueryBuilder;


class VehicleData extends BaseData
{
    // 锁车/解锁 接口报错信息
    private $LockErrorMsg;

    // 解锁锁车
    const LOCK_VEHICLE = 1;
    const UNLOCK_VEHICLE = 2;

    // 获取多条车辆信息 通过idlist
    public function getVehicleByIds($ids, $Convert=false)
    {
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0])));
        // 空list微服务会返回全部数据
        if (empty($ids)) return[];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 60008,
            'parameter' => [
                'idList' => $ids,
            ]
        ],"post");
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            throw new DataException([500, '未获取到车辆信息']);
        }
        $vehicles = $result['content']['vehicleDOS'];
        if ($Convert){
            $tmp = [];
            foreach ($vehicles as $vehicle){
                $tmp[(string)$vehicle['id']] = $vehicle;
            }
            $vehicles = $tmp;
        }
        return $vehicles;
    }

    // 获取单条车辆信息 通过id
    public function getVehicleById($id)
    {
        $vehicles = $this->getVehicleByIds([$id]);
        if (count($vehicles)!=1) {
            throw new DataException([500, '未获取到车辆信息']);
        }
        $vehicle = $vehicles[0];
        return $vehicle;
    }

    // 还车信息整合[门店]
    public function getRetVehicleTime($vehicleId)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 10024,
            'parameter' => [
                'vehicleId' => $vehicleId,
            ]
        ],"post");
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            throw new DataException([500, '车辆门店服务异常-10024']);
        }
        if (!isset($result['content']['data'][0])){
            return false;
        }
        $RemainingReturnTime = $result['content']['data'][0]['readyRentTime'] + 1800 - time();
        if ($RemainingReturnTime > 0){
            return $RemainingReturnTime;
        }
        return false;
    }

    /**
     * 通过支付单号删除锁车队列
     * @param $businessSn
     * @return bool|null
     */
    public function delLockVehicleByBusinessSn($businessSn)
    {
        $VehicleRentOrder =  $this->modelsManager->createBuilder()
            ->columns('v.*')
            ->addfrom('app\models\order\PayBill', 'b')
            ->where('b.business_sn = :business_sn:', ['business_sn' => $businessSn])
            // 支付单有效
            ->andWhere('b.is_delete=0')
            ->join('app\models\order\VehicleRentOrder', 'v.pay_bill_id = b.id','v')
            ->getQuery()
            ->execute()
            ->getFirst();
        // 无关联租车单,安全返回
        if (false === $VehicleRentOrder) return null;
        $vehicleId = $VehicleRentOrder->vehicle_id;
        // 发起删除
        $bol = (new VehicleLockQueue())->del($vehicleId);
        // 记录操作日志
        $tip = $bol ? '成功' : '失败';
        $log = "【锁车队列删除{$tip}】场景:支付单回调,车辆id:{$vehicleId},支付单号:{$businessSn}";
        $this->logger->info($log);
        $this->busLogger->recordingOperateLog($log);
        return $bol;
    }

    // 获取门店车辆关系通过车辆id
    public function getStoreVehicleSByVehicleIds($vehicleIds, $arr=true)
    {
        $vehicleIds = array_values($vehicleIds);
        $SVS = StoreVehicle::find([
            'vehicle_id IN ({vehicleIds:array}) ',
            'bind' => [
                'vehicleIds' => $vehicleIds
            ]
        ]);
        return $arr ? $SVS->toArray() : $SVS;
    }
    // 获取区域车辆关系通过车辆id
    public function getRegionVehicleSByVehicleIds($vehicleIds, $arr=true)
    {
        $vehicleIds = array_values($vehicleIds);
        $RVS = RegionVehicle::find([
            'vehicle_id IN ({vehicleIds:array}) ',
            'bind' => [
                'vehicleIds' => $vehicleIds
            ]
        ]);
        return $arr ? $RVS->toArray() : $RVS;
    }

    // 获取区域车辆关系通过骑手id
    public function getRegionVehicleSByDriverIds($driverIds, $arr=true, $insId=null)
    {
        if (is_array($driverIds)){
            $data['driver_id'] = ['IN', $driverIds];
        }else{
            $data['driver_id'] = $driverIds;
        }
        if (!is_null($insId)) $data['ins_id'] = $insId;
        $RVS = (new RegionVehicle())->arrFind($data);
        return $arr ? $RVS->toArray() : $RVS;
    }

    // 快递公司解锁锁车
    public function ExpressCompanyLockVehicle($vehicleId, $userId, $insId, $action)
    {
        if (!($vehicleId>0)){
            throw new DataException([500, '参数错误，请刷新页面重新尝试']);
        }
        $RV = RegionVehicle::arrFindFirst([
            'vehicle_id' => $vehicleId,
        ]);
        if (false == $RV){
            throw new DataException([500, '车辆未绑定快递公司']);
        }
        if ($insId != $RV->ins_id){
            throw new DataException([500, '车辆不属于当前快递公司']);
        }
        // 查询车辆
        $vehicle = Vehicle::arrFindFirst([
            'id' => $vehicleId,
        ]);
        if (false===$vehicle){
            throw new DataException([500, '车辆不存在']);
        }
        // 查询当前用户
        $user = User::arrFindFirst(['id'=>$userId]);
        // 记录日志
        $bol = (new PostofficeVehicleLog())->create([
            'vehicle_id' => $vehicleId,
            'operator_name' => $user->real_name ?? '',
            'operator_id' => $userId,
            'operator_type' => PostofficeVehicleLog::OPERATOR_TYPE_USER,
            'operate_description' => '后台' . (self::LOCK_VEHICLE == $action ? '锁车' : '解锁'),
            'status' => 1,
            'create_time' => time(),
        ]);
        if (false == $bol){
            throw new DataException([500, '操作失败，请重试']);
        }
        // 发起锁车/解锁
        if (self::LOCK_VEHICLE == $action){
            $bol = $this->Lock($vehicle->id, "【快递公司后台锁车】用户id：{$userId}");
        }else{
            $bol = $this->UnLock($vehicle->id, "【快递公司后台解锁】用户id：{$userId}");
        }
        if (false===$bol){
            throw new DataException([500, $this->getLockErrorMsg()]);
        }
        return true;
    }

    /**
     * 发送锁车指令
     * @param $vehicleId 车辆id
     * @param null $log 日志内容
     * @return bool 结果
     */
    public function Lock($vehicleId, $log=null)
    {
        // 发送锁车指令
        $result = $this->curl->httpRequest($this->Zuul->vehicle, [
            'code' => 60110,
            'parameter' => [
                'idList' => [$vehicleId]
            ],
        ], "POST");
        $bol = true;
        if ($result['statusCode'] != 200 || !empty($result['content']['errorMsg'])){
            $bol = false;
        }
        if (false === $bol) {
            $this->LockErrorMsg = isset($result['msg']) ? $result['msg'] : '锁车失败';
        };
        // 记录日志
        $log = $log ?? '【VehicleData锁车】';
        $this->logger->info("{$log} vehicleId:{$vehicleId}" . ($bol ? '【成功】' : "【失败】{$this->LockErrorMsg}"));
        return $bol;
    }

    /**
     * 发送解锁指令
     * @param $vehicleId 车辆id
     * @param null $log 日志内容
     * @return bool 结果
     */
    public function UnLock($vehicleId, $log=null)
    {
        // 发送解锁指令
        $result = $this->curl->httpRequest($this->Zuul->vehicle, [
            'code' => 60111,
            'parameter' => [
                'idList' => [$vehicleId]
            ],
        ], "POST");
        $bol = true;
        if ($result['statusCode'] != 200 || !empty($result['content']['errorMsg'])){
            $bol = false;
        }
        if (false === $bol) {
            $this->LockErrorMsg = isset($result['msg']) ? $result['msg'] : '解锁失败';
        };
        // 记录日志
        $log = $log ?? "【VehicleData解锁】";
        $this->logger->info("{$log} vehicleId:{$vehicleId}" . ($bol ? '【成功】' : "【失败】{$this->LockErrorMsg}"));
        return $bol;
    }

    // 获取锁车/解锁 接口错误信息
    public function getLockErrorMsg(){
        return $this->LockErrorMsg ?? false;
    }


    /**
     * 存量二手车分页
     * 根据用户ID查找绑定车辆信息
     */
    public function getVehiclePageByUserId($userId,$pageNum,$pageSize,$indexText)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('v.id,v.udid,v.bianhao,v.vin,v.plate_num,s.create_time')
            ->addFrom('app\models\service\Vehicle','v')
            ->rightJoin('app\models\service\VehicleSecondhand','v.id = s.vehicle_id','s')
            ->andWhere('s.user_id = :user_id:',['user_id' => $userId])
            ->andWhere('v.udid like :indexText: or v.bianhao like :indexText: or v.vin like :indexText: or v.plate_num like :indexText:',['indexText' => '%'.$indexText.'%'])
            ->orderBy('s.create_time desc');

        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();
        $result = $this->dataIntegration($pages);
        return $result;
    }


    // 查询当前年检完成状态
    public function getYaerCheckStatusByVehicleId($vehicleId)
    {
        // 查询车辆最后一条年检单的最后处理状态
        $task =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\YearlycheckTask','yt')
            ->leftJoin('app\models\service\YearlycheckAudit', 'ya.id = yt.last_audit_id','ya')
            ->andWhere('yt.vehicle_id = :vehicleId:', ['vehicleId'=>$vehicleId])
            ->columns('yt.id, ya.id, ya.status')
            ->orderBy('yt.id DESC')
            ->getQuery()
            ->getSingleResult();
        // 无任务算作完成
        if (!$task || 2==$task->status){
            return true;
        }
        return false;
    }




    public static function getTypeId($insId) {
        if ($insId == 11305) {
            return 1;
        } elseif ($insId == 11306) {
            return 4;
        }  elseif ($insId == 11307) {
            return 2;
        } else {
            return 3;
        }
    }


    public function getDeviceModel() {
        $deviceModel = DeviceModel::find()->toArray();
        $result = [];
        foreach ($deviceModel as $item) {
            $result[$item['id']] = $item['ins_id'];
        }
        return $result;
    }

}

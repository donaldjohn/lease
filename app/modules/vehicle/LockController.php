<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/1
 * Time: 10:23
 */
namespace app\modules\vehicle;

use app\models\service\BaseModel;
use app\models\service\PostofficeVehicleLog;
use app\models\service\RegionVehicle;
use app\models\service\Vehicle;
use app\models\service\VehicleLockQueue;
use app\models\service\VehicleLockRecord;
use app\models\service\VehicleLockScenes;
use app\models\users\User;
use app\modules\BaseController;
use app\services\data\VehicleData;
use Phalcon\Mvc\Model\Transaction\Manager;

class LockController extends BaseController
{
    /**
     * 锁车车辆列表
     */
    public function IndexAction()
    {
        $search = $this->request->getQuery('search','string',null);
        $batch_num = $this->request->getQuery('batch_num','string',null);
        $lock_type = $this->request->getQuery('lock_type','int');
        $create_time_begin = $this->request->getQuery('create_time_begin','int');
        $create_time_end = $this->request->getQuery('create_time_end','int');
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);

        $storeIds = [];
        $insId = $this->authed->insId;
        if ($insId > 0 && $this->authed->userType != 11) {
            $model_store = $this->modelsManager->createBuilder()
                ->addFrom('app\models\users\Institution', 't')
                ->join('app\models\users\Store', 's.ins_id = t.id', 's')
                ->where('t.parent_id = :insId: and t.is_delete = 0 and t.status = 1', array('insId' => $insId))
                ->columns('s.id')
                ->getQuery()
                ->execute()
                ->toArray();
        } else if ($insId > 0 && $this->authed->userType == 11) {
            $model_store = $this->modelsManager->createBuilder()
                ->addFrom('app\models\users\Institution', 't')
                ->join('app\models\users\Institution', 'i1.id = t.parent_id','i1')
                ->join('app\models\users\Store', 's.ins_id = t.id', 's')
                ->where('i1.parent_id = :insId: and i1.is_delete = 0 and i1.status = 1 and t.is_delete = 0 and t.status = 1 ', array('insId' => $insId))
                ->columns('s.id')
                ->getQuery()
                ->execute()
                ->toArray();
        }
        $model =$this->modelsManager->createBuilder()
            ->addfrom('app\models\service\VehicleLockQueue','t')
            ->join('app\models\service\VehicleUsage',
                "vu.vehicle_id = t.vehicle_id AND vu.use_attribute = 2", 'vu')
            ->leftJoin('app\models\service\Vehicle', 'v.id = t.vehicle_id', 'v')
            ->where('t.lock_status = :lock_status: AND v.is_lock = :is_lock:', array('lock_status' => 2, 'is_lock' => 0));
        if ($insId) {
            if ($model_store) {
               foreach ($model_store as $key => $val) {
                    $storeIds[] = $val['id'];
               }
            }
            $model->join('app\models\service\StoreVehicle', 's.vehicle_id = v.id','s')
                ->andWhere('s.store_id IN ({storeIds:array})', $parameters = ['storeIds' => $storeIds]);
        }
        if ($search) {
            $model->andWhere('v.udid LIKE :udid: OR v.bianhao LIKE :bianhao: OR v.vin LIKE :vin:',
                $parameters = ['udid' => '%'. $search. '%','bianhao' => '%'. $search. '%','vin' => '%'. $search. '%']);
        }
        if ($lock_type) {
            $model->andWhere('t.lock_type = :lock_type:', $parameters = ['lock_type' => $lock_type]);
        }
        if ($batch_num) {
            $model->andWhere('t.batch_num LIKE :batch_num:', $parameters = ['batch_num' => '%'. $batch_num . '%']);
        }
        if ($create_time_begin) {
            $model->andWhere('t.create_time BETWEEN ?1 AND ?2', $parameters = [1=> $create_time_begin, 2=>$create_time_end]);
        }
        $model_count = clone $model;
        $count = $model_count->columns('t.id')->getQuery()->execute()->toArray();
        $data = [];
        if ($count > 0 ) {
            $data = $model->columns('t.vehicle_id,v.vin,v.bianhao,v.udid,vu.use_attribute,t.lock_comment,t.create_time,
            t.lock_type,t.point_name,t.batch_num,t.id')
                ->orderBy('t.id desc')
                ->limit($pageSize, ($pageNum-1)*$pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
            foreach ($data as $key => &$val) {
                $val['create_time'] =  $val['create_time'] ? date('Y-m-d H:i:s',  $val['create_time']) : '--';
            }
        }
        return $this->toSuccess($data, ['pageNum'=> $pageNum, 'total' => count($count), 'pageSize' => $pageSize]);
    }

    /**
     * 批量锁车
     */
    public function UpdateAction()
    {
        $data = $this->request->getJsonRawBody(true);//print_r($data);exit;
        if (empty($data['ids'])) {
            return $this->toError('500', '未填写锁车ID');
        }
        if (count($data['ids']) > 20) {
            return $this->toError('500', '一次性最多能锁定20辆车');
        }

        $manager = new Manager();
        $transaction = $manager->setDbService('dw_service')->get();
        try {
            foreach ($data['ids'] as $key => $val) {
                $model = VehicleLockQueue::findFirst($val);
                if ($model->lock_status == VehicleLockQueue::CHECK_STATUS) {
                    $model->setTransaction($transaction);
                    $model->lock_status = VehicleLockQueue::CHECKED_STATUS;
                    if ($model->save() == false) {
                        $transaction->rollback($model->getMessages());
                    }
                }
            }
            $transaction->commit();
            $this->toSuccess();
        } catch (\Exception $e) {
            $this->toError('500', $e->getMessage());
        }
    }

    /**
     * 按照批次锁车
     */
    public function MultiAction()
    {
        $data = $this->request->getJsonRawBody(true);
        if (!isset($data['batch_num'])) {
            return $this->toError('500', '未填写锁车批次');
        }
        $conditions = 'batch_num = :batch_num: AND lock_status = :lock_status:';
        $parameters = [
            'batch_num' => $data['batch_num'],
            'lock_status' => VehicleLockQueue::CHECK_STATUS,
        ];
        $model = VehicleLockQueue::find([$conditions, 'bind' => $parameters]);
        if (!$model) {
            $this->toError('500', '该批次不存在待审核的车辆');
        }
        $model->update(['lock_status' => VehicleLockQueue::CHECKED_STATUS]);
        $this->toSuccess();
    }

    // 租赁后台锁车
    public function RentLockAction()
    {
        // 判断是否是运营商/内部用户
        if (0 != $this->authed->insId && 9 != $this->authed->userType){
            return $this->toError(500, '非运营商/内部用户，不可操作');
        }
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? 0;
        if (!($vehicleId>0)){
            return $this->toError(500, '参数错误，请刷新页面重新尝试');
        }
        // 查询车辆
        $vehicle = Vehicle::arrFindFirst([
            'id' => $vehicleId,
        ]);
        if (false===$vehicle){
            return $this->toError(500, '车辆不存在');
        }
        // 查询场景id
        $scenesId = VehicleLockScenes::getScenesIdByScenesCode(VehicleLockScenes::CODE_BACKSTAGE_LOCK);
        // 插入锁车记录
        VehicleLockRecord::createLockRecord($vehicleId, $scenesId, [
            'user_id' => $this->authed->userId
        ]);
        // 发起锁车
        $vehicleData = new VehicleData();
        $bol = $vehicleData->Lock($vehicle->id, "【租赁后台锁车】用户id：{$this->authed->userId}");
        if (false===$bol){
            return $this->toError(500, $vehicleData->getLockErrorMsg());
        }
        return $this->toSuccess();
    }

    // 租赁后台解锁车辆
    public function RentUnLockAction()
    {
        // 判断是否是运营商/内部用户
        if (0 != $this->authed->insId && 9 != $this->authed->userType){
            return $this->toError(500, '非运营商/内部用户，不可操作');
        }
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? 0;
        if (!($vehicleId>0)){
            return $this->toError(500, '参数错误，请刷新页面重新尝试');
        }
        // 查询车辆
        $vehicle = Vehicle::arrFindFirst([
            'id' => $vehicleId,
        ]);
        if (false===$vehicle){
            return $this->toError(500, '车辆不存在');
        }
        // 查询场景id
        $scenesId = VehicleLockScenes::getScenesIdByScenesCode(VehicleLockScenes::CODE_BACKSTAGE_LOCK);
        // 插入解锁记录
        VehicleLockRecord::createUnLockRecord($vehicleId, $scenesId, [
            'user_id' => $this->authed->userId
        ]);
        // 发起解锁
        $vehicleData = new VehicleData();
        $bol = $vehicleData->UnLock($vehicle->id, "【租赁后台解锁车辆】用户id：{$this->authed->userId}");
        if (false===$bol){
            return $this->toError(500, $vehicleData->getLockErrorMsg());
        }
        return $this->toSuccess();
    }

    // 快递公司后台锁车
    public function ExpressCompanyLockAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? 0;
        (new VehicleData())->ExpressCompanyLockVehicle($vehicleId, $this->authed->userId, $this->authed->insId, VehicleData::LOCK_VEHICLE);
        return $this->toSuccess();
    }

    // 快递公司后台解锁
    public function ExpressCompanyUnLockAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? 0;
        (new VehicleData())->ExpressCompanyLockVehicle($vehicleId, $this->authed->userId, $this->authed->insId, VehicleData::UNLOCK_VEHICLE);
        return $this->toSuccess();
    }

}
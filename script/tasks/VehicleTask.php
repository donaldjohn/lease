<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/1
 * Time: 9:26
 */
use Phalcon\Cli\Task;
use app\models\service\Vehicle;
use app\models\service\VehicleLockQueue;
use Phalcon\Mvc\Model\Transaction\Manager;
use app\models\users\Store;
use app\models\service\StoreVehicle;

class VehicleTask extends Task
{
    /**
     * 锁车脚本 （未绑定门店、未绑定骑手，订单到期）
     */
    public function LockAction()
    {
        $this->UnBindStore();//未绑定门店
        $this->VehicleRentStatus(StoreVehicle::UN_RENT);//未绑定骑手
        $this->VehicleRentStatus(StoreVehicle::UN_RETURN);//订单逾期
    }

    /**
     * 未绑定
     */
    private function UnBindStore()
    {
        // 不在锁车队列、车辆未锁、未绑定门店或站点、超出期望锁车时间、最后锁车时间超过十分钟
        $model = $this->modelsManager->createBuilder()
            ->addFrom('app\models\service\Vehicle','t')
            ->join('app\models\service\VehicleUsage', 't.id = u.vehicle_id', 'u')
            ->leftJoin('app\models\service\StoreVehicle', 's.vehicle_id = t.id', 's')
            ->where('t.lock_queue = :lock_queue: AND t.is_lock = :is_lock: AND t.except_lock_time < :except_lock_time: 
            AND t.last_lock_time < :last_lock_time: AND u.use_attribute = :use_attribute: AND s.id is null',
                [
                    'lock_queue' => Vehicle::NOT_IN_QUEUE,
                    'is_lock' => Vehicle::IS_NOT_LOCK,
                    "except_lock_time" => time(),
                    "last_lock_time" => time() + 600,
                    "use_attribute" => Vehicle::STORE_ATTRIBUTE,
                ])
            ->columns('t.id vehicle_id,t.udid')
            ->getQuery()
            ->execute()
            ->toArray();
        foreach ($model as $key => $val) {
            $this->InsertAction($val, VehicleLockQueue::UNBIND_STORE_TYPE);
        }
    }
    /**
     * 未绑定骑手和未还车状态
     * 1未绑定骑手
     * 3未还车
     * @param $rent_status
     */
    private function VehicleRentStatus($rent_status)
    {
        // 不在锁车队列、车辆未锁、车辆状态未出租或逾期、超出期望锁车时间、最后锁车时间超过十分钟
        $model = $this->modelsManager->createBuilder()
            ->addFrom('app\models\service\StoreVehicle', 's')
            ->leftJoin('app\models\service\Vehicle', 's.vehicle_id = t.id', 't')
            ->leftJoin('app\models\service\VehicleUsage', 't.id = u.vehicle_id', 'u')
            ->where('t.lock_queue = :lock_queue: AND t.is_lock = :is_lock: AND s.rent_status = :rent_status: AND
                t.except_lock_time < :except_lock_time: AND t.last_lock_time < :last_lock_time: AND  u.use_attribute = :use_attribute:',
                [
                    'lock_queue' => Vehicle::NOT_IN_QUEUE,
                    'is_lock' => Vehicle::IS_NOT_LOCK,
                    'rent_status' => $rent_status,
                    "except_lock_time" => time(),
//                    'has_bind' => Vehicle::NOT_BIND,字段已弃用
                    "last_lock_time" => time() + 60 ,
                    "use_attribute" => Vehicle::STORE_ATTRIBUTE,
                ])
            ->columns('t.udid, t.id vehicle_id, s.store_id')
            ->getQuery()
            ->execute()
            ->toArray();
        if ($rent_status == StoreVehicle::UN_RENT) {
            $type = VehicleLockQueue::UNBIND_RIDER_TYPE;
        } else {
            $type = VehicleLockQueue::ORDER_EXPIRE_TYPE;
        }
        foreach ($model as $key => $val) {
            $this->InsertAction($val, $type);
        }
    }

    /**
     * @param $prams
     */
    private function InsertAction($prams, $type)
    {
        $conditions = 'vehicle_id = :vehicle_id:';
        $parameters = [
            'vehicle_id' => $prams['vehicle_id'],
        ];
        $res = VehicleLockQueue::findFirst([$conditions,'bind' => $parameters]);
        if ($res) {
            return ;
        }
        $manager = new Manager();
        $transaction = $manager->setDbService('dw_service')->get();
        $model = new VehicleLockQueue();
        $model->vehicle_id = $prams['vehicle_id'];
        $model->udid = $prams['udid'];
        $model->create_time = time();
        $model->lock_type = $type;
        $model->lock_comment = isset(VehicleLockQueue::$LOCK_TYPR_LIST[$type]) ? VehicleLockQueue::$LOCK_TYPR_LIST[$type] : '--';
        //如果存在门店，写入门店名称
        if (isset($prams['store_id'])) {
            $model->point_id = $prams['store_id'];
            $model->point_name = Store::getStoreNameById($prams['store_id']);
        }
        $model->setTransaction($transaction);
        if ($model->save() === false) {
            $messages = $model->getMessages();
            $msg = '';
            foreach ($messages as $message) {
                $msg .= $message->getMessage();
            }
            $transaction->rollback($msg);
        }
        $vehicle = Vehicle::findFirst($prams['vehicle_id']);
        $vehicle->lock_queue = 2;
        $vehicle->update_time = time();
        $vehicle->setTransaction($transaction);
        if ($vehicle->save() === false) {
            $messages = $vehicle->getMessages();
            $msg = '';
            foreach ($messages as $message) {
                $msg .= $message->getMessage();
            }
            $transaction->rollback($msg);
        }
        $transaction->commit();
    }
}
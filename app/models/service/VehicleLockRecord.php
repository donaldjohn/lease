<?php
namespace app\models\service;

// 解/锁车记录表
class VehicleLockRecord extends BaseModel
{
    // 动作 1-锁车 2-解锁
    const ACTION_LOCK = 1;
    const ACTION_UNLOCK = 2;

    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_vehicle_lock_record');
    }

    // 创建锁车记录
    public static function createLockRecord($vehicleId, $scenesId, $data=[])
    {
        $data['vehicle_id'] = $vehicleId;
        $data['scenes_id'] = $scenesId;
        $data['action'] = self::ACTION_LOCK;
        return self::createRecord($data);
    }

    // 创建解锁记录
    public static function createUnLockRecord($vehicleId, $scenesId, $data=[])
    {
        $data['vehicle_id'] = $vehicleId;
        $data['scenes_id'] = $scenesId;
        $data['action'] = self::ACTION_UNLOCK;
        return self::createRecord($data);
    }

    public static function createRecord($data)
    {
        // 插入解/锁车记录
        $vehicleLockRecord = new self();
        $vehicleLockRecord->create_time = time();
        return $vehicleLockRecord->create($data);
    }

}

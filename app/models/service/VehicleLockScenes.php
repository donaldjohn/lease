<?php
namespace app\models\service;

// 锁车场景表
class VehicleLockScenes extends BaseModel
{
    // 场景code
    const CODE_DRIVER_LOCK = 'driver'; // 骑手锁车
    const CODE_BACKSTAGE_LOCK = 'backstage'; // 后台锁车
    const CODE_SCRIPT_LOCK = 'script'; // 脚本锁车

    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_vehicle_lock_scenes');
    }

    // 获取场景id通过场景code
    public static function getScenesIdByScenesCode($ScenesCode)
    {
        $vehicleLockScenes = self::arrFindFirst([
            'code' => $ScenesCode
        ]);
        return $vehicleLockScenes->id ?? 0;
    }

}

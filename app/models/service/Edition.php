<?php
namespace app\models\service;

// APP版本信息表
class Edition extends BaseModel
{
    // 设备类型 1:ios 2:android
    static private $equipmentType = [
        'ios' => 1,
        'android' => 2,
    ];

    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_edition");
        // 设置动态更新，防止更新时清空未传字段数据
        $this->useDynamicUpdate(true);
    }

    /**
     * 获取设备类型代码
     * @param $equipment
     * @return int|mixed
     */
    public static function GetEquipmentTypeCode($equipment)
    {
        $equipment = strtolower($equipment);
        return isset(self::$equipmentType[$equipment]) ? self::$equipmentType[$equipment] : 0;
    }
}

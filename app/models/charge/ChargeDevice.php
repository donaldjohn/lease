<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 13:31
 */
namespace app\models\charge;

class ChargeDevice extends BaseModel
{
    /**
     * 换电柜
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_charge_device");
    }

    /**
     * 获取区域下面的充电桩
     * @param $type
     * @param $area_id
     * @return array
     */
    public function getAreaData($type, $area_id)
    {
        $count = 0;
        $item =[];
        $map =[];
        if ($type == 1) {
            $ssids = $this::find([
                "columns" => "ssid, identifier, total_num"
            ])->toArray();
        } else {
            if ($type == 2) {
                $conditions = "province_id = :province_id:";
                $parameters = ['province_id' => $area_id];
            } else {
                $conditions = "city_id = :city_id:";
                $parameters = ['city_id' => $area_id];
            }
            $ssids = $this::find([
                $conditions,
                "bind" => $parameters,
                "columns" => "ssid, identifier, total_num",
            ])->toArray();
        }
        if ($ssids) {
            foreach ($ssids as $key => $val) {
                $item[] = $val['ssid'];
                $count += $val['total_num'];
                $map[$val['ssid']] = $val['identifier'];
            }
        }

        return ["total" => $count, 'ssids'=>  $item, 'map' => $map];
    }
    /**
     * 原始手机转化为加密的
     * 手机ssid 解析规则
     * 如果ssid是数字 前面加0
     * 如果是
     * @param $ssid
     * @return string
     */
    public static function encodeSsid($ssid)
    {
        $ssid = str_split($ssid);
        foreach ($ssid as $key => $val) {
            if (preg_match('/^[0-9]+$/',$val)) {
                $ssid[$key] = '0'.$val;
            }
            if (preg_match('/^[a-zA-Z]+$/',$val)) {
                $ssid[$key] = dechex(ord($val)) - 10;
            }
        }
       return implode('', $ssid);
    }
    /**
     * 解密手机号
     * @param $ssid
     * @return string
     */
    public static function decodeSsid($ssid)
    {
        $count = strlen($ssid);
        $ssid = str_split($ssid);
        $data = [];
        for ($i = 0; $i < $count - 1; $i = $i + 2) {
            if ($ssid[$i] > 0) {
                $data[] = chr(hexdec($ssid[$i] * 10 + $ssid[$i+1]  + 10));
            } else {
                $data[] = $ssid[$i + 1 ];
            }
        }
        return implode('', $data);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/29
 * Time: 14:06
 */
namespace app\modules\charge;

use app\models\charge\ChargeDevice;
use app\models\charge\ChargeHourlySummary;
use app\models\charging\BoxSocketNewchongdian;
use app\models\service\Area;
use app\modules\BaseController;

class ChartController extends BaseController
{
    /**
     * 异常
     */
    public function FaultAction()
    {
        $type = $this->request->get('type');
        $area_id = $this->request->get('area_id');
        $sql = "SELECT `ID`,`DIANYA`,`DIANLIU`,`WEIDU`,`SSID`,`DEVONE`
        FROM`box_socket_newchongdian` WHERE
        CONVERT(`DIANYA`,SIGNED) > 90
        OR CONVERT(`DIANLIU`, SIGNED) > 20
        OR CONVERT(`WEIDU`,SIGNED) > 80";
        $query = $this->getDi()->getShared('charging')->query($sql);
        $result = [];
        while ($res = $query->fetch()) {
            $result[] = $res;
        }
        if (!$result) {
            return $this->toSuccess();
        }
        $data = [];
        $ssids = (new ChargeDevice())->getAreaData($type, $area_id);
        foreach ($result  as $key => $val) {
            $decodeSsid = ChargeDevice::decodeSsid($val['SSID']);
            if (in_array($decodeSsid, $ssids['ssids'])) {
                if ($val['DIANYA'] > 90) {
                    $val['content'] = (isset($ssids['map'][$decodeSsid]) ? "编号".$ssids['map'][$decodeSsid]. "-": '') .  $val['DEVONE']. "柜电压异常";
                }
                if ($val['DIANLIU'] > 20) {
                    $val['content'] = (isset($ssids['map'][$decodeSsid]) ? "编号".$ssids['map'][$decodeSsid]. "-" : '').$val['DEVONE'] . "柜电流异常";
                }
                if ($val['WEIDU'] > 80) {
                    $val['content'] = (isset($ssids['map'][$decodeSsid]) ? "编号".$ssids['map'][$decodeSsid]. "-": '') .$val['DEVONE'] . "柜温度异常";
                }
                $data[] = $val;
            }
        }
        return $this->toSuccess($data);
    }

    /**
     * 时间排行
     */
    public function TimeAction()
    {
        $type = $this->request->get('type');
        $area_id = $this->request->get('area_id');
        if ($type == 1) {
            $result = ChargeHourlySummary::find([
                "columns" => "SUM(charge_num) as num, hour",
                "group" => "hour"
            ]);
        } else {
            $group = "hour";
            if ($type == 2) {
                $conditions = "province_id = :province_id:";
                $parameters = ['province_id' => $area_id];
                $columns = "SUM(charge_num) as num, hour";
            } else {
                $conditions = "city_id = :city_id:";
                $parameters = ['city_id' => $area_id];
                $columns = "SUM(charge_num) as num, hour";
                $group = "hour";
            }
            $result = ChargeHourlySummary::find([
                $conditions,
                "bind" => $parameters,
                "columns" => $columns,
                "group" => $group
            ]);
        }

        $data = [];
        foreach ($result as $key => $val) {
            if ($val->hour%2 == 0) {
                $key = $val->hour."-".($val->hour+2);
            } else {
                $key = ($val->hour-1). "-".($val->hour+1);
            }
            if (isset($data[$key])) {
                $data[$key] =  $data[$key] + $val->num;
            } else {
                $data[$key] = $val->num;
            }
        }
        arsort($data);
        $res['first'] = $data ? ['name' =>  key($data), 'value' =>array_shift($data)] : ['name' =>  '', 'value' =>0];
        $res['second'] = $data ? ['name' =>  key($data), 'value' =>array_shift($data)] : ['name' =>  '', 'value' =>0];
        $res['third'] = $data ? ['name' =>  key($data), 'value' =>array_shift($data)] : ['name' =>  '', 'value' =>0];

        return $this->toSuccess($res);
    }

    /**
     * 地区排行
     */
    public function BarAction()
    {
        $type = $this->request->get('type');
        $area_id = $this->request->get('area_id');
        if ($type == 1) {
            $result = ChargeHourlySummary::find([
                "columns" => "SUM(charge_num) as num, province_id as area_id",
                "group" => "province_id"
            ]);
        } else {
            if ($type == 2) {
                $conditions = "province_id = :province_id:";
                $parameters = ['province_id' => $area_id];
                $columns = "SUM(charge_num) as num, city_id as area_id";
                $group = "city_id";
            } else {
                $conditions = "city_id = :city_id:";
                $parameters = ['city_id' => $area_id];
                $columns = "SUM(charge_num) as num, area_id";
                $group = "area_id";
            }
            $result = ChargeHourlySummary::find([
                $conditions,
                "bind" => $parameters,
                "columns" => $columns,
                "group" => $group
            ]);
        }
        $area = [];
        $data = [];
        foreach ($result as $key => $val) {
            $model = Area::findFirst($val->area_id);
            if ($model) {
                $area[$val->area_id] = $model->area_name;
            } else {
                $area[$val->area_id] ='--';
            }
            $val->area_name = $area[$val->area_id];
            $data[] = $val;
        }
        if ($data && count($data) > 10) {
            $data = array_slice($data, 0, 10);
        }
        return $this->toSuccess($data);
    }

    /**
     * 地图的数据
     */
    public function MapAction()
    {
        $type = $this->request->get('type');
        $area_id = $this->request->get('area_id');

        if ($type == 1) {
            $result = ChargeDevice::find([
                "columns" => "SUM(total_num) as num, province_id as area_id",
                "group" => "province_id"
            ]);
        } else {
            if ($type == 2) {
                $conditions = "province_id = :province_id:";
                $parameters = ['province_id' => $area_id];
                $columns = "SUM(total_num) as num, city_id as area_id";
                $group = "city_id";
            } else {
                $conditions = "city_id = :city_id:";
                $parameters = ['city_id' => $area_id];
                $columns = "SUM(total_num) as num, area_id";
                $group = "area_id";
            }
            $result = ChargeDevice::find([
                $conditions,
                "bind" => $parameters,
                "columns" => $columns,
                "group" => $group
            ]);
        }
        $area = [];
        $data = [];
        foreach ($result as $key => $val) {
            $model = Area::findFirst($val->area_id);
            if ($model) {
                $area[$val->area_id] = $model->area_name;
            } else {
                $area[$val->area_id] ='--';
            }
            $val->area_name = $area[$val->area_id];
            $data[] = $val;
        }
        return $this->toSuccess($data);
    }

    /**
     * 获取当天充电总次数
     */
    public function totalAction()
    {
        $type = $this->request->get('type');
        $area_id = $this->request->get('area_id');
        if ($type == 1) {
            $conditions = "day = :day:";
            $parameters = ['day' => date('Y-m-d')];
            $result = ChargeHourlySummary::find([
                $conditions,
                "bind" => $parameters,
                "columns" => "SUM(charge_num) as num",
            ]);
        } else {
            if ($type == 2) {
                $conditions = "day = :day: and province_id = :province_id:";
                $parameters = ['day' => date('Y-m-d'), 'province_id' => $area_id];
            } else {
                $conditions = "day = :day: and city_id = :city_id:";
                $parameters = ['day' => date('Y-m-d'), 'city_id' => $area_id];
            }
            $result = ChargeHourlySummary::find([
                $conditions,
                "bind" => $parameters,
                "columns" => "SUM(charge_num) as num",
            ]);
        }
        return $this->toSuccess($result);
    }

    /**
     * 获取当前正在使用的总的充电设备个数、总数及功率
     */
    public function currentAction()
    {
        $type = $this->request->get('type');
        $area_id = $this->request->get('area_id');
        //获取正在充电的柜子
        $conditions = "GUZHANG = :GUZHANG:";
        $parameters = ['GUZHANG' => 1];
        $result = BoxSocketNewchongdian::find([
            $conditions,
            "bind" => $parameters,
        ]);
        //获取这个区域的柜子总数
        $data = (new ChargeDevice())->getAreaData($type, $area_id);
        $current_charging = 0;
        $power = 0;
        if ($data) {
            foreach ($result as $key => $val) {
                $decodeSsid = ChargeDevice::decodeSsid($val->SSID);
                if (in_array($decodeSsid, $data['ssids'])) {
                    $current_charging++;
                    $power += $val->DIANYA * $val->DIANLIU;
                }
            }
        }
        return $this->toSuccess(['total' => $data['total'], 'current_charging' => $current_charging, 'power' => $power]);
    }
}
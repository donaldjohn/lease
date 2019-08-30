<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/13
 * Time: 14:02
 */
use Phalcon\Cli\Task;
use app\models\service\RegionVehicle;
use app\models\dispatch\Drivers;
use app\models\dispatch\RegionDrivers;
use app\models\dispatch\Region;
use app\models\service\Vehicle;

class TempvehicleTask extends Task
{
    /**
     * 同步骑手车辆与站点的关系
     */
    public function SyncAction()
    {
        $res = $this->getDi()->getShared('dw_users')->query("
            SELECT * from dewin_users.temp_vehicle_relation where status = 0;"
        );
        $res->setFetchMode(Phalcon\Db::FETCH_OBJ);
        $i = 1;
        while ($robot = $res->fetch()) {
            //获取driver_id
            $driver_id = 0;
            if ($robot->driver_id > 0) {
                $driver_id = $this->getDriver($robot->phone);
            }
            $conditions = 'bianhao = :bianhao: AND use_attribute = :use_attribute:' ;
            $parameters = [
                'bianhao' => $robot->bianhao,
                'use_attribute' => 4,
            ];
            $result = Vehicle::findFirst([$conditions,'bind' => $parameters]);
            if (!$result) {
                continue;
            }
            $region = $this->getRegion($robot->store_name);
            $driver_relation = [
                "region_id" => $region['region_id'],
                "ins_id" => $region['ins_id'],
                "driver_id" => $driver_id,
                "create_time" => time(),
                "update_time" => time(),
            ];
            $vehicle_relation = [
                "region_id" => $region['region_id'],
                "ins_id" => $region['ins_id'],
                "driver_id" => $driver_id,
                "bind_status" => $driver_id > 0 ? 2 : 1,
                "vehicle_id" => $result->id,
                "update_time" => time()
            ];
            $this->addVehicleRelation($vehicle_relation);
            if ($driver_id > 0) {
                $this->addDriverRelation($driver_relation);
                $result->save(['driver_bind' => 2, 'driver_id' => $driver_id, 'has_bind' => 2] );
            }
            $this->getDi()->getShared('dw_users')->query("update temp_vehicle_relation set status = 1 WHERE  id = {$robot->id}");
            echo $i++;
        }
    }

    /**
     * 插入车辆关系
     */
    private function addVehicleRelation($data)
    {
        $model = new RegionVehicle();
        $model->save($data);
    }

    /**
     * @param $data
     */
   private function addDriverRelation($data)
   {
       $model = new RegionDrivers();
       $model->save($data);
   }

    /**
     * 获取站点信息
     * @param $region_name
     * @return array
     */
    private function getRegion($region_name)
    {
        $conditions = 'region_name = :region_name:';
        $parameters = [
            'region_name' => $region_name,
        ];
        $result = Region::findFirst([$conditions,'bind' => $parameters]);
        return ['ins_id' => $result ? $result->ins_id : 0, 'region_id' => $result ? $result->id : 0];
    }

    /**
     * @param $phone
     * @return int|\Phalcon\Mvc\Model\Resultset|\Phalcon\Mvc\Phalcon\Mvc\Model
     */
    private function getDriver($phone)
    {
        $conditions = 'phone = :phone:';
        $parameters = [
            'phone' => $phone,
        ];
        $res = Drivers::findFirst([$conditions,'bind' => $parameters]);
        if (!$res) {
            return  0;
        }
        return $res->id;
    }
}
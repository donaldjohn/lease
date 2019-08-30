<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/28
 * Time: 14:07
 */
use Phalcon\Cli\Task;
use app\models\VehicleRail;
use Phalcon\Logger\Adapter\File as FileAdapter;

class MainTask extends Task
{
    public function MainAction()
    {
        $this->dispatcher->forward(
            [
                "task"   => "main",
                "action" => "test",
                "params" => array("test","12345"),
            ]
        );
    }
    public function CalAction()
    {

        //获取苏州电子围栏信息
        $result = $this->db->query("SELECT laglnt,id FROM `dw_rail` where status = 1 limit 1 ");
        $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
        while ($robot = $result->fetch()) {
            $laglnt =  $robot->laglnt;
            $rail_id = $robot->id;
        }
        if ($laglnt) {
            $laglnt = unserialize($laglnt);
        } else {
            return;
        }
        //获取车辆信息
        $vehicle = $this->db->query("SELECT count(*) as count FROM `dw_vehicle`");
        $vehicle->setFetchMode(Phalcon\Db::FETCH_OBJ);
        while ($model = $vehicle->fetch()) {
            $count =  $model->count;
        }
        if (!$count) {
            return;
        }
        $limit = 2;
        for ($i = 0 ; $i < 1; $i++) {
            $offset = $i * $limit;
            $model_vehicle = $this->db->query("SELECT id, lng, lat, update_time FROM `dw_vehicle` limit {$offset},{$limit}");
            $model_vehicle->setFetchMode(Phalcon\Db::FETCH_OBJ);
            while ($res = $model_vehicle->fetch()) {
               $flag = $this->OutAction(["lng" => $model_vehicle ->lng, "lat" => $model_vehicle->lat], $laglnt);
               $logger = new FileAdapter(BASE_PATH . '/logs/tasks-' . date('Ymd') . '.log');
               $logger->info(json_encode($laglnt));
               if ($flag) {
                   $this->db->execute(
                       "INSERT INTO `dw_rail_vehicle`(`rail_id`, `vehicle_id`, `in_time`, `update_time`,`status`) VALUES (?, ?, ?, ?, ?)",
                       array(15, $res->id, $res->update_time, time(), 1)
                   );
               } else {
                   $this->db->execute(
                       "INSERT INTO `dw_rail_vehicle`(`rail_id`, `vehicle_id`, `out_time`, `update_time`,`status`) VALUES (?, ?, ?, ?, ?)",
                       array(15, $res->id, $res->update_time, $res->update_time, 2)
                   );
               }
            }
        }
    }
    /**
     * @param $p
     * @param $poly
     * @return bool
     */
    private function OutAction($p, $poly)
    {
        $px = $p['lat'];
        $py = $p['lng'];
        $flag = false;
        $len = count($poly);
        $j = $len -1;
        $logger = new FileAdapter(BASE_PATH . '/logs/tt-' . date('Ymd') . '.log');
        for ($i = 0 ; $i < $len ; $i++) {
            $sy = $poly[$i]['lng'];
            $sx = $poly[$i]['lat'];
            $ty = $poly[$j]['lng'];
            $tx = $poly[$j]['lat'];
            // 点与多边形顶点重合
            if ($px == $sx && $py == $sy || $px == $tx && $py == $ty)
                return TRUE;

            // 判断线段两端点是否在射线两侧
            if ($sy < $py && $ty >= $py || $sy >= $py && $ty < $py) {
                $x = $sx + ($py - $sy) * ($tx - $sx) / ($ty - $sy);
                if ($x == $px) {
                    return TRUE;
                }

                if ($x > $px) {
                    print_r($poly[$i]);$poly[$j];
                    $flag = !$flag;
                }
            }
            $j = $i;
        }
        var_dump("000");var_dump($flag);exit;
        return $flag;
    }
    /**
     * @param array $params
     */
    public function TestAction(array $params)
    {
        echo sprintf(
            "hello %s",
            $params[0]
        );

        echo PHP_EOL;

        echo sprintf(
            "best regards, %s",
            $params[1]
        );

        echo PHP_EOL;
    }
}
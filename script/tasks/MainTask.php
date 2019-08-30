<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/28
 * Time: 14:07
 */
use Phalcon\Cli\Task;
use Phalcon\Logger\Adapter\File as FileAdapter;
use app\models\Vehicle;
use app\models\RailVehicle;
use app\models\Rail;

class MainTask extends Task
{
    /**
     * test
     */
    public function MainAction()
    {
        $this->dispatcher->forward(
            [
                "task"   => "main",
                "action" => "test",
                "params" => array("test","12345"),
            ],
            [
                "task"   => "vehicle",
                "action" => "lock",
            ],
            [
                "task"   => "charge",
                "action" => "summary",
            ],
            [
                "task"   => "tempcompany",
                "action" => "sync",
            ],
            [
                "task"   => "tempsite",
                "action" => "sync",
            ],
            [
                "task"   => "tempvehicle",
                "action" => "sync",
            ]
        );
    }

    /**
     * 计算车辆是否在电子围栏中
     */
    public function CalAction()
    {
        echo "开始".time();
        //获取车辆总数
        $count = Vehicle::count();
        if (!$count) {
            return;
        }
        $conditions = 'status = :status:';
        $parameters = [
            'status' => 1,
        ];
        //获取所有电子围栏信息
        $result = Rail::find(["columns"=> "id,latlng",  $conditions, 'bind' => $parameters]);
        foreach ($result as $value) {
//            echo $value->id, "\n";
            $latlng = unserialize($value->latlng);
            if ($latlng) {
                $this->SingleCheck($value->id, $latlng, $count);
            }
        }
        echo "结束".time();
    }
    //单个电子围栏车辆的驶入驶出情况
    private function SingleCheck($id, $latlng, $count)
    {
        $limit = 30;
        for ($i = 0 ; $i < $count/$limit ; $i++) {
            $offset = $i * $limit;
            $vehicle = Vehicle::find(["columns" => "id,lat,lng,update_time", "order" => "id", "limit" => ["number" => $limit, "offset" => $offset]]);
            foreach ($vehicle as $value) {
                //判断车辆是在围栏内部还是外部
                $flag = $this->OutAction(["lng" => $value->lng,"lat" => $value->lat], $latlng);
                //查询是否存在
                $conditions = 'rail_id = :rail_id: and vehicle_id = :vehicle_id: and status = :status:';
                $parameters = [
                    'rail_id' => $id,
                    'vehicle_id' => $value->id,
                    'status' => 1,
                ];
                $in = RailVehicle::findFirst([$conditions, 'bind' => $parameters]);
                if ($flag) {
                    //不存在驶入记录是插入数据
                    if (!$in) {
                        $robot = new RailVehicle();
                        $robot->save(["rail_id" => $id, "vehicle_id" => $value->id, "in_time" => $value->update_time,"update_time" => time(), "status" => 1,]);
                    }
                } else {
                    //存在驶入记录时，更新数据
                    if ($in) {
                        $in->save(["out_time" => $value->update_time, "update_time" => time(), "status" => 2,]);
                    }
                }
            }
        }
    }
    /**
     * 判断是否在多边形内部
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
//        $logger = new FileAdapter(BASE_PATH . '/logs/tt-' . date('Ymd') . '.log');
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
//                    print_r($poly[$i]);$poly[$j];
                    $flag = !$flag;
                }
            }
            $j = $i;
        }
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
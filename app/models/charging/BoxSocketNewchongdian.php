<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 13:31
 */
namespace app\models\charging;

class BoxSocketNewchongdian extends BaseModel
{
    /**
     * 换电柜
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("box_socket_newchongdian");
    }

    /**
     * 获取柜子有多少个设备在使用
     * @param $ssid
     */
    public function getCurrentNum($ssid)
    {
        $conditions = "SSID = :SSID: AND GUZHANG = :GUZHANG:";
        $parameters = ['SSID' => $ssid, 'GUZHANG' => 1];
        $result = BoxSocketNewchongdian::find([
            $conditions,
            'bind' => $parameters,
            "columns" => "count(ID) as num",
        ])->toArray();
        return $result ? $result[0]['num'] : 0;
    }

    public function getBox($ssid)
    {
        $conditions = "SSID = :SSID:";
        $parameters = ['SSID' => $ssid];
        $result = BoxSocketNewchongdian::find([
            $conditions,
            'bind' => $parameters,
            "columns" => "GUZHANG, CREATETIME",
        ])->toArray();
        $time = 0;
        $num = 0;
        if ($result) {
            foreach ($result as $key => $val) {
                if ($val['GUZHANG'] == 1) {
                    $num++;
                }
                if ($val['CREATETIME'] > $time ) {
                    $time = $val['CREATETIME'];
                }
            }
        }
        return ['time'=> $time, 'num' => $num];
    }
}
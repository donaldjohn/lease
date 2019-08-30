<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models;

use Phalcon\Mvc\Model;

class Vehicle extends Model
{
    /**
     * 每日报表信息
     */
    public function initialize()
    {
        $this->setConnectionService('dw_service');
        $this->setSource("dw_vehicle");
    }

    public function beforeCreate()
    {
        $this->create_time = time();
        $this->update_time = 0;
    }

    /**
     * 临时封装关联数组查询，未完善
     * @param $arr 关联数组，支持类TP的[k=>[>,6], k=>[not null]]
     * @param string $relation
     * @param array $assist
     * @return Model\ResultsetInterface
     */
    public function arrFind($arr, $relation='and', $assist=[])
    {
        // 预处理conditions
        $conditions = [];
        foreach ($arr as $k => $v){
            if (is_array($v)){
                $conditions[] = $k.' '.implode(' ', $v);
                unset($arr[$k]);
            }else{
                $conditions[] = $k.' = :'.$k.':';
            }
        }
        $conditions = implode(" {$relation} ", $conditions);
        // 查询返回
        return self::find(array_merge([
            'conditions' => $conditions,
            'bind' => $arr,
        ], $assist));
    }
}
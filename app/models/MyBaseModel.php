<?php
namespace app\models;

use Phalcon\Mvc\Model;

class MyBaseModel extends Model
{

    // 关联数组查询
    static public function arrFind($arr, $relation='and', $assist=[])
    {
        $parameters = self::dealWithWhereArr($arr, $relation, $assist);
        // 查询返回
        return self::find($parameters);
    }

    // 关联数组查询
    static public function arrFindFirst($arr, $relation='and', $assist=[])
    {
        $parameters = self::dealWithWhereArr($arr, $relation, $assist);
        // 查询返回
        return self::findFirst($parameters);
    }

    // 分页查询
    static public function arrFindPage($pageSize, $pageNum, $arr, $relation='and', $assist=[])
    {
        $count = self::count(self::dealWithWhereArr($arr, $relation, $assist));
        $offset = $pageSize*($pageNum-1);
        $assist['limit'] = [(int)$pageSize, (int)$offset];
        $list = self::arrFind($arr, $relation, $assist);
        if ($list){
            $list = $list->toArray();
        }
        $meta = [
            'total' => $count,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        ];
        self::keyToHump($list);
        return [
            'list' => $list,
            'meta' => $meta
        ];
    }

    /**
     * 组装关联数组查询
     * @param $arr 关联数组，支持类TP的[k=>['>',6], k=>['not null'], k=>['IN', Arr]]
     * @param string $relation
     * @param array $assist
     * @return array
     */
    static public function dealWithWhereArr($arr, $relation='and', $assist=[])
    {
        if (isset($arr['bind']) && is_array($arr['bind'])){
            return $arr;
        }
        if (is_array($relation)){
            $assist = array_merge($relation, $assist);
            $relation = 'and';
        }
        // 预处理conditions
        $conditions = [];
        foreach ($arr as $k => $v){
            if (!is_array($v)){
                $conditions[] = $k.' = :'.$k.':';
                continue;
            }
            if (isset($v[1]) && is_array($v[1])){
                $conditions[] = "{$k} {$v[0]} ({{$k}:array})";
                $arr[$k] = array_values($v[1]);
                continue;
            }
            $conditions[] = $k.' '.implode(' ', $v);
            unset($arr[$k]);
        }
        $conditions = implode(" {$relation} ", $conditions);
        // 查询返回
        return array_merge([
            'conditions' => $conditions,
            'bind' => $arr,
        ], $assist);
    }

    // 捕捉验证器失败结果到全局变量
    protected function validate($validator){
        $bol = parent::validate($validator);
        if (!$bol){
            $Messages = $this->getMessages();
            foreach ($Messages as $msg){
                $GLOBALS['ExceptionTipsMsg'][] = $msg->getMessage();
            }
        }
        return $bol;
    }

    static public function keyToHump(&$list){
        $newList = [];
        foreach ($list as $k => $item){
            if (is_numeric($k)){
                $newList[] = self::keyToHump($item);
                continue;
            }
            $humpKey = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
                return strtoupper($matches[2]);
            },$k);
            $newList[$humpKey] = $item;
        }
        $list = $newList;
        return $newList;
    }
}
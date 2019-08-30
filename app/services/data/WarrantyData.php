<?php
namespace app\services\data;


use app\common\errors\DataException;

class WarrantyData extends BaseData
{
    public  function res_tree($tree,$pid){
        $result = array();                                //每次都声明一个新数组用来放子元素
        foreach($tree as $v){
            if($v['areaParentId'] == $pid){//匹配子记录
                $item = [];
                $item['title'] = $v['areaName'];
                $item['value'] = $v['areaId'];
                $item['label'] = $v['areaName'];
                $item['areaName'] = $v['areaName'];
                $item['name'] = $v['areaName'];
                $item['areaDeep'] = $v['areaDeep'];
                $item['areaId'] = $v['areaId'];
                //$item['expand'] = true;
                $item['children'] = self::res_tree($tree,$item['areaId']); //递归获取子记录
                $result[] = $item;
//                    if($v['children'] == null){
//                        unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
//                    }

            }
        }
        return $result;  //返回新数组
    }

    public function getRegion($id)
    {
        $params = ["code" => "10022","parameter" => ['areaId' => $id]];
        $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        $result = $result['content']['data'][0];
        return $result;
    }



    public function res_choose_tree($res)
    {
        $i = 0;
        $parents = [];
        foreach($res as $item) {
            if(isset($item['areaParentId']) && $item['areaParentId'] == 0) {
                $i++;
                continue;
            }
            $parent_result = $this->getRegion($item['areaParentId']);
            if (isset($parent_result['areaId']) && $parent_result['areaId'] != 0) {
                if (isset($parents[$parent_result['areaParentId']])) {
                    $parents[$parent_result['areaParentId']]['children'][] = $item;
                } else {
                    $list = [];
                    $list['title'] = $parent_result['areaName'];
                    $list['name'] = $parent_result['areaName'];
                    $list['areaParentId'] = $parent_result['areaParentId'];
                    $list['areaDeep'] = $parent_result['areaDeep'];
                    $list['areaId'] = $parent_result['areaId'];
                    $list['children'][] = $item;
                    $parents[$list['areaParentId']] = $list;
                }
            }

        }
        if (count($res) == $i) {
            return $res;
        }
        return self::res_choose_tree($parents);


    }


    /**
     * 获取已选区域树
     */
    public function getTreeUse($ids)
    {
        // 获取全部区域
        $result = $this->curl->httpRequest($this->Zuul->biz,["code" => "10022"],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        if (!isset($result['content']['data']))
            return $this->toError(500, "数据不存在");
        $data = $result['content']['data'];
        unset($result);
        // 处理数据结构，方便递归
        $tmpParent = [];
        $tmpSelf = [];
        foreach ($data as $val){
            // 强制字符串关联数组
            // 父子级关系
            $tmpParent[(string)$val['areaParentId']][] = [
                'areaId' => $val['areaId']
            ];
            $tmpSelf[(string)$val['areaId']] = [
                'title' => $val['areaName'],
                'name' => $val['areaName'],
                'expand' => true,
                'areaName' => $val['areaName'],
                'areaParentId' => $val['areaParentId'],
                'areaDeep' => $val['areaDeep'],
                'areaId' => $val['areaId'],
            ];
        }
        unset($data);
        // 初始化数据
        $data = [];
        //fix

        foreach ($ids as $id){
            $data[] = ['areaId' => $id];
        }
        // 获取子级数据
        $this->ChildTreeRecursive($tmpParent, $tmpSelf, $data);
        // 父级树
        $this->ParentTreeRecursive($tmpSelf, $data);
        return $data;
    }

    /**
     * 处理子级树
     */
    public function ChildTreeRecursive(&$tmpParent, &$tmpSelf, &$data){
        // 递归处理区域树
        foreach ($data as $k => $v){
            if (isset($tmpSelf[(string)$v['areaId']])) {
                $data[$k] = $tmpSelf[(string)$v['areaId']];
                // unset($tmpSelf[(string)$v['areaId']]);
                if (isset($tmpParent[(string)$v['areaId']])){
                    // 如有子级区域，加入
                    $data[$k]['children'] = $tmpParent[(string)$v['areaId']];
                    $this->ChildTreeRecursive($tmpParent, $tmpSelf, $data[$k]['children']);
                }
            } else {
                throw new DataException([500,'非法区域数据,请检查数据!']);
            }
        }
    }
    /**
     * 处理父级树
     */
    public function ParentTreeRecursive(&$tmpSelf, &$data){
        // 处理数据便于父级递归
        $dataParent = [];
        $tmpData = [];
        foreach ($data as $k => $val){
            $dataParent[(string)$val['areaParentId']][] = $val['areaId'];
            $tmpData[(string)$val['areaParentId']][] = $val;
            unset($data[$k]);
        }
        // 结束状态
        $over = true;
        // 递归处理区域树
        foreach ($tmpData as $k => $v){
            // 顶级 过
            if (0==$k) continue;
            $over = false;
            // 父级写入
            $data[$k] = $tmpSelf[(string)$k];
            // 子级写入
            $data[$k]['children'] = $v;
        }
        // 结束判定
        if (!$over) {
            unset($tmpData);
            // 获取父级数据
            return $this->ParentTreeRecursive($tmpSelf, $data);
        }else{
            // 地址赋值
            $data = $tmpData['0'];
            return;
        }
    }


    public function is_timestamp($timestamp) {
        if(strtotime(date('Y-m-d H:i:s',$timestamp)) == $timestamp) {
            return $timestamp;
        } else return false;
    }

}



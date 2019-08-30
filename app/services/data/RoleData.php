<?php
/**
 * Created by PhpStorm.
 * User: zhengchao
 * Date: 2018/5/28
 * Time: 下午9:31
 */
namespace app\services\data;
use app\common\errors\DataException;


class RoleData extends BaseData {



    public function getRoleById($id)
    {
        $res = ["id" => $id];
        $json = ['code' => 10007,'parameter' =>$res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if (count($result['content']['roles']) != 1){
                return false;
            }
            if(!isset($result['content']['roles'][0]))
                return false;
            $result = $result['content']['roles'][0];
            return $result;
        } else {
            throw new DataException([$result['statusCode'],$result['msg']]);
        }
    }


    public function checkRoleSystems($systemId,$roleId)
    {
        $params = [
            'code' => '10065',
            'parameter' => [
                'roleId' => $roleId,
                'systemId' => $systemId
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['roleSystemDOS']))
            throw new DataException([500, "数据不存在"]);
        if (isset($result['content']['roleSystemDOS'][0]))
            return true;
        return false;


    }
}
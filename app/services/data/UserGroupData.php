<?php
namespace app\services\data;
use app\common\errors\DataException;




class UserGroupData extends BaseData
{
    public function getUserGroupById($id)
    {
        $params = [
            'code' => '10014',
            'parameter' => [
                'id' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['groupDOS'][0])) {
            return false;
        }
        return $result['content']['groupDOS'][0];
    }


    public function checkUserSystems($groupId,$systemId)
    {
        $params = [
            'code' => '10060',
            'parameter' => [
                'groupId' => $groupId,
                'systemId' => $systemId
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['groupSystemDOS']))
            throw new DataException([500, "数据不存在"]);

        if (isset($result['content']['groupSystemDOS'][0]))
            return true;
        return false;
    }
}



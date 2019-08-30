<?php
namespace app\services\data;
use app\common\errors\DataException;




class SiteData extends BaseData
{

    public function getSiteIdByUserId($UserId)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60029,
            'parameter' => [
                'userId' => $UserId,
            ]
        ],"post");
        if ($result['statusCode'] != '200' || !isset($result['content']['data'][0])) {
            throw new DataException([500, '未获取到站点关系']);
        }
        return $result['content']['data'][0]['siteId'];
    }

    public function getAdminUserIdBySiteId($SiteId)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60029,
            'parameter' => [
                'siteId' => $SiteId,
                'isLeader' => 2,
            ]
        ],"post");
        if ($result['statusCode'] != '200' || !isset($result['content']['data'][0])) {
            throw new DataException([500, '未获取到用户关系']);
        }
        return $result['content']['data'][0]['userId'];
    }

    public function getUserListBySiteId($SiteId)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60029,
            'parameter' => [
                'siteId' => $SiteId
            ]
        ],"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([500, '未获取到用户关系']);
        }
        $userIds = [];
        foreach ($result['content']['data'] as $v){
            $userIds[] = $v['userId'];
        }
        $userList = $this->userData->getUserByIds($userIds);
        foreach ($result['content']['data'] as $v){
            // 过掉不存在
            if (!isset($userList[$v['userId']])) continue;
            // 加入管理状态
            $userList[$v['userId']]['isLeader'] = $v['isLeader'];
        }
        return $userList;
    }

    // 获取站点信息通过SiteId
    public function getSiteBySiteId($SiteId)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60024,
            'parameter' => [
                'id' => $SiteId
            ]
        ],"post");
        // 异常
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            throw new DataException([500,'站点服务异常']);
        }
        // 数据不存在
        if (!isset($result['content']['siteDOS'][0])){
            throw new DataException([500,'未获取到站点信息'.$SiteId]);
        }
        // 返回
        return $result['content']['siteDOS'][0];
    }

}

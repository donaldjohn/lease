<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\dispatch\RegionDrivers;
use app\models\dispatch\RegionUser;
use app\models\service\RegionVehicle;
use app\services\data\UserData;
use app\models\dispatch\Region;

class RegionData extends BaseData
{
    const RegionType = 1;
    const SiteType = 2;
    // 获取单条区域信息 通过id
    public function getRegionById($id)
    {
        $regions = $this->getRegionByIds([$id]);
        if (!isset($regions[0]))
            throw new DataException([500, "数据不存在"]);
        return $regions[0];
    }

    /**获取多条区域信息 通过idlist
     * @param $ids 区域idlist
     * @param bool $convert 是否转换为id关系数组
     * @return array 区域列表
     * @throws DataException 获取数据有误
     */
    public function getRegionByIds($ids, $convert = false)
    {
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0])));
        if (0==count($ids)){
            return [];
        }
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60006,
            'parameter' => [
                'idList' => $ids,
            ],
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([500, $result['msg']]);
        }
        $RegionList = $result['content']['regionDOS'];
        if ($convert){
            $tmpList = [];
            foreach ($RegionList as $Region){
                $tmpList[$Region['id']] = $Region;
            }
            $RegionList = $tmpList;
        }
        return $RegionList;
    }

    /**获取区域负责人信息 通过区域idlist
     * @param $ids 区域idlist
     * @return array 区域负责人信息
     * @throws DataException 匹配不对等
     */
    public function getRegionLeaderByIds($ids)
    {
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0])));
        if (0==count($ids)){
            return [];
        }
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60009',
            'parameter' => [
                'regionIdList' => $ids,
                'isLeader' => 2,
            ]
        ],"post");
        //失败返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], '获取区域人员关系：'.$result['msg']]);
        }
        $RegionLeaderList = [];
        $userIds = [];
        foreach ($result['content']['data'] as $val){
            $RegionLeaderList[$val['regionId']] = [
                'userId' => $val['userId'],
                'isLeader' => $val['isLeader'],
            ];
            $userIds[] = $val['userId'];
        }
        $userlist = (new UserData())->getUserByIds($userIds);
        if (count($RegionLeaderList) != count($userlist)){
//            throw new DataException([500, '区域人员关系不匹配']);
        }
        foreach ($RegionLeaderList as $k => $v){
            $RegionLeaderList[$k] = $userlist[$v['userId']];
            $RegionLeaderList[$k]['isLeader'] = $v['isLeader'];
        }
        return $RegionLeaderList;
    }

    /**
     * 获取用户关联的区域id
     * @param $UserId 用户id
     * @return null
     */
    public function getRegionIdByUserId($UserId)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60009,
            'parameter' => [
                'userId' => $UserId,
            ]
        ],"post");
        if ($result['statusCode'] != '200' || !isset($result['content']['data'][0])) {
            return null;
        }
        return $result['content']['data'][0]['regionId'];
    }

    // 获取区域用户数据
    public function getRegionUserList($ruArr)
    {
        /* $ruArr标准传参格式
         * [
                'regionIdList' => [1,2],
                'isLeader' => 2,
           ]
         */
        // 兼容区域id查询
        if (!is_array($ruArr)){
            $ruArr = ['regionIdList' => [$ruArr]];
        }
        // 兼容区域idlist查询
        if (!isset($ruArr['regionIdList'])){
            $ruArr = ['regionIdList' => $ruArr];
        }
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60009',
            'parameter' => $ruArr,
        ],"post");
        //失败返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], '获取区域人员关系：'.$result['msg']]);
        }
        // 用户id列表
        $userIds = [];
        // 区域用户关系
        $RegionUserList = [];
        // 用户是否为区域管理者关系
        $uils = [];
        $RUids = [];
        foreach ($result['content']['data'] as $val){
            if (0 == $val['userId']) continue;
            $userIds[] = $val['userId'];
            $RegionUserList[$val['regionId']][] = $val['userId'];
            $uils[$val['regionId'].','.$val['userId']] = $val['isLeader'];
            $RUids[$val['regionId'].','.$val['userId']] = $val['id'];
        }
        // 获取用户信息
        $userlist = (new UserData())->getUserByIds(array_values(array_unique($userIds)));
        // 取出用户信息到区域
        foreach ($RegionUserList as $regionId => $us){
            $RegionUserList[$regionId] = [];
            foreach ($us as $uid){
                // 记录当前用户是否为区域管理者
                $userlist[$uid]['isLeader'] = $uils[$regionId.','.$uid];
                $userlist[$uid]['RUid'] = $RUids[$regionId.','.$uid];
                $RegionUserList[$regionId][] = $userlist[$uid];
            }
        }
        return $RegionUserList;
    }

    // 是否有区域编码
    public function hasRegionCode($RegionCode, $insId, $type, $excludeId=null)
    {
        $where['region_code'] = $RegionCode;
        $where['ins_id'] = $insId;
        $where['region_type'] = $type;
        $where['is_delete'] = 1;
        if (!is_null($excludeId)){
            $where['id'] = [ '!=', $excludeId];
        }
        $region = Region::arrFindFirst($where);
        if ($region){
            return true;
        }
        return false;
    }

    // 是否有区域名称
    public function hasRegionName($regionName, $insId, $type, $excludeId=null)
    {
        $where['region_name'] = $regionName;
        $where['ins_id'] = $insId;
        $where['region_type'] = $type;
        $where['is_delete'] = 1;
        if (!is_null($excludeId)){
            $where['id'] = [ '!=', $excludeId];
        }
        $region = Region::arrFindFirst($where);
        if ($region){
            return true;
        }
        return false;
    }
    // 向上获取直系区域
    public function getDirectRegionById($region)
    {
        if (!is_array($region)){
            $region = $this->getRegionById($region);
        }
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60005',
            'parameter' => [
                'id' => $region['id'],
                'insId' => $region['insId'],
                'regionLevel' => $region['regionLevel'],
                'parentId' => $region['parentId'],
            ]
        ],"post");
        if (200!=$result['statusCode']){
            return [];
        }
        $result['content']['parentRegions'][] = $region;
        $list = [];
        foreach ($result['content']['parentRegions'] as $parentRegion){
            $regionIds[] = $parentRegion['id'];
            $list[$parentRegion['id']] = $parentRegion;
        }
        return $list;
    }

    // 删除区域用户关系
    public function delRegionUser($ruArr)
    {
        /* 条件传参示例
        [
            'regionId' => 6,
            'userId' => 6,
        ]*/

        // 删除区域用户关系
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60008',
            'parameter' => $ruArr,
        ],"post");
        if (200 != $result['statusCode']){
            return false;
        }
        return true;
    }

    // 删除区域用户关系通过区域id
    public function delRegionUserByRegionId($region)
    {
        return $this->delRegionUser(['userId' => $region]);
    }

    // 邮管1.5，查询区域及下属区域id集合
    public function getBelongRegionIdsByRegionId($regionId, $insId, $onlySite=false)
    {
        $data['ins_id'] = $insId;
        $regions = (new Region())->arrFind($data)->toArray();
        // 预处理数据方便递归
        $tmpParent = [];
        $tmpself = [];
        foreach ($regions as $region){
            $tmpParent[$region['parent_id']][] = $region['id'];
            $tmpself[$region['id']] = $region;
        }
        unset($regions);
        // 如果当前区域是站点类型，缓存其归属区域
        if (isset($tmpself[$regionId]) && 2==$tmpself[$regionId]['region_type']){
            $siteParentRegionId = $tmpself[$regionId]['parent_id'];
        }
        $regionIds = [$regionId];
        if (isset($tmpParent[$regionId])){
            $regionIds = array_values($this->getChildRegionIds($regionId, $tmpParent));
        }
        // 不只要站点
        if (false===$onlySite){
            if (isset($siteParentRegionId)){
                $regionIds[] = $siteParentRegionId;
            }
            return $regionIds;
        }
        // 筛出站点
        $siteIds = [];
        foreach ($regionIds as $v){
            if (2 == $tmpself[$v]['region_type']){
                $siteIds[] = $v;
            }
        }
        return $siteIds;
    }

    /**
     * 私有方法 递归子级区域id包括自己
     * @param $regionId 区域ID
     * @param $tmpParent 处理好的父子级
     * @param bool $init 初始化
     * @return array
     */
    private function getChildRegionIds($regionId, &$tmpParent, $init=true)
    {
        // 无子级 过
        if (!isset($tmpParent[$regionId])) {
            return $init ? [$regionId] : [];
        };
        $Childs = array_values($tmpParent[$regionId]);
        foreach ($tmpParent[$regionId] as $v){
            // 递归子级 合并
            $Childs = array_merge_recursive($Childs, $this->getChildRegionIds($v, $tmpParent, false));
        }
        if ($init){
            $Childs[] = $regionId;
        }
        return $Childs;
    }

    // 查询是否有用户绑定到区域站点
    public function isBindRegionByUserIds($userIds)
    {
        if (empty($userIds)){
            return false;
        }
        $RUs = RegionUser::find([
            'user_id IN ({userIds:array})',
            'bind' => [
                'userIds' => $userIds,
            ],
        ])->toArray();
        if ($RUs){
            return true;
        }
        return false;
    }

    // 删除前查询区域站点的业务关系
    public function delRegionCheck($regionId)
    {
        // 查询区域/站点 车辆关系
        $RV = RegionVehicle::arrFindFirst([
            'region_id' => $regionId,
        ]);
        if (false !== $RV){
            throw new DataException([500, '当前区域/站点有车辆关系，不可删除']);
        }
        // 查询区域/站点 骑手关系
        $RD = RegionDrivers::arrFindFirst([
            'region_id' => $regionId,
        ]);
        if (false !== $RD){
            throw new DataException([500, '当前区域/站点有骑手关系，不可删除']);
        }
        // 查询是否有业务员
        $RU = RegionUser::arrFindFirst([
            'region_id' => $regionId
        ]);
        if (false !== $RU){
            throw new DataException([500, '当前区域/站点有业务员关系，不可删除']);
        }
    }

}

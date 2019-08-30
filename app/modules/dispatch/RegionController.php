<?php
namespace app\modules\dispatch;


use app\modules\BaseController;
use app\services\data\CommonData;
use app\services\data\RegionData;
use app\models\dispatch\RegionUser;

//区域模块
class RegionController extends BaseController
{
    /**
     * 查询区域 1.5
     * code：60004
     */
    public function listAction()
    {
        $fields = [
            //区域级别
            'regionLevel' => [
                'min' => 1,
                'max' => 9,
            ],
            //区域code
            'regionCode' => 0,
            //区域名称
            'regionName' => 0,
            'regionStatus' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (!$parameter){
            return;
        }
        // 如果需要全部，去掉分页
        if (isset($_GET['type']) && 'list'==$_GET['type']){
            unset($parameter['pageNum']);
            unset($parameter['pageSize']);
        }
        $RegionData =new RegionData();
        // 有用户区域关系
        if (isset($this->authed->regionId) && $this->authed->regionId>0){
            // 查询下属站点
            $siteIds = $RegionData->getBelongRegionIdsByRegionId($this->authed->regionId, $this->authed->insId);
            $parameter['idList'] = $siteIds;
        }
        $parameter['insId'] = $this->authed->insId;
        $parameter['regionType'] = 1;
        $parameter['isDelete'] = 1;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60004,
            'parameter' => $parameter
        ],"post");
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'未获取到信息');
        }
        $regionlist = $result['content']['regionDOS'];
        //父级区域信息
        $parentIds = [];
        $regionIds = [];
        foreach ($regionlist as $k => $region) {
            $regionIds[] = $region['id'];
            if ($region['parentId']>0){
                $parentIds[] = $region['parentId'];
            }
        }
        $parentIds = array_values(array_unique($parentIds));
        // 获取父级区域信息
        $parentres = $RegionData->getRegionByIds($parentIds, true);
        // 获取区域负责人信息
        $regionLeaders = $RegionData->getRegionLeaderByIds($regionIds);
        //分页返回
        $meta = isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : null;
        // 定义返回字段及处理规则
        $fields = [
            'id' => '',
            'regionLevel' => '',
            'regionCode' => '',
            'regionName' => '',
            'parentRegion' => '---',
            'regionRemark' => '',
            'leader' => '',
            'parentId' => 0,
            'proviceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'address' => '',
            'regionStatus' => [
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用',
                ]
            ],
            'createAt' => [
                'fun' => 'time'
            ],
        ];
        // 结果处理
        $list = [];
        foreach ($regionlist as $key => $value){
            // 父级区域
            if (isset($parentres[$value['parentId']])){
                $value['parentRegion'] = $parentres[$value['parentId']]['regionName'];
            }
            // 负责人
            if (isset($regionLeaders[$value['id']])){
                $value['leader'] = $regionLeaders[$value['id']]['userName'];
            }
            $list[$key] = $this->backData($fields,$value);
        }
        return $this->toSuccess($list,$meta);
    }

    /**
     * 区域详情
     * code：
     */
    public function oneAction($id)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60004,
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        if ($result['statusCode'] != '200' || count($result['content']['regionDOS']) != 1) {
            return $this->toError(500,'未获取到有效数据');
        }
        $region = $result['content']['regionDOS'][0];
        if ($region['proviceId'] != 0) {
            $p = $this->userData->getRegionName($region['proviceId']);
            $region['proviceName'] = $p['areaName'];
            unset($p);
        }
        if ($region['cityId'] != 0) {
            $c = $this->userData->getRegionName($region['cityId']);
            $region['cityName'] = $c['areaName'];
            unset($c);
        }
        if (isset($region['areaId']) && $region['areaId'] != 0) {
            $c = $this->userData->getRegionName($region['areaId']);
            $region['areaName'] = $c['areaName'];
            unset($c);
        }
        $RegionData =new RegionData();
        if ($region['parentId'] > 0){
            // 获取父级区域信息
            $parentres = $RegionData->getRegionByIds([$region['parentId']], true);
        }
        // 定义返回字段及处理规则
        $fields = [
            'id' => '',
            'regionLevel' => '',
            'regionCode' => '',
            'regionName' => '',
            'parentRegion' => '---',
            'regionRemark' => '',
            'parentId' => 0,
            'proviceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'address' => '',
            'proviceName' => '',
            'cityName' => '',
            'areaName' => '',
            'regionStatus' => [
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用',
                ]
            ],
            'createAt' => [
                'fun' => 'time'
            ],
        ];
        // 父级区域
        if (isset($parentres[$region['parentId']])){
            $region['parentRegion'] = $parentres[$region['parentId']]['regionName'];
        }
        $region = $this->backData($fields,$region);
        // 查询关联用户信息
        $userList = $RegionData->getRegionUserList($id)[$id] ?? [];
        // 处理用户关系
        $users = [];
        foreach ($userList as $user){
            $users[] = [
                'RUid' => $user['RUid'],
                'userId' => $user['id'],
                'userName' => $user['userName'],
                'phone' => $user['phone'],
                'realName' => $user['realName'],
                'isLeader' => $user['isLeader'],
            ];
        }
        $region['users'] = $users;
        return $this->toSuccess($region);
    }


    /**
     * 新增区域 1.5
     * code：60001
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'regionLevel' => '请选择区域级别',
            'parentId' => [
                'def' => 0,
            ],
            'regionCode' => '请输入区域代码',
            'regionName' => '请输入区域名称',
            'regionStatus' => '请选择区域状态',
            'regionRemark' => [
                'def' => ''
            ],
            'proviceId' => '请选择所属省份',
            'cityId' => '请选择所属省市',
            'areaId' => '请选择所属区县',
            'address' => 0
        ];
        $parameter = $this->getArrPars($fields, $request);
        if (!$parameter){
            return;
        }
        // 如果不是一级区域，必需有父级区域
        if (0==$parameter['parentId'] && 1!=$parameter['regionLevel']){
            return $this->toError(500,'请选择父级区域');
        }
        $insId = $this->authed->insId;
        // 判断区域代码是否存在
        if ($this->RegionData->hasRegionCode($parameter['regionCode'], $insId, RegionData::RegionType)){
            return $this->toError(500, '区域代码已存在');
        }
        // 判断区域名称是否存在
        if ($this->RegionData->hasRegionName($parameter['regionName'], $insId, RegionData::RegionType)){
            return $this->toError(500, '区域名称已存在');
        }
        // 机构id
        $parameter['insId'] = $this->authed->insId;
        $parameter['regionType'] = 1;
        $parameter['createAt'] = time();
        $parameter['updateAt'] = 0;
        // 判断业务员是否已被使用
        if (isset($request['users'])){
            $userIds = [];
            foreach ($request['users'] as $user){
                $userIds[] = $user['userId'];
            }
            if ((new RegionData())->isBindRegionByUserIds($userIds)){
                return $this->toError(500, '业务员已绑定至其它区域/站点');
            }
        }
        // 新增区域
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60001',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'新增失败'.$result['msg']);
        }
        // 无用户关系直接返回
        if (!isset($request['users']) || 0==count($request['users'])){
            return $this->toSuccess(200, '新增成功' );
        }
        $regionId = $result['content']['id'];
        //处理负责人关系 暂无批量插入接口
        foreach ($request['users'] as $user){
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60007',
                'parameter' => [
                    'regionId' => $regionId,
                    'isLeader' => $user['isLeader'],
                    'userId' => $user['userId'],
                ]
            ],"post");
            //结果处理返回
            if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
                return $this->toError(500,'用户关系维护失败');
            }
        }
        return $this->toSuccess($result['statusCode'], '新增成功' );
    }


    /**
     * 修改区域 1.5
     * code：60002
     */
    public function UpdateAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'regionLevel' => 0,
            'parentId' => 0,
            'regionCode' => 0,
            'regionName' => 0,
            'regionStatus' => 0,
            'regionRemark' => 0,
            'proviceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'address' => 0
        ];
        $parameter = $this->getArrPars($fields, $request, true);
        if (!$parameter){
            return $this->toError(500, '无可更新内容');
        }
        // 禁用判断
        if (isset($parameter['regionStatus']) && 2==$parameter['regionStatus'])
        {
            //调用微服务 查询是否有子级区域
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60004',
                'parameter' => [
                    'insId' => $this->authed->insId,
                    'parentId' => $id,
                ]
            ],"post");
            // 失败返回
            if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
                return $this->toError(500,'操作失败');
            }
            // 有子级区域禁止删除
            if (count($result['content']['regionDOS'])>0){
                return $this->toError(500,'当前区域存在子级区域/站点，无法执行禁用操作');
            }
        }
        $insId = $this->authed->insId;
        if (isset($parameter['regionCode']) && isset($parameter['regionName'])){
            // 判断区域代码是否存在
            if ($this->RegionData->hasRegionCode($parameter['regionCode'], $insId, RegionData::RegionType, $id)){
                return $this->toError(500, '区域代码已存在');
            }
            // 判断区域名称是否存在
            if ($this->RegionData->hasRegionName($parameter['regionName'], $insId, RegionData::RegionType, $id)){
                return $this->toError(500, '区域名称已存在');
            }
        }
        $parameter['id'] = $id;
        // 机构id
        $parameter['insId'] = $insId;
        $parameter['updateAt'] = time();
        $params = [
            'code' => '60002',
            'parameter' => $parameter
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,$params,"post");
        //结果处理返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'修改失败');
        }
        return $this->toSuccess(200, '修改成功' );
    }

    /**
     * 删除区域 1.5
     * code：60003
     */
    public function DeleteAction($id)
    {
        //调用微服务 查询是否有子级区域
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60004',
            'parameter' => [
                'insId' => $this->authed->insId,
                'parentId' => $id,
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'操作失败');
        }
        // 有子级区域禁止删除
        if (count($result['content']['regionDOS'])>0){
            return $this->toError(500,'当前删除区域存在子级区域，无法执行删除操作');
        }
        // 删除前校验业务关系
        (new RegionData())->delRegionCheck($id);
        //调用微服务 删除区域
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60003',
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        //失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'操作失败');
        }
        // 删除区域用户关系
        $res = (new RegionData())->delRegionUser(['regionId'=>$id]);
        if (false === $res){
            return $this->toError(500,'处理区域用户关系失败');
        }
        return $this->toSuccess(200, '操作成功' );
    }

    // 新增/编辑业务员
    public function UpstaffAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $id = $request['RUid'] ?? $request['id'] ?? false;
        $regionId = $request['siteId'] ?? $request['regionId'] ?? false;
        $userId = $request['userId'] ?? false;
        $isLeader = $request['isLeader'] ?? 1;
        if (!$regionId || !$userId){
            return $this->toError(500, '参数错误');
        }
        // 如果新增负责人，查是否已有负责人
        if (2==$isLeader){
            $RU = RegionUser::arrFindFirst([
                'region_id' => $regionId,
                'is_leader' => 2,
            ]);
            if ($RU && $id != $RU->id){
                return $this->toError(500, '同区域/站点不可有多个负责人');
            }
        }
        // 查是否有记录
        if ($id){
            $RU = RegionUser::arrFindFirst([
                'id' => $id,
            ]);
        }else{
            // 查询是否已有关系
            $RU = RegionUser::arrFindFirst([
                // 'region_id' => $regionId,
                'user_id' => $userId,
            ]);
            // 用户存在其他关系，不允许新增
            if ($RU && $regionId != $RU->region_id){
                return $this->toError(500, '此用户已绑定至其它区域/站点');
            }
        }
        $isNew = false;
        // 不存在则新建
        if (false===$RU){
            $isNew = true;
            $RU = new RegionUser();
            $RU->create_at = time();
        }
        $RU->region_id = $regionId;
        $RU->user_id = $userId;
        $RU->is_leader = $isLeader;
        $RU->update_at = time();
        $bol = $RU->save();
        if (false===$bol){
            return $this->toError(500, '保存失败');
        }
        //  发送短信
        if ($isNew){
            (new CommonData())->SendSiteUserSMS($userId);
        }
        return $this->toSuccess([
            'id' => $RU->id,
        ]);
    }

    // 删除业务员关系
    public function DelstaffAction($id)
    {
        $RU = RegionUser::arrFindFirst([
            'id' => $id,
        ]);
        $bol = $RU->delete();
        if (false===$bol){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }

}

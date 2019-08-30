<?php
namespace app\modules\dispatch;


use app\models\dispatch\Drivers;
use app\models\dispatch\DriversAttribute;
use app\models\dispatch\Region;
use app\models\service\InsLicenseplateStat;
use app\models\service\PostofficeVehicleLog;
use app\models\service\Qrcode;
use app\models\service\RegionLicenseplateStat;
use app\models\service\VehicleUsage;
use app\models\service\YearlycheckTask;
use app\models\users\Institution;
use app\models\users\User;
use app\modules\BaseController;
use app\services\data\DriverData;
use app\services\data\SiteData;
use app\services\data\RegionData;
use app\services\data\StoreData;
use app\services\data\VehicleData;
use app\models\service\RegionVehicle;
use app\models\service\Vehicle;
use app\models\dispatch\RegionDrivers;
use app\models\dispatch\RegionUser;
use app\common\library\AnQiService;
use app\models\service\StoreVehicle;

//站点模块
class SiteController extends BaseController
{
    /**
     * 查询站点 1.5
     */
    public function listAction()
    {
        $fields = [
            //站点代码
            'regionCode' => [
                'as' => 'siteCode',
            ],
            //站点名称
            'regionName' => [
                'as' => 'siteName',
            ],
            //状态 1启用 2禁用
            'regionStatus' => [
                'as' => 'siteStatus',
            ],
            // 父级区域
            'parentId' => [
                'as' => 'regionId',
            ],
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        // 如果需要全部，去掉分页
        if (isset($_GET['type']) && 'list'==$_GET['type']){
            unset($parameter['pageNum']);
            unset($parameter['pageSize']);
        }
        $RegionData =new RegionData();
        $parameter['is_delete'] = 1;
        if ($this->authed->insId > 0){
            $parameter['insId'] = $this->authed->insId;
        }
        $parameter['regionType'] = 2;
        $parameter['isDelete'] = 1;
        // 有用户区域关系
        if (isset($this->authed->regionId) && $this->authed->regionId>0){
            // 查询下属站点
            $siteIds = $RegionData->getBelongRegionIdsByRegionId($this->authed->regionId, $this->authed->insId, true);
            // 避免无下属站点时权限问题
            $siteIds[] = $this->authed->regionId;
            $parameter['idList'] = $siteIds;
        }
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
        //分页返回
        $meta = $result['content']['pageInfo'] ?? [];
        $list = [];
        // 定义返回字段规则
        $fields = [
            'id' => '',
            'siteCode' => [
                'as' => 'regionCode',
            ],
            'siteName' => [
                'as' => 'regionName',
            ],
            'siteRemark' => [
                'as' => 'regionRemark',
            ],
            'regionLevel' => [
                'as' => 'parentLevel',
            ],
            'regionId' => [
                'as' => 'parentId',
            ],
            'regionName' => [
                'as' => 'parentName',
            ],
            'proviceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'address' => '',
            'siteStatus' => [
                'as' => 'regionStatus',
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用',
                ],
            ],
            'createAt' => [
                'fun' => 'time',
            ]
        ];
        foreach ($regionlist as $key => $value){
            if (isset($parentres[$value['parentId']])){
                $value['parentName'] = $parentres[$value['parentId']]['regionName'];
                $value['parentLevel'] = $parentres[$value['parentId']]['regionLevel'];
            }
            $list[] = $this->backData($fields,$value);
        }
        return $this->toSuccess($list,$meta);
    }

    /**
     * 站点详情
     */
    public function OneAction($id)
    {
        $RegionData =new RegionData();
        // 查询站点详情
        $site = $RegionData->getRegionById($id);
        $regionId = $site['parentId'];
        if ($site['proviceId'] > 0) {
            $p = $this->userData->getRegionName($site['proviceId']);
            $site['proviceName'] = $p['areaName'];
            unset($p);
        }
        if ($site['cityId'] > 0) {
            $c = $this->userData->getRegionName($site['cityId']);
            $site['cityName'] = $c['areaName'];
            unset($c);
        }
        if (isset($site['areaId']) && $site['areaId'] > 0) {
            $c = $this->userData->getRegionName($site['areaId']);
            $site['areaName'] = $c['areaName'];
            unset($c);
        }
        if ($regionId>0){
            // 获取区域信息
            $region = $RegionData->getRegionById($regionId);
            $site['parentName'] = $region['regionName'];
            $site['parentLevel'] = $region['regionLevel'];
        }
        $fields = [
            'id' => '',
            'siteCode' => [
                'as' => 'regionCode',
            ],
            'siteName' => [
                'as' => 'regionName',
            ],
            'siteRemark' => [
                'as' => 'regionRemark',
            ],
            'regionId' => [
                'as' => 'parentId',
            ],
            'regionName' => [
                'as' => 'parentName',
                'def' => '',
            ],
            'regionLevel' => [
                'as' => 'parentLevel',
                'def' => 0,
            ],
            'siteStatus' => [
                'as' => 'regionStatus',
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用'
                ]
            ],
            'createAt' => [
                'fun' => 'time'
            ],
            'proviceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'proviceName' => 0,
            'cityName' => 0,
            'areaName' => 0,
            'address' => ''
        ];
        $site = $this->backData($fields,$site);
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
        $site['users'] = $users;
        return $this->toSuccess($site);
    }


    /**
     * 新增站点 1.5
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理 兼容邮管1.0接口字段
        $fields = [
            'regionLevel' => '请选择区域级别',
            'parentId' => [
                'need' => '请选择区域',
                'as' => 'regionId',
            ],
            'regionCode' => [
                'need' => '请输入站点代码',
                'as' => 'siteCode',
            ],
            'regionName' => [
                'need' => '请输入站点名称',
                'as' => 'siteName',
            ],
            'regionStatus' => [
                'need' => '请选择状态',
                'as' => 'siteStatus',
            ],
            'regionRemark' => [
                'as' => 'siteRemark',
                'def' => '',
            ],
            'proviceId' => '请选择所属省份',
            'cityId' => '请选择所属省市',
            'areaId' => '请选择所属区县',
            'address' => 0
        ];
        $parameter = $this->getArrPars($fields, $request);
        // 修正新关系下的区域级别
        $parameter['regionLevel'] += 1;
        // 如果不是一级区域，必需有父级区域
        if (0==$parameter['parentId'] && 1!=$parameter['regionLevel']){
            return $this->toError(500,'请选择父级区域');
        }
        $insId = $this->authed->insId;
        // 判断站点代码是否存在
        if ($this->RegionData->hasRegionCode($parameter['regionCode'], $insId, RegionData::SiteType)){
            return $this->toError(500, '站点代码已存在');
        }
        // 判断站点名称是否存在
        if ($this->RegionData->hasRegionName($parameter['regionName'], $insId, RegionData::SiteType)){
            return $this->toError(500, '站点名称已存在');
        }
        // 机构id
        $parameter['insId'] = $this->authed->insId;
        $parameter['regionType'] = 2;
        $parameter['createAt'] = time();
        $parameter['updateAt'] = 0;
        // 判断业务员是否已被使用
        if (isset($request['users'])){
            $userIds = [];
            foreach ($request['users'] as $user){
                $userIds[] = $user['userId'];
            }
            if ((new RegionData())->isBindRegionByUserIds($userIds)){
                return $this->toError(500, '业务员已绑定其它区域/站点');
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
        return $this->toSuccess(200, '新增成功' );
    }


    /**
     * 修改站点1.5
     */
    public function UpdateAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'regionLevel' => 0,
            'parentId' => [
                'as' => 'regionId',
            ],
            'regionCode' => [
                'as' => 'siteCode',
            ],
            'regionName' => [
                'as' => 'siteName',
            ],
            'regionStatus' => [
                'as' => 'siteStatus',
            ],
            'regionRemark' => [
                'as' => 'siteRemark',
            ],
            'proviceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'address' => 0
        ];
        $parameter = $this->getArrPars($fields, $request, true);
        if (!$parameter){
            return;
        }
        // 修正新关系下的区域级别
        if (isset($parameter['regionLevel'])) $parameter['regionLevel'] += 1;
        $insId = $this->authed->insId;
        if (isset($parameter['regionCode']) && isset($parameter['regionName'])){
            // 判断区域代码是否存在
            if ($this->RegionData->hasRegionCode($parameter['regionCode'], $insId, RegionData::SiteType, $id)){
                return $this->toError(500, '站点代码已存在');
            }
            // 判断区域名称是否存在
            if ($this->RegionData->hasRegionName($parameter['regionName'], $insId, RegionData::SiteType, $id)){
                return $this->toError(500, '站点名称已存在');
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
        return $this->toSuccess(200, '更新成功' );
    }

    /**
     * 删除站点
     */
    public function DeleteAction($id)
    {
        // 删除前校验业务关系
        (new RegionData())->delRegionCheck($id);
//        // 查询站点
//        $site = Region::findFirst([
//            'id = :id:',
//            'bind' => [
//                'id' => $id,
//            ]
//        ]);
//        if (false===$site){
//            return $this->toError(500, '未查询到站点信息');
//        }
//        // 删除站点
//        $site->is_delete = 2;
//        $bol = $site->save();
//        if (false===$bol){
//            return $this->toError(500, '操作失败');
//        }
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
        return $this->toSuccess();
    }

    /**
     * APP获取站点信息1.5
     */
    public function InfoAction()
    {
        // 获取登录用户的站点ID
        $regionId = $this->RegionData->getRegionIdByUserId($this->authed->userId);
        $site =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Region','r')
            ->where('r.id = :regionId:', ['regionId'=>$regionId])
            // 查询关联用户
            ->leftJoin('app\models\dispatch\RegionUser', 'ru.region_id = r.id','ru')
            // ->andWhere('ru.is_leader = 2')
            ->columns('r.id, r.region_code AS siteCode, r.region_name AS siteName, r.provice_id AS proviceId, r.city_id AS cityId, r.area_id AS areaId, r.address, ru.user_id')
            ->getQuery()
            ->getSingleResult();
        if (false===$site){
            return $this->toError(500, '未查询到站点信息');
        }
        $site = $site->toArray();
        if ($site['user_id'] > 0){
            // 获取联系人信息
            $linkMan = $this->userData->getUserById($site['user_id']);
            $site['linkman'] = $linkMan['realName'];
            $site['phone'] = $linkMan['phone'];
        }
        // 获取行政区域地址
        if ($site['proviceId'] > 0 && $site['cityId'] > 0) {
            $site['proviceName'] = $this->userData->getRegionName($site['proviceId'])['areaName'] ?? '';
            $site['cityName'] = $this->userData->getRegionName($site['cityId'])['areaName'] ?? '';
            $site['areaName'] = $this->userData->getRegionName($site['areaId'])['areaName'] ?? '';
            $site['address'] = $site['proviceName'] . $site['cityName'] . $site['areaName'] . $site['address'];
        }
        // 返回
        $fields = [
            'id' => 0,
            'siteCode' => '',
            'siteName' => '',
            'linkman' => '',
            'phone' => '',
            'address' => '',
        ];
        $site = $this->backData($fields,$site);
        return $this->toSuccess($site, '获取成功' );
    }

    // 站点APP获取附近门店
    public function StoreAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (empty($request['lng']) || empty($request['lat'])){
            return $this->toError(500, '未获取到定位信息');
        }
        $data = (new StoreData())->getAPPStoreMap($request, false, false, false);
        return $this->toSuccess($data);
    }


    /**
     * 站点APP扫描车辆二维码获得车辆信息
     * @author Lishiqin
     * @param string bianhao 得威二维码编号
     * @return mixed
     */
    public function QrcodeInfoAction()
    {
        // 判断二维码编号的有效性
        $request = $this->request->get();
        $bianhao = $request['bianhao'] ?? null;
        if (empty($bianhao)) {
            return $this->toError(500, '二维码不合法');
        }

        // 获取登录用户的站点ID
        $regionId = $this->RegionData->getRegionIdByUserId($this->authed->userId);
        if (is_null($regionId)){
            return $this->toError(500, '此用户未授权站点关系');
        }
        // 请求微服务接口获得车辆信息
        $result = $this->curl->httpRequest($this->Zuul->vehicle, [
            'code' => 60012,
            'parameter' => [
                'bianhaoList' => [$bianhao]
            ]
        ], "post");
        // 没有车辆信息
        if ($result['statusCode'] != 200 || !isset($result['content']['vehicleDOS'][0])){
            // 查询二维码是否存在
            $qrcodeInfo = Qrcode::arrFindFirst([
                'bianhao' => $bianhao
            ]);
            if (!$qrcodeInfo){
                return $this->toError(500, "没有查到二维码信息");
            }
            // 2-已发放 3-已激活
            if (2==$qrcodeInfo->status){
                return $this->toError(500, "当前二维码未做三码合一处理，请通知客服及时处理");
            }
            return $this->toError(500, "未查询到车辆信息");
        }
        $vehicle = $result['content']['vehicleDOS'][0];
        $vehicle['lng']          = $vehicle['lng'] ? $vehicle['lng'] : 0;
        $vehicle['lat']          = $vehicle['lat'] ? $vehicle['lat'] : 0;
        // 返回结构
        $data = [
            'status' => 1, // 1-正常， 2-提示
            'vehicle' => &$vehicle,
            'tip' => '',
        ];
        /*
        // 查询车辆是否有租赁属性
        $hasRent = VehicleUsage::arrFindFirst([
            'vehicle_id' => $vehicle['id'],
            'use_attribute' => 2,
        ]);
        if ($hasRent){
            return $this->toError(500, '车辆已绑定至租赁系统');
        }*/
        // 查询车辆是否已经绑定站点
        $RV = RegionVehicle::arrFindFirst([
            'vehicle_id' => $vehicle['id']
        ]);
        // 未绑定站点，无需后续验证
        if (false == $RV){
            return $this->toSuccess($data);
        }
        // 不在当前快递公司
        if ($RV->ins_id != $this->authed->insId){
            return $this->toError(500, "当前车辆已绑定其他快递公司");
        }
        // 已在当前站点
        if ($RV->region_id == $regionId){
            return $this->toError(500, '车辆已绑定当前站点，无需操作');
        }
        // TODO:后续为同公司不同站点逻辑
        // 挂靠在当前公司
        if ($RV->region_id > 0){
            // 查询已绑定站点名称
            $bindStie = Region::arrFindFirst([
                'id' => $RV->region_id,
            ]);
            if ($bindStie) $bindStie = $bindStie->toArray();
            $bindStieName = $bindStie['region_name'] ?? '';
        }else{
            // 获取快递公司名称
            $expressNames = $this->userData->getCompanyNamesByInsIds([$RV->ins_id]);
            $bindStieName = $expressNames[$RV->ins_id] ?? '';
        }
        // 车辆未绑定骑手
        if (!($RV->driver_id > 0)){
            $data['status'] = 2;
            $data['tip'] = "该车辆目前绑定的是{$bindStieName}，请确认是否调拨到本站点。";
            return $this->toSuccess($data);
        }
        // TODO:后续为已绑定骑手逻辑
        // 查询是否存在未通过年检任务
        $yearCheckStatus = (new VehicleData())->getYaerCheckStatusByVehicleId($RV->getVehicleId());
        // 未完成年检任务
        $undoneTask = !$yearCheckStatus;
        // 查询骑手姓名
        $driver = Drivers::arrFindFirst([
            'id' => $RV->driver_id,
        ]);
        if (!$driver){
            return $this->toError(500, '关联骑手信息异常');
        }
        $driver = $driver->toArray();
        $driverName = $driver['real_name'] ?? '';
        if ($undoneTask){
            return $this->toError(500, "该车辆当前有未完成的年检任务且{$driverName}小哥正在使用，无法调拨。");
        }
        return $this->toError(500, "该车辆目前由{$bindStieName} {$driverName}小哥使用，如果需要调拨到本站点，请联系{$bindStieName}，先解除车辆与站点的绑定。");
    }

    /**
     * 获取车辆是否已被门店绑定
     * @param string $id 得威二维码编号
     * @return mixed
     */
    public function vehicleStoreBind($id) {
        $params = [
            "code" => 10028,
            "parameter" => [
                "vehicleId" => $id,
            ]
        ];

        // 请求微服务接口提交换电柜组状态
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        // 判断结果返回
        if ($result['statusCode'] == 200 && isset($result['content']['data']) && count($result['content']['data']) > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 站点批量绑定车辆 1.5
     */
    public function VehicleBindAction()
    {
        // 请求中站点ID及车辆ID列表数据有效性验证
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['vehicleIdList']) || empty($request['vehicleIdList'])){
            return $this->toError(500, '无可绑定车辆');
        }
        // 获取登录用户的站点ID
        $regionId = $this->RegionData->getRegionIdByUserId($this->authed->userId);
        if (is_null($regionId)){
            return $this->toError(500, '此用户未授权站点关系');
        }
        $vehicleIdList = $request['vehicleIdList'];
        $VehicleData = new VehicleData();
        /*
        // 查询是否有已经绑定了门店的车辆
        $SVS = $VehicleData->getStoreVehicleSByVehicleIds($vehicleIdList);
        // 如果有绑定门店的车辆，直接返回
        if (!empty($SVS)){
            return $this->toError(500, '已有车辆被门店绑定');
//            $StoreVehicleIds = [];
//            foreach ($SVS as $SV){
//                $StoreVehicleIds[] = $SV['vehicle_id'];
//            }
//            // 返回已绑定门店的车辆
//            return $this->toSuccess([
//                'status' => 2,
//                'bindStoreVehicleIdList' => $StoreVehicleIds,
//            ]);
        }*/
        // 开启事务
        $this->dw_service->begin();
        // 查询已经绑定了区域的车辆
        $RVS = $VehicleData->getRegionVehicleSByVehicleIds($vehicleIdList, false);
        // 获取已绑定车辆的id
        $bindIds = [];
        $time = time();
        // 处理已绑定的车辆
        if (!empty($RVS)){
            foreach ($RVS as $RV){
                $bindIds[] = $RV->vehicle_id;
            }
            // 更新绑定关系
            $res = $RVS->update([
                'region_id' => $regionId,
                'ins_id' => $this->authed->insId,
                'bind_status' => 1,
                'driver_id' => 0,
                'region_vehicle_time' => $time,
                'update_time' => $time,
            ]);
            if (false===$res){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 获取未绑定过的车辆id
        $noBindIds = array_values(array_diff($vehicleIdList, $bindIds));
        $insId = $this->authed->insId;
        // 新增绑定
        if (!empty($noBindIds)){
            // 查询配额
            $insLicenseplateStat = InsLicenseplateStat::arrFindFirst([
                'ins_id' => $insId
            ]);
            if (false===$insLicenseplateStat || count($noBindIds) > $insLicenseplateStat->unused_count){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '当前快递公司可用配额不足,剩余可用数：'.($insLicenseplateStat->unused_count ?? 0));
            }
            // 已使用数
            $insLicenseplateStat->used_count += count($noBindIds);
            // 未使用数
            $insLicenseplateStat->unused_count -= count($noBindIds);
            $insLicenseplateStat->update_time = $time;
            $bol = $insLicenseplateStat->save();
            if (false===$bol){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
            foreach ($noBindIds as $vid){
                $data = [
                    'region_id' => $regionId,
                    'ins_id' => $this->authed->insId,
                    'vehicle_id' => $vid,
                    'bind_status' => 1,
                    'driver_id' => 0,
                    'region_vehicle_time' => $time,
                    'update_time' => $time,
                ];
                $res = (new RegionVehicle())->create($data);
                if (false===$res){
                    // 事务回滚
                    $this->dw_service->rollback();
                    return $this->toError(500, '操作失败');
                }
            }
            // 插入配额使用记录
            $useRec = [
                'ins_id' => $insId,
                'region_id' => $regionId,
                'used_count' => count($noBindIds),
                'create_time' => $time
            ];
            $recRes = (new RegionLicenseplateStat())->create($useRec);
            if (false===$recRes){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 更新车辆表绑定关系
        $vehicleS = Vehicle::find([
            'id IN ({vehicleIdList:array})',
            'bind' => [
                'vehicleIdList' => $vehicleIdList,
            ]
        ]);
        $res = $vehicleS->update([
            'has_bind' => 2,
            'driver_bind' => 1,
            'driver_id' => 0,
            'update_time' => time(),
            'use_status' => 1,
        ]);
        if (false===$res){
            // 事务回滚
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }
        // 查询已有邮管属性的车辆
        $hasUsageVehicles = VehicleUsage::arrFind([
            'vehicle_id' => ['IN', $vehicleIdList],
            'use_attribute' => 4,
        ]);
        $hasUsageVehicleIdList = [];
        if ($hasUsageVehicles){
            $hasUsageVehicles = $hasUsageVehicles->toArray();
            foreach ($hasUsageVehicles as $hasUsageVehicle){
                $hasUsageVehicleIdList[] = $hasUsageVehicle['vehicle_id'];
            }
        }
        $notUsageVehicleIdList = array_diff($vehicleIdList, $hasUsageVehicleIdList);
        // 插入用途
        foreach ($notUsageVehicleIdList as $notUsageVehicleId){
            $bol = (new VehicleUsage())->create([
                'vehicle_id' => $notUsageVehicleId,
                'use_attribute' => 4,
                'create_time' => time(),
            ]);
            if (false===$bol){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 提交事务
        $this->dw_service->commit();
//        // 向外部邮管推送快递公司绑定车辆关系信息，不影响绑定业务
//        $this->CallService('user', 10203, [
//            'insId' => $this->authed->insId,
//            'ids' => $noBindIds
//        ]);
        //推送数据author zyd 20190227
        $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 10310,
            'parameter' => [
                "insId" => $this->authed->insId,
                "ids" => $vehicleIdList
            ],
        ],"post");
        return $this->toSuccess(null);
    }

    // 站点APP扫骑手码获取骑手信息1.5
    public function ScandriverAction()
    {
        // 获取请求的骑手二维码
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['QRCode']) || empty($request['QRCode'])){
            return $this->toError(500, '参数错误');
        }
        // 解析链接
        $req = parse_url($request['QRCode']);
        // 如果不存在
        if (!isset($req['query'])){
            return $this->toError(500, '二维码内容有误');
        }
        // 解析参数
        parse_str($req['query'], $query);
        // 手机号异常
        if (!isset($query['phone']) || empty($query['phone'])){
            return $this->toError(500, '参数错误');
        }
        // 查询骑手
        $DriverData = new DriverData();
        $driver = $DriverData->getDriverByPhone($query['phone']);
        if (is_null($driver)){
            return $this->toError(500, '未查询到骑手');
        }
        // 骑手车辆存在年检任务
        $RV = RegionVehicle::arrFindFirst([
            'driver_id' => $driver['id'],
        ]);
        if ($RV){
            // 查询是否存在未通过年检任务
            $status = (new VehicleData())->getYaerCheckStatusByVehicleId($RV->getVehicleId());
            if (false==$status){
                return $this->toError(500, '该骑手正在进行年检任务，不得重新绑定站点');
            }
        }
        $fields = [
            'id' => '',
            'phone' => '',
            'userName' => '',
            'realName' => '',
            'identify' => [
                'fun' => 'identity',
            ],
        ];
        $driver = $this->backData($fields, $driver);
        return $this->toSuccess($driver);
    }

    /**
     * 站点扫码批量绑定骑手(多条)1.5
     */
    public function BinddriversAction()
    {
        // 获取请求的骑手二维码
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['driverIds']) || empty($request['driverIds'])){
            return $this->toError(500, '参数异常');
        }
        // 去重
        $driverIds = array_values(array_unique($request['driverIds']));
        // 获取登录用户的站点ID
        $regionId = $this->RegionData->getRegionIdByUserId($this->authed->userId);
        if (is_null($regionId)){
            return $this->toError(500, '此用户未授权站点关系');
        }
        // 获取骑手已有绑定关系
        $DriverData = new DriverData();
        $RDS = $DriverData->getRegionDriverSByDriverIds($driverIds, false);
        // 开启事务
        $this->dw_dispatch->begin();
        $this->dw_service->begin();
        // 需更新的骑手ids
        $upDriverIds = [];
        foreach ($RDS as $RD){
            $upDriverIds[] = $RD->driver_id;
        }
        $bol = $RDS->update([
            'region_id' => $regionId,
            'ins_id' => $this->authed->insId,
            'update_time' => time(),
        ]);
        if (false===$bol){
            // 事务回滚
            $this->dw_dispatch->rollback();
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }
        // 查询有绑定车辆的骑手区域车辆绑定关系
        $VehicleData = new VehicleData();
        $RVS = $VehicleData->getRegionVehicleSByDriverIds($driverIds, false);
        // 获得车辆ID
        $bindVehicleIds = [];
        foreach ($RVS as $RV){
            $bindVehicleIds[] = $RV->vehicle_id;
        }
        // 解绑骑手与同机构站点的车辆绑定
        $bol = $RVS->update([
            'bind_status' => 1,
            'driver_id' => 0,
            'bind_time' => 0,
            'update_time' => time(),
        ]);
        if (false===$bol){
            // 事务回滚
            $this->dw_dispatch->rollback();
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }
        if(count($bindVehicleIds) > 0){
            // 查询车辆信息
            $VehicleS = Vehicle::find([
                'id IN ({Ids:array})',
                'bind' => [
                    'Ids' => $bindVehicleIds
                ]
            ]);
            // 更新车辆绑定关系
            $bol = $VehicleS->update([
                'driver_bind' => 1,
                'driver_id' => 0,
                'update_time' => time(),
            ]);
            if (false===$bol){
                // 事务回滚
                $this->dw_dispatch->rollback();
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 需新增的骑手ids
        $noBindIds = array_values(array_diff($driverIds, $upDriverIds));
        foreach ($noBindIds as $driverId){
            $data = [
                'region_id' => $regionId,
                'driver_id' => $driverId,
                'ins_id' => $this->authed->insId,
                'create_time' => time(),
            ];
            $bol = (new RegionDrivers())->create($data);
            if (false===$bol){
                // 事务回滚
                $this->dw_dispatch->rollback();
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        /**
         * 向骑手关系表里添加数据（邮管局）
         * 先查询没有后插入 不能发版注释
         */
        foreach ($driverIds as $driverId) {
            $driverAtt = DriversAttribute::findFirst(['conditions' => 'driver_id = :driver_id: and type_id = :type_id:','bind' => ['driver_id' => $driverId,'type_id' => 1]]);
            if (!$driverAtt) {
                $DA = new DriversAttribute();
                $DA->driver_id = $driverId;
                $DA->type_id = 1;
                $DA->create_time = time();
                if ($DA->save() == false) {
                    $this->dw_dispatch->rollback();
                    $this->dw_service->rollback();
                    return $this->toError(500, '操作失败');
                }
            }
        }
        // 提交事务
        $this->dw_dispatch->commit();
        $this->dw_service->commit();

        /**
         * 向志辉推送
         */
        foreach ($driverIds as $driverId) {
            $parameter = ['driverId' => $driverId,'eventType' => 'A'];
            $result = $this->CallService('biz', 10311, $parameter, false);
        }

        return $this->toSuccess(null);
    }

    // 站点锁车
    public function LockAction()
    {
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        if(!isset($request['vehicleId']) || !isset($request['lockType'])){
            return $this->toError(500, '参数异常');
        }
        $userId = $this->authed->userId;
        $type = $request['lockType'];
        // 1 锁车 2 解锁
        if (!in_array($type, [1,2])){
            return $this->toError(500, '非法操作');
        }
        // 查询车辆信息
        $vehicle = Vehicle::arrFindFirst(['id'=>$request['vehicleId']]);
        if (false===$vehicle){
            return $this->toError(500, '无效的车辆信息');
        }
        $vehicleId = $vehicle->id;
        // 查询车辆是否属于当前站点
        // 获取登录用户的站点ID
        $regionId = $this->RegionData->getRegionIdByUserId($userId);
        $RV = RegionVehicle::arrFindFirst([
            'region_id' => $regionId,
            'vehicle_id' => $vehicleId,
        ]);
        if (false===$RV){
            return $this->toError(500, '车辆不属于当前站点');
        }
        // 查询当前用户
        $user = User::arrFindFirst(['id'=>$userId]);
        // 记录日志
        $logBol = (new PostofficeVehicleLog())->create([
            'vehicle_id' => $vehicleId,
            'operator_name' => $user->real_name ?? '',
            'operator_id' => $userId,
            'operator_type' => PostofficeVehicleLog::OPERATOR_TYPE_USER,
            'operate_description' => '站点APP' . (VehicleData::LOCK_VEHICLE == $type ? '锁车' : '解锁'),
            'status' => 1,
            'create_time' => time(),
        ]);
        if (false == $logBol){
            return $this->toError(500, '操作失败，请重试');
        }
        $vehicleData = new VehicleData();
        // 1 锁车 2 解锁 与安骑锁车字段不同
        if (VehicleData::LOCK_VEHICLE == $type){
            $bol = $vehicleData->Lock($vehicle->id, "【站点APP锁车】用户id：{$userId}");
        }else{
            $bol = $vehicleData->UnLock($vehicle->id, "【站点APP解锁】用户id：{$userId}");
        }
        if (false===$bol){
            return $this->toError(500, $vehicleData->getLockErrorMsg());
        }
        return $this->toSuccess();
    }

}

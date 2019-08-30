<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/16
 * Time: 19:33
 */
namespace app\modules\vehicle;

use app\common\library\AnQiService;
use app\common\library\PhpExcel;
use app\models\dispatch\Drivers;
use app\models\dispatch\Region;
use app\services\data\DriverData;
use app\models\product\ProductSkuRelation;
use app\models\service\Vehicle;
use app\models\service\RegionVehicle;
use app\services\data\ProductData;
use app\services\data\RegionData;

use app\modules\BaseController;

class IndexController extends BaseController
{
    /**
     * 车辆行驶状态list
     * @var array
     */
    static $status_list = [
        1 => "骑行",
        2 => "停车",
        3 => "断电",
    ];
    /**
     * 车辆列表
     */
    public function IndexAction()
    {
        $fields = [
            // 通用搜索
            'searchText' => 0,
            'vin' => 0,
            'udid' => 0,
            'plate_num' => [
                'as' => 'plateNum',
            ],
            'bianhao' => 0,
            'status' => 0,
            'isLock' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);

        $pageSize = isset($_GET['pageSize'])&&$_GET['pageSize']>0 ? $_GET['pageSize'] : 20;
        $pageNum = isset($_GET['pageNum'])&&$_GET['pageNum']>0 ? $_GET['pageNum'] : 1;
        // 如果有区域 查询子级区域
        if (isset($this->authed->regionId) && $this->authed->regionId>0){
            // 查询下属站点
            $regionIds = (new RegionData())->getBelongRegionIdsByRegionId($this->authed->regionId, $this->authed->insId);
        }
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\RegionVehicle','rv')
            ->where('ins_id = :insId:', ['insId'=>$this->authed->insId]);
        // 有区域范围 in 区域
        if (isset($regionIds)){
            $model = $model->andWhere('rv.region_id IN ({regionIds:array})', ['regionIds'=>$regionIds]);
        }
        // 连接车辆表
        $model = $model->join('app\models\service\Vehicle', 'rv.vehicle_id = v.id','v')
            ->andWhere("[left](v.udid,5) != '99999'");
        // 通用搜索
        if (isset($parameter['searchText'])){
            $model = $model->andWhere('v.vin LIKE :searchText: OR v.plate_num LIKE :searchText: OR v.bianhao LIKE :searchText:',
                ['searchText'=>'%'.$parameter['searchText'].'%']);
        }
        if (isset($parameter['vin'])){
            $model = $model->andWhere('v.vin LIKE :vin:', ['vin'=>'%'.$parameter['vin'].'%']);
        }
        if (isset($parameter['plate_num'])){
            $model = $model->andWhere('v.plate_num LIKE :plate_num:', ['plate_num'=>'%'.$parameter['plate_num'].'%']);
        }
        if (isset($parameter['udid'])){
            $model = $model->andWhere('v.udid LIKE :udid:', ['udid'=>'%'.$parameter['udid'].'%']);
        }
        if (isset($parameter['bianhao'])){
            $model = $model->andWhere('v.bianhao LIKE :bianhao:', ['bianhao'=>'%'.$parameter['bianhao'].'%']);
        }
        if (isset($parameter['isLock'])){
            $model = $model->andWhere('v.is_lock = :is_lock:', ['is_lock'=>$parameter['isLock']]);
        }
        if (isset($parameter['status'])){
            $model = $model->andWhere('v.status = :status:', ['status'=>$parameter['status']]);
        }
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(v.id) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        // 查询数据
        $vehiclelist = $model->columns('rv.region_id AS regionId, v.id, v.udid, v.vin, v.bianhao, v.plate_num AS plateNum, v.driver_id AS driverId, v.status, v.has_bind AS hasBind, v.is_lock AS isLock, v.lat, v.lng, v.create_time AS createTime')
            ->orderBy('v.id ASC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()
            ->execute()
            ->toArray();
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        $driverIds =[];
        $regionIds =[];
        foreach ($vehiclelist as $vehicle){
            if ($vehicle['driverId']>0){
                $driverIds[] = $vehicle['driverId'];
            }
            if ($vehicle['regionId']>0){
                $regionIds[] = $vehicle['regionId'];
            }
        }
        $driverlist =[];
        if (count($driverIds)>0){
            $dresult = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60014',
                'parameter' => [
                    'idList' => $driverIds,
                ],
            ],"post");
            if ($dresult['statusCode']==200){
                foreach ($dresult['content']['driversDOS'] as $driver){
                    $driverlist[$driver['id']] = $driver;
                }
            }
        }
        $RegionData = new RegionData();
        $regionList = [];
        if (count($regionIds)>0){
            $regionList = $RegionData->getRegionByIds($regionIds, true);
        }
        // 站点车辆需再次查区域
        $pRegionIds = [];
        foreach ($vehiclelist as $k => $vehicle){
            if ($vehicle['driverId']>0 && isset($driverlist[$vehicle['driverId']])){
                $vehicle['name'] = $driverlist[$vehicle['driverId']]['realName'];
                $vehicle['phone'] = $driverlist[$vehicle['driverId']]['phone'];
            }else{
                $vehicle['name'] = '---';
                $vehicle['phone'] = '';
            }
            if ($vehicle['regionId']>0 && isset($regionList[$vehicle['regionId']])){
                $tmpRegion = $regionList[$vehicle['regionId']];
                // 如果是站点 记录再次查询区域
                if (2==$tmpRegion['regionType']){
                    $vehicle['siteName'] = $tmpRegion['regionName'];
                    $vehicle['siteId'] = $tmpRegion['id'];
                    $vehicle['regionId'] = $tmpRegion['parentId'];
                    $pRegionIds[] = $tmpRegion['parentId'];
                    $vehicle['regionName'] = '';
                }else{
                    $vehicle['regionName'] = $tmpRegion['regionName'];
                }
            }else{
                $vehicle['siteName'] = '';
                $vehicle['regionName'] = '';
            }
            $vehicle['createTime'] = date('Y-m-d H:i:s', $vehicle['createTime']);
            $vehiclelist[$k] = $vehicle;
        }
        // 取站点上级区域信息
        $pRegionList = $RegionData->getRegionByIds($pRegionIds, true);
        foreach ($vehiclelist as $k => $vehicle){
            if ($vehicle['regionId']>0 && isset($pRegionList[$vehicle['regionId']])){
                $vehiclelist[$k]['regionName'] = $pRegionList[$vehicle['regionId']]['regionName'];
            }
        }
        return $this->toSuccess($vehiclelist, $meta);
    }

    /*// 门店车辆列表
    public function StorevehiclelistAction(){
        $fields = [
            // 车辆代码
            'vehicleCode' => 0,
            // 激活状态 1激活 2未激活
            'activeStatus' => 0,
            // 门店ID
            'storeId' => 0,
            // 出租状态  1未出租 2:已出租 3未换车
            'rentStatus' => 0,
            // 骑行状态 1 骑行中 2 停车中 3 断电
            'status' => 0,
            // 骑手ID
            'driverId' => 0,
            // 车辆状态是否失联 1未失联 2失联
            'contact' => 0,
            // 信号强弱参数1强 2中 3弱
            'signal_' => 0,
            // 电池状态 电瓶是否在位 0=不在 1=在
            'charge' => 0,
            // 锁车状态 1 锁车 2 未锁车
            'isLock' => 0,
            // 2:门店租赁
            'useAttribute' => [
                'def' => 2,
            ],
            // 创建时间开始
            'createTimeStart' => 0,
            // 最后通讯时间开始
            'communicateTimeStart' => 0,
            // 页码
            'pageNum' => [
                'def' => 1,
            ],
            // 页大小
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (false === $parameter){
            return;
        }
        // 获取车辆列表
        $res = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => '60015',
            'parameter' => $parameter
        ],"post");
        if ($res['statusCode'] != 200) {
            return $this->toError($res['statusCode'],$res['msg']);
        }
        $vehicleList = $res['content']['data'];
        // 分页数据
        $meta = $res['content']['pageInfo'];
        $storeIds = [];
        $driverIds = [];
        foreach ($vehicleList as $k => $v){
            // 未绑定门店，未激活
            if (is_null($v['storeId'])){
                $vehicleList[$k]['activeStatus'] = 2;
            }else{
                // 激活
                $vehicleList[$k]['activeStatus'] = 1;
                $storeIds[] = $v['storeId'];
            }
            if (0!=$v['driverId']){
                $driverIds[] = $v['driverId'];
            }
        }
        // 获取门店信息
        $stores = $this->StoreData->getStoreByIds($storeIds, true);
        // 获取骑手信息
        $drivers = $this->DriverData->getDriverByIds($driverIds, true);
        foreach ($vehicleList as $k => $v){
            $vehicleList[$k]['storeName'] = isset($stores[(string)($v['storeId'])]) ? $stores[(string)($v['storeId'])]['storeName'] : '';
            $vehicleList[$k]['realName'] = isset($drivers[(string)($v['driverId'])]) ? $drivers[(string)($v['driverId'])]['realName'] : '';
            // 处理时间
            $vehicleList[$k]['createTime'] = (0==$v['createTime']) ? '-' : date('Y-m-d H:i:s', $v['createTime']);
            $vehicleList[$k]['updateTime'] = (0==$v['updateTime']) ? '-' : date('Y-m-d H:i:s', $v['updateTime']);
            $vehicleList[$k]['communicateTime'] = (0==$v['communicateTime']) ? '-' : date('Y-m-d H:i:s', $v['communicateTime']);
        }
        return $this->toSuccess($vehicleList, $meta);
    }*/

    /**
     * 车辆的详细数据
     */
    public function DetailAction()
    {
        //获取车辆的基本信息
        $vehicleId = intval($this->request->get('vehicleId'));
        if (!$vehicleId) {
            return $this->toError('500','车辆vehicleId不能为空');
        }
        $pram = ["vehicleId" => $vehicleId];
        $data = [
            'parameter' => $pram,
            'code' => '60005',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
           return $this->toError($result['statusCode'],$result['msg']);
        }
        $content = $result['content']['VehicleDO'];
        $content['siteCode'] = '';
        $content['siteName'] = '';
        // 获取车辆站点信息
        $RV = RegionVehicle::findFirst([
            'vehicle_id = :vehicle_id:',
            'bind' => [
                'vehicle_id' => $vehicleId
            ]
        ]);
//        $content['bindTime'] = '';服务端返回
        if ($RV && $RV->region_id>0){
            $site = Region::findFirst([
                'id = :id: and region_type = 2',
                'bind' => [
                    'id' => $RV->region_id,
                ],
            ]);
//            $content['bindTime'] = $RV->bind_time ? date('Y-m-d H:i:s', $RV->bind_time) : '';
        }
        if (isset($site) && $site){
            $content['siteCode'] = $site->region_code;
            $content['siteName'] = $site->region_name;
        }
        //获取骑手信息
        $content['realName'] = '';
        $content['userName'] = '';
        if ($content['driverId']) {
            $driver_pram = ["id" => $content['driverId']];
            $driver_data = [
                'parameter' => $driver_pram,
                'code' => '60014',
            ];
            $driver_result = $this->curl->httpRequest($this->Zuul->dispatch, $driver_data, "POST");
            if ($driver_result['statusCode'] <> 200) {
                return $this->toError($result['statusCode'],$result['msg']);
            };
            $label = ['realName', 'userName', 'identify', 'phone', 'sex'];
            if (isset($driver_result['content']['driversDOS'][0])) {
                $content['driverCreateTime'] = $driver_result['content']['driversDOS'][0]['createTime'];
                $content['driverStatus'] = $driver_result['content']['driversDOS'][0]['status'];
                foreach ($label as $k => $v) {
                    if ($v == 'identify') {
                        $content[$v] = preg_replace("/(\d{1})(\d{16})(\d{1})/", "$1****************$3", $driver_result['content']['driversDOS'][0][$v]);
                    } else {
                        $content[$v] = $driver_result['content']['driversDOS'][0][$v];
                    }
                }
            }

        }
        //获取保单信息
        $content['secureNum'] = '';
        //TODO : java暂未发版本，暂不加判断
//        if ($content['has_secure'] == 1) {
            $secure_pram = ["vehicleId" => $content['id']];
            $secure_data = [
                'parameter' => $secure_pram,
                'code' => '10008',
            ];
            $secure_result = $this->curl->httpRequest($this->Zuul->biz, $secure_data, "POST");
            if ($secure_result['statusCode'] <> 200) {
                return $this->toError($result['statusCode'],$result['msg']);
            };
            //print_r($secure_result);exit;
            if ($secure_result['content']['secureDOS']) {
                $content['secureNum'] = $secure_result['content']['secureDOS'][0]['secureNum'];
            }
//        }
        $content['communicateTime'] = $content['communicateTime'] ? date('Y-m-d H:i:s', $content['communicateTime']) : '';
        $content['createTime'] = $content['createTime'] ? date('Y-m-d H:i:s', $content['createTime']) : '';
        $content['updateTime'] = $content['updateTime'] ? date('Y-m-d H:i:s', $content['updateTime']) : '';
        $content['driverCreateTime'] = isset($content['driverCreateTime']) ? date('Y-m-d H:i:s', $content['driverCreateTime']) : '-';
        $content['bindTime'] =  $content['bindTime'] ? date('Y-m-d H:i:s', $content['bindTime']) : '';
        return $this->toSuccess($content);
    }

    /**
     * TODO:废弃
     * 锁车和解锁
     */
//    public function LockAction()
//    {
//        $vehicleId = $this->content->vehicleId;
//        if (!$vehicleId) {
//            return $this->toError('500','车辆vehicleId不能为空');
//        }
//        $type =$this->content->type;
//        if (!in_array($type, [1, 2])) {
//            return $this->toError('500','状态错误');
//        }
//        //查询车辆的信息
//        $pram = ["vehicleId" => $vehicleId];
//        $data = [
//            'parameter' => $pram,
//            'code' => '60005',
//        ];
//        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
//        if ($result['statusCode'] <> 200) {
//            return $this->toError($result['statusCode'], $result['msg']);
//        };
//        if (!$result['content']['VehicleDO']['udid']) {
//            return $this->toError('500','未找到该设备');
//        }
//        if ($type == AnQiService::LOCK) {
//            $data = [
//                'parameter' => ["udid" => $result['content']['VehicleDO']['udid']],
//                'code' => '60101',
//            ];
//        } else {
//            $data = [
//                'parameter' => ["udid" => $result['content']['VehicleDO']['udid']],
//                'code' => '60102',
//            ];
//        }
//
//        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
//        if ($result['statusCode'] <> 200) {
//            return $this->toError($result['statusCode'],$result['msg']);
//        };
//
//        return $this->toSuccess();
//    }

    /*// 导出门店车辆列表
    public function ExportstorevehicleAction()
    {
        $fields = [
            // 车辆代码
            'vehicleCode' => 0,
            // 激活状态 1激活 2未激活
            'activeStatus' => 0,
            // 门店ID
            'storeId' => 0,
            // 出租状态  1未出租 2:已出租 3未换车
            'rentStatus' => 0,
            // 骑行状态 1 骑行中 2 停车中 3 断电
            'status' => 0,
            // 骑手ID
            'driverId' => 0,
            // 车辆状态是否失联 1未失联 2失联
            'contact' => 0,
            // 信号强弱参数1强 2中 3弱
            'signal_' => 0,
            // 电池状态 电瓶是否在位 0=不在 1=在
            'charge' => 0,
            // 锁车状态 1 锁车 2 未锁车
            'isLock' => 0,
            // 2:门店租赁
            'useAttribute' => [
                'def' => 2,
            ],
            // 创建时间开始
            'createTimeStart' => 0,
            // 最后通讯时间开始
            'communicateTimeStart' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (false === $parameter){
            return;
        }
        // 获取车辆列表
        $res = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => '60018',
            'parameter' => $parameter
        ],"post");
        if ($res['statusCode'] != 200) {
            return $this->toError($res['statusCode'],$res['msg']);
        }
        $url = $res['content']['data']['url'];
        $this->toSuccess([
            'url' => $url
        ]);
    }*/

    /**
     * 导出车辆报表
     */
    public function ExportAction()
    {
        $status = $this->request->get('status');
        $isLock = $this->request->get('isLock');
        $siteBind = $this->request->get('siteBind');
        $bianhao = $this->request->get('bianhao');

        $params = [
            "regionId" => 1,
            "status" => $status,
            "isLock" => $isLock,
            "bianhao" => $bianhao,
            "siteBind" => $siteBind,
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '40000',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->search, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        $sheetRow = ['车辆编号',  '车牌号', '备案号', '站点', '骑手', '车辆状态', '激活状态', '使用状态'];//表头
        $label = ['bianhao', 'plateNum', 'recordNum', 'siteName', 'name', 'isLock', 'siteBind', 'status'];
        $data = [];
        $i = 0;
        foreach ($result['content']['data'] as $key => $val) {
            foreach ($label as $k => $v) {
                switch ($v) {
                    case 'status':
                        $data[$i][] = self::$status_list[$val[$v]];
                        break;
                    case 'isLock':
                        $data[$i][] = $val[$v] == 1 ? '锁定' : '未锁';
                        break;
                    case 'siteBind':
                        $data[$i][] = $val[$v] == 1 ? '未绑定' : '绑定';
                        break;
                    default :
                        $data[$i][] = $val[$v];
                        break;
                }
            }
            $i++;
            unset($result['content']['data'][$key]);
        }
        PhpExcel::downloadExcel('车辆报表', $sheetRow, $data);
    }

    /**
     * 车辆地图信息
     */
    public function MapAction()
    {
        $pageSize = intval($this->request->get('pageSize'));
        $pageNum = intval($this->request->get('pageNum'));

        $params = [
            "pageSize" => (int)$pageSize,
            "pageNum" => (int)$pageNum,
            "regionId" => 4,
        ];
        $data = [
            'parameter' => $params,
            'code' => '40001',
        ];
        $result = $this->curl->httpRequest($this->Zuul->search, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess($result['content']['data'],$result['content']['pageInfo']);
    }

    /**
     * 车辆解绑站点
     */
    public function UntieAction()
    {
        $vehicleId = $this->content->vehicleId;
        if (!$vehicleId) {
            return $this->toError('500','车辆vehicleId不能为空');
        }
        $vehicle = Vehicle::arrFindFirst([
            'id' => $vehicleId
        ]);
        if (false===$vehicle) {
            return $this->toError(500, '未找到车辆信息');
        }
        if ($vehicle->driver_bind == Vehicle::BIND) {
            return $this->toError('500','该车辆有骑手在用，不可解绑');
        }
        if ($vehicle->has_bind == Vehicle::NOT_BIND) {
            return $this->toError('500','该车辆未被绑定');
        }
        $RV = RegionVehicle::arrFindFirst([
            'vehicle_id' => $vehicleId
        ]);
        if (false===$RV){
            return $this->toError('500','该车辆未绑定到站点');
        }
        // 解绑站点关系
        $RV->region_id = 0;
        $bol = $RV->save();
        if (false===$bol){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }
    // 站点APP获取车辆列表
    public function SitevehiclesAction()
    {
        $pageSize = isset($_GET['pageSize'])&&$_GET['pageSize']>0 ? $_GET['pageSize'] : 20;
        $pageNum = isset($_GET['pageNum'])&&$_GET['pageNum']>0 ? $_GET['pageNum'] : 1;
        $regionId = (new RegionData())->getRegionIdByUserId($this->authed->userId);
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\RegionVehicle','rv')
            ->where('region_id = :region_id:', ['region_id'=>$regionId])
            ->join('app\models\service\Vehicle', 'rv.vehicle_id = v.id','v');
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(v.id) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        // 查询数据
        $vehiclelist = $model->columns('v.id, v.bianhao, v.plate_num AS plateNum, v.driver_id AS driverId')
            ->orderBy('v.id ASC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()
            ->execute()
            ->toArray();
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        $driverIds =[];
        $driverlist =[];
        foreach ($vehiclelist as $vehicle){
            if ($vehicle['driverId']>0){
                $driverIds[] = $vehicle['driverId'];
            }
        }
        if (count($driverIds)>0){
            $dresult = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60014',
                'parameter' => [
                    'idList' => $driverIds,
                ],
            ],"post");
            if ($dresult['statusCode']==200){
                foreach ($dresult['content']['driversDOS'] as $driver){
                    $driverlist[$driver['id']] = $driver;
                }
            }
        }
        foreach ($vehiclelist as $k => $vehicle){
            if ($vehicle['driverId']>0 && isset($driverlist[$vehicle['driverId']])){
                $vehicle['linkman'] = $driverlist[$vehicle['driverId']]['realName'];
                $vehicle['phone'] = $driverlist[$vehicle['driverId']]['phone'];
            }else{
                $vehicle['linkman'] = '---';
                $vehicle['phone'] = '';
            }
            $vehiclelist[$k] = $vehicle;
        }

        return $this->toSuccess($vehiclelist, $meta);
    }

    // 站点APP获取车辆详情
    public function AppvehicleAction($vehicleId)
    {
        // 查询车辆详情
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => '60005',
            'parameter' => [
                'vehicleId' => $vehicleId
            ]
        ],"post");
        if (200!=$result['statusCode']){
            return $this->toError(500,'未获取到有效信息');
        }
        $vehicle = $result['content']['VehicleDO'];
        // 获取商品信息
        $vehicleProductInfo  =(new ProductData())->getVehicleProductInfoByVehicleModelId($vehicle['vehicleModelId']);
        $vehicle['brandName'] = $vehicleProductInfo['brandName'];
        $vehicle['model'] = $vehicleProductInfo['model'];
        $vehicle['imgUrl'] = $vehicleProductInfo['imgUrl'];
        // 获取骑手信息
        if ($vehicle['driverId']>0){
            $driver = (new DriverData())->getDriverById($vehicle['driverId']);
            $vehicle['userName'] = $driver['userName'];
            $vehicle['realName'] = $driver['realName'];
            $vehicle['name'] = $driver['realName']; // TODO:兼容字段，下版废弃
            $vehicle['jobNo'] = $driver['jobNo'];
            $vehicle['identify'] = $driver['identify'];
            $vehicle['phone'] = $driver['phone'];
        }
        return $this->toSuccess($vehicle);
    }

    /**
     * 获取骑手身份证信息
     * @param $id author zyd
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function IdentifyAction($id)
    {
        $driver = Drivers::findFirst($id);
        if ($driver) {
            return $this->toSuccess(["identify" =>$driver->identify]);
        } else {
            return $this->toError('500', '未找到此骑手');
        }
    }

    // 车辆信息 - 图表信息
    public function StatisticChartAction($id)
    {
        $_GET['vehicleId'] = $id;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 10027,
            'parameter' => $_GET
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        $data = $result['content'];
        return $this->toSuccess($data);
    }

    public function DateValuesAction() {
        $json = $this->request->getJsonRawBody(true);
        $result = $this->userData->common($json,$this->Zuul->vehicle,60305);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        return $this->toSuccess($result,$pageInfo);
    }
}
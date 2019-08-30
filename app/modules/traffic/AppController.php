<?php
namespace app\modules\traffic;

use app\models\dispatch\DriverLicence;
use app\models\dispatch\Drivers;
use app\models\service\Secure;
use app\models\service\Vehicle;
use app\models\users\Association;
use app\modules\BaseController;
use app\services\data\DriverData;
use app\services\data\ProductData;
use app\services\data\UserData;
use app\services\data\VehicleData;

// 交管APP
class AppController extends BaseController
{
    // 查询车辆信息
    public function VehicleAction()
    {
        if (!isset($_GET['plateNum']) || empty($_GET['plateNum'])){
            return $this->toError(500, '车牌号不合法');
        }
        // 查询车辆及关联快递公司insID
        $vehicle =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\Vehicle','v')
            ->where('v.plate_num = :plateNum: or v.bianhao = :bianhao:', [
                'plateNum'=>$_GET['plateNum'], 'bianhao'=>$_GET['plateNum']
            ])
            ->join('app\models\service\RegionVehicle', 'rv.vehicle_id = v.id','rv')
            ->columns('v.id, v.udid, v.bianhao, v.vin, v.plate_num AS plateNum, v.product_id AS productId, v.product_sku_relation_id AS productSkuRelationId, v.driver_id AS driverId, rv.ins_id AS expressId, v.vehicle_model_id AS vehicleModelId')
            ->getQuery()
            ->getSingleResult();
        if (false === $vehicle){
            return $this->toError(500, '车牌/得威编码不存在');
        }
        $vehicle = $vehicle->toArray();
        $vehicle['expressName'] = (new UserData())->getExpressNamesByInsId($vehicle['expressId']);
        // 查询骑手信息
        $driver = Drivers::arrFindFirst([
            'id' => $vehicle['driverId']
        ]);
        $vehicle['driverName'] = $driver->real_name ?? '';
        $vehicle['IdentificationNumber'] = $driver->identify ?? '';
        // 查询在保信息
        $secure = Secure::findFirst([
            'vehicle_id = :vehicleId: AND start_time < :time: AND (end_time = 0 or end_time > :time:)',
            'bind' => [
                'vehicleId' => $vehicle['id'],
                'time' => time(),
            ]
        ]);
        $vehicle['isSecure'] = $secure ? true : false;
        // 获取车辆品牌型号图片
        $vehicleModelDetail = (new ProductData())->getVehicleProductInfoByVehicleModelId($vehicle['vehicleModelId']);
        // 写入车辆品牌数据
        $vehicle['vehicleName'] = $vehicleModelDetail['vehicleName'] ?? '';
        $vehicle['brandName'] = $vehicleModelDetail['brandName'] ?? '';
        $vehicle['model'] = $vehicleModelDetail['model'] ?? '';
        $vehicle['imgUrl'] = $vehicleModelDetail['imgUrl'] ?? '';
        // 获取年检状态
        $vehicle['inspection'] = (new VehicleData())->getYaerCheckStatusByVehicleId($vehicle['id']) ? 2 : 1;
        return $this->toSuccess($vehicle);
    }


    // 违章开单
    public function AddAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 车辆id
            'vehicleId' => 0,
            // 车牌号
            'vehicleLicence' => 0,
            // 骑手id
            'driverId' => 0,
            // 骑手姓名
            'driverName' => 0,
            // 骑手身份证号
            'identify' => 0,
            // 违章类型 array
            'types' => '请选择违章类型',
            // 违章图片URL array
            'picPaths' => 0,
            // 描述
            'remark' => 0,
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
//        if (!isset($parameter['vehicleLicence']) && !isset($parameter['identify'])){
//            return $this->toError(500, '车牌号和身份证不可同时为空');
//        }
        //
        if (!isset($parameter['identify'])){
            return $this->toError(500, '骑手身份证不可为空');
        }
        // 如果没有车辆id
        if ((!isset($parameter['vehicleId']) || empty($parameter['vehicleId']))
            && isset($parameter['vehicleLicence'])){
            $vehicle = Vehicle::arrFindFirst([
                'plate_num' => $parameter['vehicleLicence']
            ]);
            if (false===$vehicle){
                return $this->toError(500, '车牌不存在');
            }
            $parameter['vehicleId'] = $vehicle->id ?? 0;
        }
        // 如果没有骑手id
        if (isset($parameter['identify'])
            && (!isset($parameter['driverId']) || empty($parameter['driverId']))){
            $driver = Drivers::arrFindFirst([
                'identify' => $parameter['identify']
            ]);
            if (false == $driver){
                return $this->toError(500, '人员不存在');
            }
            $parameter['driverId'] = $driver->id ?? 0;
            $parameter['driverName'] = $driver->real_name ?? '';
        }
        if (empty($parameter['vehicleId'])){
            return $this->toError(500, '车辆不存在于系统');
        }
        if (empty($parameter['driverId'])){
            return $this->toError(500, '人员不属于系统');
        }
        // 获取当前用户机构区域信息
        $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);

        $parameter['provinceId'] = $area['provinceId'] ?? 0;
        $parameter['cityId'] = $area['cityId'] ?? 0;
        $parameter['areaId'] = $area['areaId'] ?? 0;

        $parameter['dataSource'] = 1;
        $parameter['sourcePerson'] = $this->authed->userId;
        $parameter['peccancyTime'] = time();
        // 增加违章记录
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15007',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败'.$result['msg']);
        }
        $ids = $result['content']['ids'];
        $driverData = new DriverData();
        // 查骑手信息
        if (!isset($driver)){
            $driver = Drivers::arrFindFirst(['id' => $parameter['driverId']]);
        }
        // 骑手存在才进行通知、扣分
        if ($driver){
            $SMSSwitch = $driverData->getCityPeccancySMSSwitchByCityId($parameter['cityId']);
            // 违章单id对应违章类型
            $peccancyTypeRes =  $this->modelsManager->createBuilder()
                ->addfrom('app\models\service\PeccancyType','pt')
                ->join('app\models\service\PeccancyTypeValue', 'ptv.value=pt.type','ptv')
                ->where('pt.peccancy_id IN ({idList:array})', [
                    'idList' => $ids,
                ])
                ->columns('pt.peccancy_id, ptv.description')
                ->getQuery()
                ->execute()
                ->toArray();
            foreach ($peccancyTypeRes as $peccancyType){
                // 发送违章通知
                if ($SMSSwitch){
                    $driverData->SendPeccancyNotification($driver->phone, $parameter['vehicleLicence'], date('Y-m-d H:i:s', $parameter['peccancyTime']), $peccancyType['description']);
                }
                // 扣分
                $driverData->PeccancyDeductionScore($peccancyType['peccancy_id']);
            }
        }
        // 成功返回
        return $this->toSuccess([
            'ids' => $ids,
        ]);
    }

    // 开单记录
    public function RecordlistAction()
    {
        // 开单人
        $parameter['sourcePerson'] = $this->authed->userId;
        // 七天内
        $parameter['createAtStart'] = time() - (3600*24*7);
        $parameter['pageSize'] = $_GET['pageSize'] ?? 20;
        $parameter['pageNum'] = $_GET['pageNum'] ?? 1;
        // 查询车辆违规列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15006',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 车牌获取车架号
        $vehicleLicenceS = [];
        foreach ($list as $k =>$item){
            if (!empty($item['vehicleLicence'])){
                $vehicleLicenceS[] = $item['vehicleLicence'];
            }
        }
        $plateNumVin = [];
        if (count($vehicleLicenceS) > 0){
            $vehicles = Vehicle::arrFind([
                'plate_num' => ['IN', $vehicleLicenceS]
            ])->toArray();
            foreach ($vehicles as $vehicle){
                $plateNumVin[$vehicle['plate_num']] = $vehicle['vin'];
            }
        }
        foreach ($list as $k => $v){
            $list[$k]['vin'] = $plateNumVin[$v['vehicleLicence']] ?? '';
            // 处理一对多数据为数组
            $list[$k]['types'] = explode('|', $v['types']);
            $list[$k]['picPaths'] = explode('|', $v['picPaths']);
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 作废
    public function AbolitionAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['reason']) || empty($request['reason'])){
            return $this->toError(500, '请输入作废原因');
        }
        // 查询违章单详情
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15006',
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        // 异常返回
        if (200 != $result['statusCode']) {
            return $this->toError(500, '服务异常'.$result['msg']);
        }
        if (1 != count($result['content']['data'])){
            return $this->toError(500, '未查到有效单据');
        }
        $order = $result['content']['data'][0];
        if ($this->authed->userId != $order['sourcePerson']){
            return $this->toError(500, '不可处理非自己的开单');
        }
        if (0 != $order['status']){
            return $this->toError(500, '不可重复处理违章单');
        }
        // 作废违章单
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15008',
            'parameter' => [
                'peccancyId' => $id,
                'userId' => $this->authed->userId,
                'processType' => 4,
                'reason' => $request['reason']
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败：'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess([
            'id' => $result['content']['id'],
        ]);
    }

    // 核实骑手信息
    public function CheckDriverAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'identify' => '请填写骑手身份证号',
            'expressInsId' => '快递公司异常',
        ];
        $parameter = $this->getArrPars($fields, $request);
        // TODO: 可能有多条骑手身份证相同，优先查询快递公司下骑手
        // 查询快递公司骑手关系
        // leftjoin内建条件查首条会导致同身份证多骑手时出现惰性查询
        $driverExpress = $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Drivers', 'd')
            ->where('d.identify = :identify:', [
                'identify' => $parameter['identify']
            ])
            ->join('app\models\dispatch\RegionDrivers',
                'rd.driver_id = d.id',
                'rd')
            ->andWhere('rd.ins_id = :expressInsId:', [
                'expressInsId' => $parameter['expressInsId']
            ])
            ->columns('d.id, d.real_name, rd.ins_id')
            ->getQuery()
            ->getSingleResult();
        // 快递公司关系核实
        $data['expressRelation'] = $driverExpress ? true : false;
        if ($driverExpress){
            $data['realName'] = $driverExpress->real_name;
        }else{
            // 快递公司内未查询到，全部骑手内查询
            $driver = Drivers::arrFindFirst([
                'identify' => $parameter['identify']
            ]);
            $data['realName'] = $driver->real_name ?? '';
        }
        $driverId = $driverExpress->id ?? $driver->id ?? false;
        if (false == $driverId){
            return $this->toError(500, '人员不存在');
        }
        // 有效驾照
        $data['hasLicence'] = false;
        if ($driverId){
            // 获取区域
            $area = (new UserData())->getAreaByInsId($this->authed->insId, $this->authed->userType);
            if (empty($area['cityId'])){
                return $this->toError(500, '非法操作，非市级单位');
            }
            // 查询区域快递协会
            $association = Association::arrFindFirst([
                'province_id' => $area['provinceId'],
                'city_id' => $area['cityId'],
            ]);
            $associationInsId = $association->ins_id ?? 0;
            // 查询骑手驾照分
            $licence = DriverLicence::arrFindFirst([
                'driver_id'=>$driverId,
                'ins_id' => $associationInsId,
                'valid_starttime' => ['<', time()],
                'valid_endtime' => ['>', time()],
            ]);
            if ($licence){
                $data['hasLicence'] = true;
            }
        }
        /*
        // 查询骑手有效驾照
        $dls = $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Drivers', 'd')
            ->where('d.identify = :identify:', [
                'identify' => $parameter['identify']
            ])
            ->join('app\models\dispatch\DriverLicence', 'dl.driver_id = d.id', 'dl')
            ->andWhere('dl.valid_starttime < :time: AND dl.valid_endtime > :time:', [
                'time' => time()
            ])
            ->columns('dl.*')
            ->getQuery()
            ->execute()
            ->toArray();
        // TODO:获取当前执法人员归属区域,对比驾照区域
        // $area = (new UserData())->getAreaByInsId($this->authed->insId);
        foreach ($dls as $dl) {
            // TODO:判断区域
            if (true) {
                $data['hasLicence'] = true;
                break;
            }
        }*/
        return $this->toSuccess($data);
    }

    public function GeneralAction()
    {
        // 是否有文件上传
        if (!$this->request->hasFiles()) {
            return $this->toError(500,'未收到文件');
        }
        // 获取文件
        $file = $this->request->getUploadedFiles()[0];
        if (0==$file->getSize() || $file->getSize()/1024 > 1536){
            return $this->toError(500,'文件大小不支持，请选择1.5M以内的文件');
        }
        // 将文件做base64编码
        $baseStr = base64_encode(file_get_contents($file->getTempName()));
        //发送图片保存到文件服务
        $path = '';
        $result = $this->uploadPhoto($file->getName(),$baseStr);
        if ($result['code'] != true) {
            return $this->toError(500,$result['msg']);
        } else {
            $path = $result['content'];
        }

        $result = $this->ocrData->GENERAL($this->config->alicloudapi->general_appcode,$baseStr);

        if ($result == false) {
            return $this->toSuccess(['code' => '识别失败','path' => '']);
        } else {
            return $this->toSuccess(['code' => $result,'path' => $path]);
        }
    }

    private function uploadPhoto($fileName,$baseFile) {
        // 传输存储文件
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10030",
            'parameter' => [
                'suffiex' => pathinfo($fileName, PATHINFO_EXTENSION),
                'fileStr' => $baseFile,
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return ['code' => false,'msg' => '上传文件失败!','content' => ''];
        }
        return ['code' => true, 'content' => 'http://'.$result['content']['address'],'msg' => ''];
    }

    // 附近车辆
    public function NearbyVehicleAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'lng' => '经纬度异常',
            'lat' => '经纬度异常',
            // 附近距离 单位km
            'distance' => [
                'def' => 5,
            ],
        ];
        $parameter = $this->getArrPars($fields, $request);
        // 获取附近车辆
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 60022,
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (200 != $result['statusCode']) {
            return $this->toError(500, $result['msg']);
        }
        $data = $result['content']['data'];
        return $this->toSuccess($data);
    }


    // 附近车辆详情
    public function VehicleDetailAction($id)
    {
        // 获取附近车辆
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 60023,
            'parameter' => [
                'id' => (int)$id
            ]
        ],"post");
        // 失败返回
        if (200 != $result['statusCode']) {
            return $this->toError(500, $result['msg']);
        }
        $data = $result['content']['data'];
        return $this->toSuccess($data);
    }
}

<?php
namespace app\modules\postoffice;

use app\common\errors\DataException;
use app\common\library\ReturnCodeService;
use app\common\library\ZuulApiService;
use app\models\dispatch\Region;
use app\models\service\RegionVehicle;
use app\models\service\Secure;
use app\models\service\SecureInfo;
use app\models\service\Vehicle;
use app\models\users\User;
use app\modules\BaseController;
use app\services\data\UserData;

//保单模块
class WarrantyController extends BaseController
{
    /**
     * 保单列表
     * code：40003
     */
    public function listAction()
    {
//        $fields = [
//            // 保单编号
//            'secureNum' => 0,
//            // 快递公司id
//            'expressId' => 0,
//            // 开始时间
//            'startTime' => 0,
//            // 截止时间
//            'endTime' => 0,
//            // 车架号
//            'vin' => 0,
//            'pageNum' => [
//                'def' => 1,
//            ],
//            'pageSize' => [
//                'def' => 20,
//            ],
//        ];
//        $parameter = $this->getArrPars($fields, $_GET);
//        if (!$parameter){
//            return;
//        }
        $parameter = $_GET;
        $parameter['pageNum'] = $parameter['pageNum'] ?? 1;
        $parameter['pageSize'] = $parameter['pageSize'] ?? 20;
        if (!empty($parameter['startTime'])){
            $parameter['startTime'] = strtotime($parameter['startTime']);
        }
        if (!empty($parameter['endTime'])){
            $parameter['endTime'] = strtotime($parameter['endTime']);
        }
        // 保险公司ID
        $parameter['secureId'] = $this->authed->insId;
        //调用微服务接口获取数据
        $params = ["code" => "40003","parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->search,$params,"post");
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'未找到有效数据');
        }
        $licenselist = $result['content']['data'];

        $expressIds = [];
        foreach ($licenselist as $license) {
            $expressIds[] = $license['expressId'];
        }
        $expressNames = $this->userData->getCompanyNamesByInsIds($expressIds);
        $list = [];
        foreach ($licenselist as $license) {
            // 快递公司
            $license['expressName'] = $expressNames[$license['expressId']] ?? '----';
            $list[] = $this->handleBackTimestamp($license);
        }
        $meta = $result['content']['pageInfo'] ?? [];
        return $this->toSuccess($list, $meta);
    }

    /**
     * 查询保单
     * code：
     */
    public function oneAction($id)
    {
    }


    /**
     * 新增保单
     * code：10005
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'secureNum' => '请填写保单编号',
            // 'expressId' => '请选择快递公司',
            'vehicleId' => '请选择待保车辆',
            'startTime' => '请选择保单开始时间',
            'endTime' => '请选择保单结束时间',
            // 服务电话
            'serviceLine' => 0,
        ];
        $parameter = $this->getArrPars($fields, $request);
        // 保险公司id
        $parameter['secureId'] = $this->authed->insId;
        $parameter['startTime'] = strtotime($parameter['startTime']);
        $parameter['endTime'] = strtotime($parameter['endTime']);
        $parameter['createAt'] = time();
        $parameter['updateAt'] = time();
        // 查询车辆的快递公司insId
        $RV = RegionVehicle::findFirst([
            'vehicle_id = :vehicle_id:',
            'bind' => [
                'vehicle_id' => $parameter['vehicleId'],
            ],
        ]);
        if (false===$RV){
            return $this->toError(500, '车辆未找到快递公司关系');
        }
        $parameter['expressId'] = $RV->ins_id;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '10005',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 没有服务热线无需维护
        if (empty($request['serviceLine'])){
            return $this->toSuccess();
        }
        $bol = SecureInfo::upSecureInfo($this->authed->insId, $parameter['secureNum'], ['service_line'=>$request['serviceLine']]);
        if (false == $bol){
            return $this->toError(500, '服务热线维护失败，请稍后尝试编辑');
        }
        return $this->toSuccess();
    }

    // 维护SecureInfo



    /**
     * 修改保单
     * code：
     */
    public function UpdateAction($id)
    {
        /*
        // 先查询保单信息
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '10008',
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200' || !isset($result['content']['secureDOS'][0])) {
            return $this->toError(500,'更新失败');
        }
        $vehicleId = $result['content']['secureDOS'][0]['vehicleId'];
         */
        // 获取更新参数
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            // 保单编号
            'secureNum' => '请填写保单编号',
            // 快递公司ID
            // 'expressId' => '请选择快递公司',
            // 车辆ID
            'vehicleId' => '请选择车辆',
            // 保单开始时间
            'startTime' => 0,
            // 保单结束时间
            'endTime' => 0,
            // 服务电话
            'serviceLine' => 0,
        ];
        $parameter = $this->getArrPars($fields, $request,true);
        if (false === $parameter){
            return;
        }
        // 保险公司id
        $parameter['secureId'] = $this->authed->insId;
        // 保单id
        $parameter['id'] = $id;
        $parameter['updateAt'] = time();
        // 查询快递公司
        $RV = RegionVehicle::arrFindFirst([
            'vehicle_id' => $parameter['vehicleId'],
        ]);
        if (false == $RV){
            return $this->toError(500, '车辆不属于快递公司');
        }
        $parameter['expressId'] = $RV->ins_id;
        // 处理时间格式
        if (isset($parameter['startTime'])){
            $parameter['startTime'] = strtotime($parameter['startTime']);
            if (false === $parameter['startTime']){
                return $this->toError(500,'保单开始时间有误');
            }
        }
        if (isset($parameter['endTime'])){
            $parameter['endTime'] = strtotime($parameter['endTime']);
            if (false === $parameter['endTime']){
                return $this->toError(500,'保单截止时间有误');
            }
        }
        //调用微服务接口更新保单
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '10007',
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500,'更新失败');
        }
        // 没有服务热线无需维护
        if (empty($request['serviceLine'])){
            return $this->toSuccess();
        }
        $bol = SecureInfo::upSecureInfo($this->authed->insId, $parameter['secureNum'], ['service_line'=>$request['serviceLine']]);
        if (false == $bol){
            return $this->toError(500, '服务热线维护失败，请稍后尝试编辑');
        }
        return $this->toSuccess();
    }

    /**
     * 删除保单
     * code：10006
     */
    public function DeleteAction($id)
    {
        //调用微服务接口删除保单
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '10006',
            'parameter' => [
                'idList' => [(int)$id]
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }

    // 批量删除保单
    public function BatchDelAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (empty($request['idList'])){
            return $this->toError(500, '无待删除保单');
        }
        $idList = $request['idList'];
        foreach ($idList as $k => $id){
            $idList[$k] = (int)$id;
        }
        //调用微服务接口删除保单
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '10006',
            'parameter' => [
                'idList' => $idList
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }


    // 保险公司获取可保车辆
    public function SecureVehicleAction(){
        // 参数提取
        $fields = [
            'expressInsId' => 0,
            'bianhao' => 0,
            'vin' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (isset($parameter['expressInsId'])){
            $insIds = [$parameter['expressInsId']];
        }else{
            // 查询保险公司关联的快递公司
            $insIds = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
            if (false === $insIds){
                return $this->toEmptyList();
            }
        }
        $insIds = array_values(array_unique($insIds));
        // 没有可用查询，直接返回
        if (empty($insIds)){
            return $this->toEmptyList();
        }
        // 查询快递公司下的车辆
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\RegionVehicle','rv')
            ->where('rv.ins_id IN ({insIds:array})', ['insIds'=>$insIds])
            ->join('app\models\service\Vehicle', 'rv.vehicle_id = v.id','v');
//            ->andWhere('v.has_secure = :has_secure:', ['has_secure'=>Vehicle::NOT_HAS_SECURE]);
        if (isset($parameter['bianhao']) && !empty($parameter['bianhao'])){
            $model = $model->andWhere('v.bianhao like :bianhao:', ['bianhao'=>'%'.$parameter['bianhao'].'%']);
        }
        if (!empty($parameter['vin'])){
            $model = $model->andWhere('v.vin like :vin:', ['vin'=>'%'.$parameter['vin'].'%']);
        }
        $vehiclelist = $model->columns('v.id, v.bianhao, v.vin, v.product_id AS productId, v.product_sku_relation_id AS productSkuRelationId, v.vehicle_model_id AS vehicleModelId')
            ->getQuery()
            ->execute()
            ->toArray();
        return $this->toSuccess($vehiclelist);
    }

    /** 车辆设为待保
     * @param $vehicleId
     * @return bool
     */
    public function UnProtectVehicle($vehicleId)
    {
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => '60014',
            'parameter' => [
                'id' => $vehicleId,
                'hasSecure' => 1
            ]
        ],"post");
        if (200!=$result['statusCode']){
            return false;
        }
        return true;
    }

    /** 车辆设为已保
     * @param $vehicleId
     * @return bool
     */
    public function ProtectVehicle($vehicleId)
    {
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => '60014',
            'parameter' => [
                'id' => $vehicleId,
                'hasSecure' => 2
            ]
        ],"post");
        if (200!=$result['statusCode']){
            return false;
        }
        return true;
    }


    public function LeadInAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['insId'] = $this->authed->insId;
        $result = $this->userData->postCommon($json,$this->Zuul->biz,10031);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

    public function SelectexpressAction()
    {
        // 获取当前机构关联的快递公司
        $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false!==$expressIdList && empty($expressIdList)){
            return $this->toEmptyList();
        }
        $model =  $this->modelsManager->createBuilder()
            // 查询快递公司
            ->addfrom('app\models\users\Institution','i')
            ->andWhere('i.type_id = 7');
        if (is_array($expressIdList)){
            $model = $model->andWhere('i.id IN ({expressIdList:array})', [
                'expressIdList' => $expressIdList,
            ]);
        }
        // 关联主系统快递公司 && 管理者为启用状态
        $model = $model->join('app\models\users\UserInstitution', 'ui.ins_id = i.id AND ui.is_admin = 1','ui')
                    ->join('app\models\users\User', 'u.id = ui.user_id AND u.user_status = 1','u')
                    ->join('app\models\users\Company', 'c.ins_id = i.id','c');
        if (isset($_GET['companyName'])&&!empty($_GET['companyName'])){
            $model = $model->andWhere('c.company_name like :companyName:', ['companyName'=>'%'.$_GET['companyName'].'%']);
        }
        $express = $model->columns('i.id AS insId, c.company_name AS companyName')
            ->getQuery()
            ->execute()
            ->toArray();
        return $this->toSuccess($express);
    }

    public function ExportVehicleAction()
    {
        // 调用微导出模版
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 10032,
            'parameter' => [
                'insId' => $this->authed->insId
            ]
        ],"post");
        //结果处理返回
        if (200 != $result['statusCode']) {
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        if (empty($result['content'])){
            return $this->toError(500, $result['msg']);
        }
        $url = 'https://' . $result['content']['data']['url'];
        return $this->toSuccess([
            'url' => $url
        ]);
    }

    // 添加保单附件
    public function AddSecureFileAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        if (empty($request['secureFileUrl'])){
            return $this->toError(500, '请先上传文件');
        }
        // 查询保单信息
        $secure = Secure::arrFindFirst([
            'id' => $id,
        ]);
        if (false == $secure){
            return $this->toError(500,'未查询到保单信息');
        }
        $secure = $secure->toArray();

        $insId = $this->authed->insId;
        if ($secure['secure_id'] != $insId){
            return $this->toError(500, '不可操作非当前机构保单');
        }
        $secureInfo = SecureInfo::arrFindFirst([
            'secure_ins_id' => $insId,
            'secure_num' => $secure['secure_num'],
        ]);
        if (false == $secureInfo){
            $secureInfo= new SecureInfo();
        }
        $bol = $secureInfo->save([
            'secure_ins_id' => $insId,
            'secure_num' => $secure['secure_num'],
            'secure_file' => $request['secureFileUrl'],
        ]);
        if (false == $bol){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }

}

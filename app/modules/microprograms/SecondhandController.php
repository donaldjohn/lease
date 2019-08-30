<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: SecondhandController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\microprograms;


use app\models\service\DeviceModel;
use app\models\service\VehicleSecondhand;
use app\models\service\Vehicle;
use app\models\service\Qrcode;
use app\models\service\VehicleSource;
use app\modules\BaseController;

/**
 * Class SecondhandController
 * @package app\modules\microprograms
 * 车辆后装模块
 */
class SecondhandController extends BaseController
{
    /**
     * 获取用户上传历史数据
     */
    public function ListAction()
    {
        /**
         * 获取url参数
         */
        $indexText = $this->request->getQuery('indexText','string',null,true);
        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);

        $app = $this->VehicleData->getVehiclePageByUserId($this->authed->userId,$pageNum,$pageSize,$indexText);

        $sum = VehicleSecondhand::count(['conditions' => 'user_id = :user_id:','bind' =>['user_id' => $this->authed->userId]]);
        return  $this->toSuccess(['page' => $app['data'],'sum' => $sum],$app['meta']);
    }



    /**
     * 上传数据
     */
    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        /**
         * 数据插入vehicle 然后插入vehicle_secondhand
         */
        //验证数据完整性
        if (!isset($json['udid'])) {
            return $this->toError(500,'数据不完整!缺少设备号!');
        }
        if (!isset($json['device_model_id'])) {
            return $this->toError(500,'数据不完整!缺少设备厂商!');
        }
        if (!isset($json['bianhao'])) {
            return $this->toError(500,'数据不完整!缺少得威编号!');
        }
        if (!isset($json['vin'])) {
            return $this->toError(500, '数据不完整!缺少车架号!');
        }
        if (!isset($json['plate_num'])) {
            return $this->toError(500, '数据不完整!缺少车牌号!');
        }
//        if (!isset($json['product_id'])) {
//            return $this->toError(500, '数据不完整!缺少商品ID!');
//        }
//        if (!isset($json['product_sku_relation_id'])) {
//            return $this->toError(500, '数据不完整!缺少商品规格ID!');
//        }
        if (!isset($json['vehicle_model_id'])) {
            return $this->toError(500, '数据不完整!缺少车辆商品ID!');
        }

        $this->dw_service->begin();
        $vehicle = new Vehicle();
        $vehicle->assign($json);
        $time1 = time();
        $vehicle->pull_time = $time1;
        $vehicle->mileage_time = $time1;
        $vehicle->data_source = 2;
        $vehicle->is_lock=2;
        /**
         * 查询数据是否重复
         */
        $vehicleCheck = Vehicle::findFirst(['conditions' => 'udid = :udid: and device_model_id = :device_model_id:','bind' =>['udid' => $json['udid'],'device_model_id' => $json['vehicle_model_id']]]);
        if ($vehicleCheck) {
            return $this->toError(500, '数据重复!存在相同设备号!');
        }
        //如果udid在vehicle里面已经存在了，就判断此udid已被使用
        $vehicle2 = Vehicle::findFirst(['conditions' => 'udid = :udid:','bind' =>['udid' => $json['udid']]]);
        if ($vehicle2) {
            return $this->toSuccess(['status' => false],[],200,"数据重复!存在相同设备号!");
        }
        $vehicleCheck1 = Vehicle::findFirst(['conditions' => 'vin = :vin: and vehicle_model_id = :vehicle_model_id:','bind' =>['vin' => $json['vin'],'vehicle_model_id' => $json['vehicle_model_id']]]);
        if ($vehicleCheck1) {
            return $this->toError(500, '数据重复!存在相同车型车架号!');
        }
        $vehicleCheck2 = Vehicle::findFirst(['conditions' => 'bianhao = :bianhao:','bind' =>['bianhao' => $json['bianhao']]]);
        if ($vehicleCheck2) {
            return $this->toError(500, '数据重复!存在相同得威编号!');
        }

        $plateNumCheck = Vehicle::findFirst(['conditions' => 'plate_num = :plate_num:','bind' =>['plate_num' => $json['plate_num']]]);
        if ($plateNumCheck) {
            return $this->toError(500, $json['plate_num'].'车牌号已被车辆绑定，请联系管理员核对');
        }

        if ($vehicle->save() == false) {
            $this->dw_service->rollback();
            return $this->toError(500,$vehicle->getMessages()[0]->getMessage());
        }
        $secondHand = new VehicleSecondhand();
        $secondHand->setUserId($this->authed->userId);
        $secondHand->setRealName($this->authed->userName);
        $secondHand->setVehicleId($vehicle->id);
        $secondHand->setCreateTime(time());
        if ($secondHand->save() == false) {
            $this->dw_service->rollback();
            return $this->toError(500,$secondHand->getMessages()[0]->getMessage());
        }
        /**
         * 更新二维码表状态
         */
        $qrCode = Qrcode::findFirst(['conditions' => 'bianhao = :bianhao:','bind' => ['bianhao' => $vehicle->bianhao]]);
        if ($qrCode == false) {
            $this->dw_service->rollback();
            return $this->toError(500,"编号不存在！");
        }
        $qrCode->status = 3;
        $qrCode->activation_time = time();
        if($qrCode->update() == false) {
            $this->dw_service->rollback();
            return $this->toError(500,$qrCode->getMessages()[0]->getMessage());
        }
        /**
         * 根据设备供应商insId =》 获取dw_vehicle_source source_type
         * TODO 严重有问题 新增供应商就需要修改代码
         */
        $deviceModel = DeviceModel::findFirst($json['device_model_id']);
        if(!$deviceModel) {
            $this->dw_service->rollback();
            return $this->toError(500,"设备厂商不存在！");
        }
        $typeId = $this->VehicleData->getTypeId($deviceModel->ins_id);
        $vehicleSource = new VehicleSource();
        $vehicleSource->vehicle_id = $vehicle->id;
        $vehicleSource->source_type = $typeId;
        $vehicleSource->create_time = time();
        if (!$vehicleSource->save()) {
            $this->dw_service->rollback();
            $this->logger->error($qrCode->getMessages()[0]->getMessage());
            return $this->toError(500,"车辆用途保存失败");
        }

        $this->dw_service->commit();
        return $this->toSuccess(true);
    }




    /**
     * 上传数据  福建项目
     * 不是新增 直接修改   由于数据已经存在（二维码和车架号）
     */
    public function CreateFJAction()
    {
        $json = $this->request->getJsonRawBody(true);
        /**
         * 数据插入vehicle 然后插入vehicle_secondhand
         */
        //验证数据完整性
        if (!isset($json['udid'])) {
            return $this->toError(500,'数据不完整!缺少设备号!');
        }
        if (!isset($json['device_model_id'])) {
            return $this->toError(500,'数据不完整!缺少设备厂商!');
        }
        if (!isset($json['bianhao'])) {
            return $this->toError(500,'数据不完整!缺少得威编号!');
        }
        if (!isset($json['vin'])) {
            return $this->toError(500, '数据不完整!缺少车架号!');
        }
        if (!isset($json['plate_num'])) {
            return $this->toError(500, '数据不完整!缺少车牌号!');
        }
//        if (!isset($json['product_id'])) {
//            return $this->toError(500, '数据不完整!缺少商品ID!');
//        }
//        if (!isset($json['product_sku_relation_id'])) {
//            return $this->toError(500, '数据不完整!缺少商品规格ID!');
//        }
        if (!isset($json['vehicle_model_id'])) {
            return $this->toError(500, '数据不完整!缺少车辆商品ID!');
        }

        $this->dw_service->begin();
//        $vehicle = new Vehicle();
//        $vehicle->assign($json);
//        $time1 = time();
//        $vehicle->pull_time = $time1;
//        $vehicle->mileage_time = $time1;
//        $vehicle->data_source = 2;
        /**
         * 查询数据是否重复
         */
        $vehicleCheck = Vehicle::findFirst(['conditions' => 'udid = :udid: and device_model_id = :device_model_id:','bind' =>['udid' => $json['udid'],'device_model_id' => $json['device_model_id']]]);
        if ($vehicleCheck) {
            return $this->toError(500, '数据重复!存在相同型号设备号!');
        }
        /**
         * 查询 车架号和得威二维码是否已经存在
         */
        $vehicle = Vehicle::findFirst(['conditions' => 'vin = :vin: and vehicle_model_id = :vehicle_model_id: and bianhao = :bianhao:','bind' =>['bianhao' => $json['bianhao'],'vin' => $json['vin'],'vehicle_model_id' => $json['vehicle_model_id']]]);
        if (!$vehicle) {
            return $this->toError(500, '数据不存在!不存在相同车型车架号和二维码编号!');
        }
//        $vehicleCheck2 = Vehicle::findFirst(['conditions' => 'bianhao = :bianhao:','bind' =>['bianhao' => $json['bianhao']]]);
//        if ($vehicleCheck2) {
//            return $this->toError(500, '数据重复!存在相同得威编号!');
//        }
        $time1 = time();
        $vehicle->assign($json);
        $vehicle->pull_time = $time1;
        $vehicle->mileage_time = $time1;
        if ($vehicle->update() == false) {
            return $this->toError(500,$vehicle->getMessages()[0]->getMessage());
        }
        $VB = VehicleSecondhand::findFirst(['conditions' => 'vehicle_id = :vehicleId:','bind' => ['vehicleId' => $vehicle->id]]);
        if ($VB) {
            $VB->setUserId($this->authed->userId);
            $VB->setRealName($this->authed->userName);
            $VB->setVehicleId($vehicle->id);
            $VB->setCreateTime(time());
            if ($VB->update() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$VB->getMessages()[0]->getMessage());
            }
        } else {
            $secondHand = new VehicleSecondhand();
            $secondHand->setUserId($this->authed->userId);
            $secondHand->setRealName($this->authed->userName);
            $secondHand->setVehicleId($vehicle->id);
            $secondHand->setCreateTime(time());
            if ($secondHand->save() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$secondHand->getMessages()[0]->getMessage());
            }
        }
        /**
         * 更新二维码表状态
         */
        $qrCode = Qrcode::findFirst(['conditions' => 'bianhao = :bianhao:','bind' => ['bianhao' => $vehicle->bianhao]]);
        if ($qrCode == false) {
            $this->dw_service->rollback();
            return $this->toError(500,"编号不存在！");
        }
        $qrCode->status = 3;
        $qrCode->activation_time = time();
        if($qrCode->update() == false) {
            $this->dw_service->rollback();
            return $this->toError(500,$qrCode->getMessages()[0]->getMessage());
        }
        /**
         * 根据设备供应商insId =》 获取dw_vehicle_source source_type
         * TODO 严重有问题 新增供应商就需要修改代码
         */
//        $deviceModel = DeviceModel::findFirst($json['device_model_id']);
//        if(!$deviceModel) {
//            $this->dw_service->rollback();
//            return $this->toError(500,"设备厂商不存在！");
//        }
//        $typeId = $this->VehicleData->getTypeId($deviceModel->ins_id);
//        $vehicleSource = new VehicleSource();
//        $vehicleSource->vehicle_id = $vehicle->id;
//        $vehicleSource->source_type = $typeId;
//        $vehicleSource->create_time = time();
//        if (!$vehicleSource->save()) {
//            $this->dw_service->rollback();
//            $this->logger->error($qrCode->getMessages()[0]->getMessage());
//            return $this->toError(500,"车辆用途保存失败");
//        }
        /**
         * 通知张建清 api
         */
        $parameter = [
            'vehicleId' => $vehicle->id,
            'vehicleNo' => $vehicle->plate_num,
            'platformId' =>3301000010,
            'producerId' => substr($vehicle->udid,-11),
            'terminalModelType' => $vehicle->vin,
            'terminalID' => 'ET500',
            'terminalSIMCode' => 13500000000
        ];
        $parameter1 = [
            'vehicleNo' => $vehicle->plate_num,
            'vehicleId' => $vehicle->id,
            'VEHICLE_NATIONALIT' =>330001,
            'VIN' => $vehicle->plate_num //车牌号
        ];
        $this->CallService('vehicle','60043',$parameter,false);
        $this->CallService('vehicle','60044',$parameter1,false);
        $this->dw_service->commit();
        return $this->toSuccess(true);
    }



    /**
     * 通用图片识别
     */
    public function OcrGeneralAction()
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
            return $this->toSuccess(['code' => '识别失败','path' => $path]);
        } else {
            return $this->toSuccess(['code' => $result,'path' => $path]);
        }

    }

    /**
     * 车架号识别
     */
    public function OcrVinAction()
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

        $result = $this->ocrData->Vin($this->config->alicloudapi->vin_appcode,$baseStr);

        if ($result == false) {
            return $this->toSuccess(['code' => '识别失败','path' => $path]);
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

}
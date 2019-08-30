<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: MicroController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\multicodetask;


use app\models\service\DeviceModel;
use app\models\service\MulticodeTask;
use app\models\service\MulticodeTaskDetail;
use app\models\service\MulticodeTaskUser;
use app\models\service\Qrcode;
use app\models\service\Vehicle;
use app\models\service\VehicleSource;
use app\models\users\User;
use app\modules\BaseController;
use Phalcon\Validation;

class MicroController extends BaseController
{
    public function ListAction()
    {
        //TODO:: hack
        //判断用户状态;
        $user = User::findFirst($this->authed->userId);
        if ($user->user_status != 1) {
            return $this->toError(500,'当前用户状态异常!');
        }
        /**
         * 获取参数
         */
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);
        $done = $this->request->getQuery('done','int',null,true) > 0 ? true : false;
        /**
         * 查询分页数据
         */
        $multiCodeTaks = $this->taskData->getTaskPage($pageNum,$pageSize,null,$done,null,$this->authed->userId);
        return $this->toSuccess($multiCodeTaks['data'],$multiCodeTaks['meta']);
    }


    public function OneAction($id)
    {

        $taskCheck = MulticodeTaskUser::findFirst(['task_id' => $id,'user_id' => $this->authed->userId]);
        if ($taskCheck == false) {
            return $this->toError(500,'非法操作!,当前任务不属于该用户!');
        }
        /**
         * 获取参数
         */
        $indexText = $this->request->getQuery('indexText','string',null);
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);
        /**
         * 获取task详情
         *
         */
        $task = $this->taskData->getTask($id);
        //$user = User::findFirst($task->user_id);
        //$task->user_name = $user->getUserName();
        $user = $this->userData->getUserById($task->user_id);
        $task->real_name = $user['realName'];
        $taskDetail = $this->taskData->getTaskDetailPage($id,$pageNum,$pageSize,$indexText);

        $result = ['task_info' => $task, 'task_detail' => $taskDetail['data']];
        return $this->toSuccess($result,$taskDetail['meta']);

    }


    //批量添加
    public function CreateAction($id)
    {
        //判断用户状态;
        $user = User::findFirst($this->authed->userId);
        if ($user->user_status != 1) {
            return $this->toError(500,'当前用户状态异常!');
        }

        $deviceModels = $this->VehicleData->getDeviceModel();
        $this->dw_service->begin();
        try{
            //生成sql
            $task = MulticodeTask::findFirst($id);
            if ($task == false) {
                return $this->toError(500,'不存在当前任务');
            }

            //查看任务状态
            if ($task->getTaskStatus() != 3) {
                return $this->toError(500,'当前任务状态不为操作中!');
            }

//            $taskCheck = MulticodeTaskUser::findFirst(['task_id = :task_id: and user_id = :user_id:','bind' => ['task_id' => $id,'user_id' => $this->authed->userId]]);
//            if ($taskCheck == false) {
//                return $this->toError(500,'非法操作!,当前任务不属于该用户!');
//            }

            if ($task->getTaskStatus() == 4) {
                $this->dw_service->commit();
                return $this->toError(500,'当前任务状态已完成!');
            }

            $json = $this->request->getJsonRawBody(true);

            if (!isset($json['multicode'])) {
                return $this->toError(500,'上传数据格式不正确!');
            }
            $num = $task->getTaskCompletedNum() + count($json['multicode']);

            if ($num > $task->getTaskNum()) {
                return $this->toError(500,'上传数据格式不正确!');
            }

            $result = $this->taskData->setInsetTaskDetailData($task,$json);
            if ($result == false) {
                return $this->toError(500,'上传数据格式不正确!');
            }
            $taskDetail = new MulticodeTaskDetail();
            $taskDetailSql = $taskDetail->batch_insert($result);
            //插入taskdetail
            $this->dw_service->query($taskDetailSql);

            //更新至vehicle
            $result = $this->taskData->setInsetVehicleData($task,$json);
            if ($result == false) {
                $this->dw_service->rollback();
                return $this->toError(500,'上传数据格式不正确或出现重复数据');
            }
//            $vehicle = new Vehicle();
//            $vehicleSql = $vehicle->batch_insert($result);
//            $this->dw_service->query($vehicleSql);
            //更新至vehicle
            //插入 vehicle_source
            foreach ($result as $item) {
                $vehicle = new Vehicle();
                $vehicle->assign($item);
                if (!$vehicle->save()) {
                    $this->dw_service->rollback();
                    return $this->toError(500,'服务失败，请重试！');
                }
                $vehicleSource = new VehicleSource();
                $vehicleSource->vehicle_id = $vehicle->id;
                $vehicleSource->source_type = $this->VehicleData->getTypeId($deviceModels[$vehicle->device_model_id]);
                $vehicleSource->create_time = time();
                if (!$vehicleSource->save()) {
                    $this->dw_service->rollback();
                    return $this->toError(500,'服务失败，请重试！');
                }
            }


            /**
             * 更新qrcode状态
             */
            $bianhaos = [];
            foreach($result as $item) {
             $bianhaos[] = $item['bianhao'];
            }
            $phql = "update app\\models\\service\\Qrcode set status =3,activation_time = {datetime} where bianhao in ({bianhaos:array})";
            $robots = $this->modelsManager->executeQuery(
                $phql,
                ['datetime' => time(),'bianhaos' => $bianhaos]
            );

            /**
             * 上传成功,更新完成数量
             */
            $num = $task->getTaskCompletedNum() + count($json['multicode']);
            //更新完成数量
            $task->setTaskCompletedNum($num);

            if ($task->getTaskCompletedNum() == $task->getTaskNum()) {
                $task->setTaskStatus(4); //已完成
            }

            if ($task->update() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,'保存任务失败!');
            }
            /**
             * 同步老系统
             */
            $pushData = [];
            foreach($json['multicode'] as $item) {
                $data = [];
                if (isset($item['qrcode'])) {
                    $data['bianhao'] = $item['qrcode'];
                }
                if (isset($item['udid'])) {
                    $data['udid'] = $item['udid'];
                }
                if (isset($item['vin'])) {
                    $data['vin'] = $item['vin'];
                }
                if (isset($item['plate_num'])) {
                    $data['plate_num'] = $item['plate_num'];
                }
                $pushData[] = $data;
            }
            $postData = array();
            $postData['data'] = $pushData;
            //$result = $this->curl->httpRequest($this->config->synchronizationVehicleUrl, $postData, "post");
            $result = $this->curl->sendCurl($this->config->synchronizationVehicleUrl, $postData, "post");

            if (!isset($result['status']) || $result['status'] != 200) {
                $this->dw_service->rollback();
                $this->logger->error("同步多码合一失败! data :" . json_encode($postData,JSON_UNESCAPED_UNICODE));
            }
            $this->dw_service->commit();
            return $this->toSuccess(true);
        } catch (\Exception $e) {
            $this->logger->error("执行失败...".$e->getMessage());
            $this->dw_service->rollback();
            return $this->toError(500,'上传失败,出现重复数据或其他错误!');
        }

    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 2:待领取 => 3:操作中
     */
    public function StatusAction($id)
    {
        //判断用户状态;
        $user = User::findFirst($this->authed->userId);
        if ($user->user_status != 1) {
            return $this->toError(500,'当前用户状态异常!');
        }

        $task = MulticodeTask::findFirst($id);
        if ($task == false) {
            return $this->toError(500,'不存在当前任务');
        }
        $taskCheck = MulticodeTaskUser::findFirst(['task_id = :task_id: and user_id = :user_id:','bind' => ['task_id' => $id,'user_id' => $this->authed->userId]]);
        if ($taskCheck == false) {
            return $this->toError(500,'非法操作!,当前任务不属于该用户!');
        }

        if ($task->getTaskStatus() == 4) {
            return $this->toError(500,'当前任务状态已完成!');
        }

        if ($task->getTaskStatus() == 2) {
            $task->setTaskStatus(3);
        }

        if ($task->update() == false) {
            return $this->toError(500,'更新失败!');
        } else {
            return $this->toSuccess(true);
        }
    }


    /**
     * 验证数据是否重复
     */
    public function CheckAction()
    {
        $json = $this->request->getJsonRawBody(true);

        if (!isset($json['type'])) {
            return $this->toError(500,'参数不正确!');
        }
        if (!isset($json['value'])) {
            return $this->toError(500,'参数不正确!');
        }

        if ($json['type'] == 'udid') {
            /**
             * 2018.11.13
             * 需求变更 根据url 查询 产商 device_model_id 返回
             */
            $device_model_id = 0;
            $device_code = "";
            $device_name = "";
            $value = '';
            $rules = DeviceModel::find(['conditions' => 'is_delete = 0 and :url: like CONCAT(\'%\',match_prefix,\'%\')','bind' =>['url' =>$json['value']]])->toArray();

            foreach ($rules as $item) {
                if(preg_match($item['qrcode_rule'],$json['value'],$r)) { //匹配字串中是否包至少两位到4位的数字
                    $device_model_id = $item['id'];
                    $device_code = $item['model_code'];
                    $device_name =$item['model_name'];
                    $value = $r[1];
                    break;
                }
            }
            if ($device_model_id == 0) {
                return $this->toSuccess(['status' => false],[],200,"当前设备号不符合规则");
            }

            $vehicle = Vehicle::findFirst(['conditions' => 'udid = :udid: and device_model_id = :device_model_id:','bind' =>['udid' =>$value,"device_model_id" => $device_model_id]]);
            if (!$vehicle) {
                return $this->toSuccess(['status' => true,'device_model_id' => $device_model_id,'value' => $value,'device_code' =>$device_code,'device_name' => $device_name],[],200);
            } else {
                return $this->toSuccess(['status' => false,'value' => $value,'device_code' =>$device_code,'device_name' => $device_name],[],200,"设备编号已使用");
            }
            //如果udid在vehicle里面已经存在了，就判断此udid已被使用
            $vehicle2 = Vehicle::findFirst(['conditions' => 'udid = :udid:','bind' =>['udid' => $value]]);
            if ($vehicle2) {
                return $this->toSuccess(['status' => false],[],200,"设备编号已使用");
            }
        } else if ($json['type'] == 'bianhao') {
            $vehicle = Vehicle::findFirst(['conditions' => 'bianhao = :bianhao:','bind' =>['bianhao' =>$json['value']]]);
            /**
             * 编号是否存在
             */
            $qrcode = Qrcode::findFirst(['conditions' => 'bianhao = :bianhao:','bind' =>['bianhao' =>$json['value']]]);
            if ($qrcode == false) {
                return $this->toSuccess(['status' => false],[],200,"车辆码不存在");
            }
           /* if ($qrcode->status == 3) {
                return $this->toSuccess(['status' => false],[],200,"设备号已被车辆绑定，请联系管理员核对");
            }*/
            if ($vehicle->udid != null && $vehicle->udid != "-1") {
                return $this->toSuccess(['status' => false],[],200,"设备号已被车辆绑定，请联系管理员核对");
            }
            if ($qrcode->status == 1) {
                return $this->toSuccess(['status' => false],[],200,"车辆码未发放，请联系管理员");
            }
            if ($vehicle) {
                return $this->toSuccess(['status' => false],[],200,"车辆码不可用");
            }
            /**
             * 验证编号是否符合规则
             * 得威编号只能为数字 12-16
             */
//            $regex = '/^\d{12,16}$/i';
//            if(!preg_match($regex, $json['value'])){
//                return $this->toSuccess(['status' => false],[],200,"得威编号只能为数字切长度在12-16之间");
//            }

        } else if ($json['type'] == 'vin') {
            if (!isset($json['vehicle_model_id'])) {
                return $this->toError(500,'车辆型号不能为空！');
            }
            /**
             * 验证车架号是否符合规则
             * 车架号只能为数字和字母
             */
            $regex = '/^[a-zA-Z0-9-#.\/]+$/i';
            if(!preg_match($regex, $json['value'])){
                return $this->toSuccess(['status' => false],[],200,"车架号只能为数字或字母或-#./");
            }

            $vehicle = Vehicle::findFirst(['conditions' => 'vin = :vin: and vehicle_model_id = :vehicle_model_id:','bind' =>['vin' =>$json['value'],'vehicle_model_id' =>$json['vehicle_model_id'] ]]);
            if ($vehicle) {
                return $this->toSuccess(['status' => false],[],200,"车架号已使用");
            }

        } else if ($json['type'] == 'plate_num') {
            $vehicle = Vehicle::findFirst(['conditions' => 'plate_num = :plate_num:','bind' =>['plate_num' =>$json['value']]]);
            if ($vehicle) {
                return $this->toSuccess(['status' => false],[],200,"车牌号已使用");
            }
        } else {
            return $this->toSuccess(['status' => false],[],200,"type参数不正确");
        }
        return $this->toSuccess(['status' => true]);
    }



    public function getUdidAction(){
        $json = $this->request->getJsonRawBody(true);

        if (!isset($json['type'])) {
            return $this->toError(500,'参数不正确!');
        }
        if (!isset($json['value'])) {
            return $this->toError(500,'参数不正确!');
        }

        if ($json['type'] == 'udid') {
            $device_model_id = 0;
            $device_code = "";
            $device_name = "";
            $value = '';
            $rules = DeviceModel::find(['conditions' => 'is_delete = 0 and :url: like CONCAT(\'%\',match_prefix,\'%\')', 'bind' => ['url' => $json['value']]])->toArray();

            foreach ($rules as $item) {
                if (preg_match($item['qrcode_rule'], $json['value'], $r)) { //匹配字串中是否包至少两位到4位的数字
                    $device_model_id = $item['id'];
                    $device_code = $item['model_code'];
                    $device_name = $item['model_name'];
                    $value = $r[1];
                    break;
                }
            }
            if ($device_model_id == 0) {
                return $this->toSuccess(['status' => false], [], 200, "查询不到对应供应商");
            }
            $vehicle = Vehicle::findFirst(['conditions' => 'udid = :udid: and device_model_id = :device_model_id:','bind' =>['udid' =>$value,"device_model_id" => $device_model_id]]);
            if ($vehicle) {
                return $this->toSuccess(['status' => true,'device_model_id' => $device_model_id,'value' => $value,'device_code' =>$device_code,'device_name' => $device_name],[],200);
            } else {
                return $this->toSuccess(['status' => false,'device_model_id' => $device_model_id,'value' => $value,'device_code' =>$device_code,'device_name' => $device_name],[],200,"设备编号暂未用");
            }
        } else {
            return $this->toSuccess(['status' => false], [], 200, "参数错误！");
        }
    }
}
<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\dispatch\DriverDevicetoken;
use app\models\dispatch\DriverEventBlacklist;
use app\models\dispatch\DriverMessage;
use app\models\service\AppUmeng;


class MessagePushData extends BaseData
{

    const DW_DRIVER_APP = 'DW_DRIVER';
    const VEHICLE_UNUSUAL = 'VEHICLE_UNUSUAL';

    // 还车事件CODE
    const EVENT_RETURNVEHICLE = 'RetuenVehicleSuccess';
    // 维修提交事件CODE
    const EVENT_REPAIR_ACCEPT = 'RepairAccept';
    // 维修开始事件CODE
    const EVENT_REPAIR_START = 'RepairStart';
    // 维修完成事件CODE
    const EVENT_REPAIR_FINISH = 'RepairFinish';
    // 维修取消事件CODE
    const EVENT_REPAIR_CANCEL = 'RepairCancel';


    // 骑手登录记录设备
    public function DriverLoginDevice($driverId)
    {
        $types = [
            'android' => 1,
            'ios' => 2,
        ];
        // 获取设备token
        $deviceToken = $this->request->getHeader('deviceToken') ?? '';
        $deviceUUID = $this->request->getHeader('deviceUUID') ?? '';
        // 获取包名
        $packageName = $this->request->getHeader('packageName');
        // 设备类型
        $type = $this->request->getHeader('type');
        if ((empty($deviceToken) && empty($deviceUUID)) || empty($packageName) || !($driverId>0) || !isset($types[$type])){
            return false;
        }
        $deviceType = $types[$type];
        // 查询应用APPID
        $AppId = $this->getAppIdByPackageName($packageName, $deviceType) ?? 0;
        // 查询设备记录
        $DriverDeviceS = DriverDevicetoken::find([
            'app_id = :app_id: and (driver_id = :driver_id: or device_uuid = :device_uuid:)',
            'bind' => [
                'app_id' => $AppId,
                'driver_id' => $driverId,
                'device_uuid' => $deviceUUID,
            ]
        ]);
        $exist = false;
        // 使用driver_id做唯一
        foreach ($DriverDeviceS as $DriverDevice){
            if (!empty($deviceUUID) && $DriverDevice->device_uuid == $deviceUUID && false==$exist){
                //
                if (0 == $DriverDevice->app_id){
                    $DriverDevice->app_id = $AppId;
                }
                $exist =true;
                // 更新
                $DriverDevice->device_token = $deviceToken;
                $DriverDevice->package_name = $packageName;
                $DriverDevice->driver_id = $driverId;
                $DriverDevice->last_login = time();
                $bol = $DriverDevice->save();
                continue;
            }
            // 历史同 deviceToken/骑手 存在的非本设备记录，删除
            $DriverDevice->delete();
        }
        if (false === $exist){
            // 不存在则新增
            $data = [
                'driver_id' => $driverId,
                'app_id' => $AppId,
                'device_token' => $deviceToken,
                'device_uuid' => $deviceUUID,
                'package_name' => $packageName,
                'device_type' => $deviceType,
                'create_at' => time(),
                'last_login' => time(),
            ];
            $bol = (new DriverDevicetoken())->save($data);
        }
        return true;
    }

    /**
     * 获取APPID通过包名
     * @param $packageName
     * @param null $type
     * @return bool|\Phalcon\Mvc\Model\Resultset|\Phalcon\Mvc\Phalcon\Mvc\Model
     */
    public function getAppIdByPackageName($packageName, $type=null)
    {
        $data['package_name'] = $packageName;
        if (!is_null($type)){
            $data['app_type'] = $type;
        }
        $data['is_delete'] = 1;
        $AU = AppUmeng::arrFindFirst($data);
        if (false === $AU){
            return false;
        }
        return $AU->app_id;
    }



    /**
     * 获取友盟APP信息
     * @param $packageName
     * @return array
     */
    public function getUmengByPackageName($packageName)
    {
        $data = AppUmeng::find([
            'package_name = :package_name: and app_status = 1',
            'bind' => [
                'package_name' => $packageName,
            ],
        ])->toArray();
        $ls = [];
        foreach ($data as $item) {
            $mark = $item['package_name'].'-'.$item['app_type'];
            $ls[$mark] = $item;
        }
        return $ls;
    }

    /**
     * 获取有效友盟APP信息
     * @param $AppSN
     * @return array
     */
    public function getValidUmengByAppSN($AppSN)
    {
        $data = AppUmeng::find([
            'app_sn = :app_sn: and app_status = 1 and is_delete=1',
            'bind' => [
                'app_sn' => $AppSN,
            ],
        ])->toArray();
        $ls = [];
        foreach ($data as $item) {
            $mark = $item['package_name'].'-'.$item['app_type'];
            $ls[$mark] = $item;
        }
        return $ls;
    }

    /**
     * 处理模版消息
     * @param $body 模版中的消息体
     * @param array $data 替换数组
     * @return mixed 消息
     */
    public function GenerateMessage($body, $data=[])
    {
        // 处理消息变量
        foreach ($data as $key => $val){
            $body = str_ireplace('{'.$key.'}', $val, $body);
        }
        return $body;
    }


    /**
     * 调用微服务推送消息
     * @param $data
     * @return mixed
     * @throws DataException
     */
    public function PushMsg($data)
    {
        // 推送模型 1测试模型 2生产模式
        $modeType = 1;
        // 判断是否是生产环境模型
        if (isset($this->config->env) && 'prod'==$this->config->env){
            $modeType = 2;
        }
        $extraField = isset($data['msg']['extraField']) ? $data['msg']['extraField'] : [];
        // 默认补全msg参数
        $defalutMsg = [
            // 推送方式 1 单播，2广播 3群播
            'pushType' => 1,
            // 推送模型 1测试模型 2生产模式
            'modeType' => $modeType,
            // 推送方式 固定为2message透传 (安卓必填)
            'notification' => '2',
            // IOS 内容？
            'alert' => isset($extraField['messageBody']) ? $extraField['messageBody'] : '',
            // 标记(ios必填)
            'badge' => isset($extraField['unReadTotal']) ? $extraField['unReadTotal'] : 1,
            // 提示音(ios必填)
            'sound' => 'defalut',
            // 点击通知后动作(安卓必填)
            'custom' => '',
            // 描述 IOS不展示，未确定
            // 'description' => isset($extraField['messageBody']) ? $extraField['messageBody'] : '',
        ];
        // 合并参数
        $data['msg'] = array_merge($defalutMsg, $data['msg']);
        // 调用微服务发送消息
        $result = $this->curl->httpRequest($this->Zuul->msgPush,[
            'code' => 10001,
            'parameter' => $data,
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([500, $result['msg']]);
        }
        $data = $result['content']['data'];
        return isset($data['iosMsgId']) ? $data['iosMsgId'] : $data['androidMsgId'];
    }

    /**
     * 获取骑手未读消息条数
     * @param $driverId
     * @param $AppId
     * @return mixed
     */
    public function getUnReadTotal($driverId, $AppId)
    {
        $where = [
            'driver_id' => $driverId,
            'app_id' => $AppId,
            'is_read' => 1,
            'is_delete' => 1,
        ];
        $queryArr = DriverMessage::dealWithWhereArr($where);
        // 总条数
        return DriverMessage::count($queryArr);
    }

    /**
     * 维修单获取状态对应模版
     * @param $repairStatus 维修单状态
     * @return string 模版
     */
    public function getRepairEventCodeByRepairStatus($repairStatus)
    {
        // 选择模版
        switch($repairStatus){
            case 1 :
                $templateSn = self::EVENT_REPAIR_ACCEPT;
                break;
            case 2 :
                $templateSn = self::EVENT_REPAIR_ACCEPT;
                break;
            case 3 :
                $templateSn = self::EVENT_REPAIR_START;
                break;
            case 5 :
                $templateSn = self::EVENT_REPAIR_FINISH;
                break;
            case 6 :
                $templateSn = self::EVENT_REPAIR_CANCEL;
                break;
            default :
                return false;
        }
        return $templateSn;
    }


    /**
     * @param int $driverId
     * @param int $appId
     * @param string $deviceToken
     * @param int $eventId
     * @param array $data
     * @return bool
     *
     */
    public function SendMessageToDriverUseDevicetoken(int $driverId,int $appId,string $deviceToken,int $eventId,array $data){
        /**
         * 判断当前用户是否需要发送
         */
        $check = $this->checkDriverAppEvent($appId,$driverId,$deviceToken,$eventId);
        if ($check) {
            /**
             * 存在禁用数据无需发送,认为发送成功;
             */
            return true;
        }
        /**
         * 需要发送信息
         */
        /**
         * 1.根据$eventId获取消息对应模板
         */
        $template = $this->modelsManager->createBuilder()
            ->columns('t.*')
            ->addFrom('app\models\service\MessageTemplate','t')
            ->leftJoin('app\models\service\AppEvent','t.id = e.template_id','e')
            ->andWhere('e.id = :id:',['id' => $eventId])
            ->getQuery()->getSingleResult();

        if ($template == false) {
            $this->logger->error('事件:'.$appId.'未查到有效模版!');
            return null;
        }

        /**
         * 获取umeng信息
         */
        $Umeng = AppUmeng::findFirst(['app_id = :app_id:','bind' => $appId])->toArray();
        if ($Umeng == false){
            return false;
        }

        // 处理模版消息
        $msgBody = $this->GenerateMessage($template->template_text, $data);
        // 处理跳转协议
        $noticeTypes = ['notice_url', 'notice_andriod', 'notice_ios'];
        foreach ($noticeTypes as $val){
            $template->$val = $this->GenerateMessage($template->$val, $data);
        }
        // 消息入库
        $msgData = [
            'template_id' => $template->id,
            'driver_id' => $driverId,
            'message_title' => $template->template_name,
            'message_body' => $msgBody,
            'notice_url' => $template->notice_url,
            'notice_andriod' => $template->notice_andriod,
            'notice_ios' => $template->notice_ios,
            'is_read' => 1,
            'is_delete' => 1,
            'message_time' => time(),
        ];
        $DriverMessage = new DriverMessage();
        $bol = $DriverMessage->save($msgData);
        if (false === $bol){
            $this->logger->error('消息入库失败:'.PHP_EOL.json_encode($msgData, JSON_UNESCAPED_UNICODE));
            return false;
        }
        $MessageId = $DriverMessage->id;
        // 获取未读消息数量
        $unReadTotal = $this->getUnReadTotal($driverId);

            $data = [
                // 设备 1-安卓 2-水果
                'appPushType' => $Umeng['app_type'],
                'bizKey' => date('YmdHis',time()).md5($deviceToken),
                'msg' => [
                    'appkey' => $Umeng['appkey'],
                    'appMasterSecret' => $Umeng['mastersecret'],
                    'deviceToken' => $deviceToken,
                    'extraField' => [
                        'messageId' => $MessageId,
                        'messageTitle' => $msgData['message_title'],
                        'messageBody' => $msgBody,
                        'unReadTotal' => $unReadTotal,
                    ],
                ],
            ];
            $log = '骑手消息推送'.$driverId.PHP_EOL;
            $log .= json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            // 调用微服务发送消息 捕捉异常，单条失败不影响其他
            try{
                $log .= $this->PushMsg($data);
                $this->logger->info($log);
            }catch (DataException $e){
                $log .= $e->getMessage().PHP_EOL;
                $this->logger->error($log);
            }
        return true;

    }




    public function SendMessageToDriverV2($driverId,$appCode,$eventCode,array $data)
    {

        /**
         * 获取事件对应模板信息
         */
        $app = $this->modelsManager->createBuilder()
        ->columns('a.id,a.app_code,a.app_status,a.is_delete,e.id as event_id,
            e.event_level,e.template_id,e.if_show,
            t.template_name,t.template_type,t.notice_type,t.notice_url,t.notice_andriod,t.notice_ios,
            t.template_status,t.template_pic,t.template_text,t.template_need_button,t.template_button')
        ->addFrom('app\models\service\AppList','a')
        ->leftJoin('app\models\service\AppEventRelation','aer.app_id = a.id','aer')
        ->leftJoin('app\models\service\AppEvent','aer.event_id = e.id','e')
        ->leftJoin('app\models\service\MessageTemplate','e.template_id = t.id','t')
        ->andWhere('e.event_code = :event_code: and a.app_code = :app_code:
         and e.event_status = 1 and e.is_delete = 1
         and a.app_status = 1 and a.is_delete = 1
         and t.is_delete = 1 and t.template_status = 1',['event_code' => $eventCode,'app_code' => $appCode])
        ->getQuery()->getSingleResult();


        /**
         * 发送条件有误,不能发送信息
         */
        if ($app == false ||$app->id == null || $app->template_name == null || $app->template_id == null)
        {
            return false;
        }

        /**
         * 当前骑手有哪些设备接受通知
         */

        /**
         * 查询用户使用了哪些设备
         */
        $driverDevicetoken = DriverDevicetoken::find(['driver_id = :driver_id: and app_id = :app_id:', 'bind' => ['driver_id' => $driverId,'app_id' => $app->id]])->toArray();

        if ($driverDevicetoken == false) {
            return false;
        }

        /**
         * 根据appID查询友盟信息 多条数据
         */
        $umengs = [];
        $appUmengs = AppUmeng::find(['conditions'=>'app_id = :app_id: and app_status = 1 and is_delete = 1','bind' =>['app_id' =>  $app->id]])->toArray();
        /**
         * 根据APP类型存储友盟信息
         */
        foreach ($appUmengs as $item) {
            $umengs[$item['app_type']] = $item;
        }




        //发送信息
        // 处理模版消息
        $msgBody = $this->GenerateMessage($app->template_text, $data);
        // 处理跳转协议
        $noticeTypes = ['notice_url', 'notice_andriod', 'notice_ios'];
        foreach ($noticeTypes as $val){
            $app->$val = $this->GenerateMessage($app->$val, $data);
        }
        // 消息入库
        $msgData = [
            'app_id' => $app->id,
            'template_id' => $app->template_id,
            'driver_id' => $driverId,
            'message_title' => $app->template_name,
            'message_body' => $msgBody,
            'notice_url' => $app->notice_url,
            'notice_andriod' => $app->notice_andriod,
            'notice_ios' => $app->notice_ios,
            'is_read' => 1,
            'is_delete' => 1,
            'message_time' => time(),
        ];
        $DriverMessage = new DriverMessage();
        $bol = $DriverMessage->save($msgData);
        if (false === $bol){
            $this->logger->error('消息入库失败:'.PHP_EOL.json_encode($msgData, JSON_UNESCAPED_UNICODE));
            return false;
        }
        $MessageId = $DriverMessage->id;
        // 获取未读消息数量
        $unReadTotal = $this->getUnReadTotal($driverId, $app->id);


        foreach ($driverDevicetoken as $item) {
            //用户不能禁用 直发
            if ($app->if_show == 1) {
                $check = $this->checkDriverAppEvent($app->id,$driverId,$item['device_token'],$app->event_id);
                if ($check) {
                   //禁用
                    continue;
                }
            }
            $data = [
                // 设备 1-安卓 2-水果
                'appPushType' => $item['device_type'],
                'bizKey' => date('YmdHis',time()).md5($item['device_token']),
                'msg' => [
                    'appkey' => $umengs[$item['device_type']]['appkey'],
                    'appMasterSecret' => $umengs[$item['device_type']]['mastersecret'],
                    'deviceToken' => $item['device_token'],
                    'extraField' => [
                        'messageId' => $MessageId,
                        'messageTitle' => $msgData['message_title'],
                        'messageBody' => $msgBody,
                        'unReadTotal' => $unReadTotal,
                    ],
                ],
            ];
            $log = '骑手消息推送'.$driverId.PHP_EOL;
            $log .= json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            // 调用微服务发送消息 捕捉异常，单条失败不影响其他
            try{
                $log .= $this->PushMsg($data);
                $this->logger->info($log);
            }catch (DataException $e){
                $log .= $e->getMessage().PHP_EOL;
                $this->logger->error($log);
            }
        }

        return true;
    }



    public function checkDriverAppEvent($appId,$driverId,$deviceToken,$eventId)
    {
        $checkEvent = DriverEventBlacklist::findFirst(['conditions' => 'app_id = :app_id: and driver_id = :driver_id: and device_token = :device_token: and event_id = :event_id:',
            'bind' =>['app_id' => $appId,'driver_id' => $driverId,'device_token' => $deviceToken,'event_id' => $eventId]]);
        //存在禁用数据 说明 当前信息该用户不需要发送!!
        if ($checkEvent) {
            return true;
        } else {
            return false;
        }
    }

}

<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: MessageController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\home;


use app\models\service\Vehicle;
use app\modules\BaseController;
use app\services\data\MessagePushData;

class MessageController extends BaseController
{
    public $PUSH_STATUS = [
        101 => '锁车从关到开',
        102 => '锁车从开到关',
        103 => '电门从关到开',
        104 => '电门从开到关',
        105 => '车辆震动',
        107 => '电瓶在位=>不在位',
        108 => '电瓶不在位=>在位',
        109 => '车辆倾倒(测试中)',
        110 => '车辆扶正(测试中)'
    ];




    /**
     * 车辆异动接收
     * 收到异动通知是否需要存入数据库.
     */
    public function IndexAction()
    {
        $type = $this->request->getQuery('type','int');
        $udid = $this->request->getQuery('udid','string');
        $timestamp = $this->request->getQuery('timestamp','int');
        $sign = $this->request->getQuery('sign','string');

        //验证是否正确
        $md5 = md5($this->config->anzhiyun->channel.$type.$timestamp.$udid.$this->config->anzhiyun->key);
        if ($md5 != $sign) {
            return $this->toError(500,'验证不通过!');
        }

        switch ($type) {
            case 105 :
                if ($this->unusual($udid,$timestamp)) {
                    return $this->toSuccess(true);
                } else {
                    return $this->toError(500,'发送失败!');
                }
                break;
            default:
                return $this->toError(500,'发送失败!');
        }

    }

    private function unusual($udid,$timestamp)
    {
        //根据udid 查询数据
        $vehicle = Vehicle::findFirst(['conditions' => 'udid = :udid:','bind' => ['udid' => $udid]]);
        /**
         * 当前车辆没有对应的骑手
         */
        if ($vehicle == false || $vehicle->driver_id <= 0) {
            return true;
        }
        $data = [
            'bianhao'=>$vehicle->bianhao
        ];
        return $this->messagePushData->SendMessageToDriverV2($vehicle->driver_id,MessagePushData::DW_DRIVER_APP,MessagePushData::VEHICLE_UNUSUAL,$data);
    }
}
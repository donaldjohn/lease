<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: MessageController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\driversapp;


use app\models\dispatch\DriverAppEvent;
use app\models\dispatch\DriverDevicetoken;
use app\models\dispatch\DriverEventBlacklist;
use app\models\service\AppEvent;
use app\models\service\AppUmeng;
use app\modules\BaseController;

class MessageController extends BaseController
{


    public function treeAction()
    {


        $tree = [];
        $types = [
            'android' => 1,
            'ios' => 2,
        ];
        // 获取设备token
        $deviceToken = $this->request->getHeader('deviceToken');
        // 获取包名
        $packageName = $this->request->getHeader('packageName');
        // 设备类型
        $type = $this->request->getHeader('type');
        if ($type == null) {
            return $this->toError(500,'type必填!');
        }

        $deviceType = $types[strtolower($type)];

//        $DriverDevicetoken = DriverDevicetoken::findFirst([
//            'device_token = :device_token: and package_name = :package_name: and device_type = :device_type:',
//            'bind' => [
//                'device_token' => $deviceToken,
//                'package_name' => $packageName,
//                'device_type' => $deviceType,
//            ]
//        ]);
//
//        if ($DriverDevicetoken == false) {
//            return $this->toError(500,'当前设备未记录');
//        }

        //根据packageName查询 appId

        $appUmeng = AppUmeng::findFirst(['conditions' => 'package_name = :package_name: and app_type = :deviceType: and is_delete = 1','bind' => ['package_name' => $packageName,'deviceType' => $deviceType]]);
        if ($appUmeng == false) {
            return $this->toError(500,'当前app未注册!');
        }

        $appId = $appUmeng->getAppId();


        //当前app能用的event 默认启用状态
        $builder  = $this->modelsManager->createBuilder()
            ->columns('e.id,e.event_name,e.event_code,e.event_level,e.if_show,e.parent_id,e.event_order,e.event_status,e.is_delete')
            ->addFrom('app\models\service\AppEvent','e')
            ->leftJoin('app\models\service\AppEventRelation','e.id = r.event_id','r')
            ->andWhere('r.app_id = :app_id: and e.if_show = 1 and e.event_status = 1 and e.is_delete = 1',['app_id' => $appId])
            ->orderBy('e.parent_id,e.event_order')
            ->getQuery()->execute()->toArray();


        if (count($builder) == 0) {
            return $this->toSuccess($tree);
        }

        //查询已禁用的
        $forbiddenEventIds = [];
        $result = DriverEventBlacklist::find(['conditions' => 'app_id = :app_id: and device_token = :device_token: and driver_id = :driver_id:',
            'bind' => ['app_id' => $appId,'device_token' => $deviceToken,'driver_id' => $this->authed->userId]])->toArray();

//        $messageStatus = true;
//        //启用和禁用数量一样
//        if (count($builder) == count($result)) {
//            $messageStatus = false;
//        }
        foreach ($result as $item) {
            $forbiddenEventIds[] = $item['event_id'];
        }
        foreach($builder as $item) {
            if (in_array($item['id'],$forbiddenEventIds)) {
                $item['checked'] = false;
            } else {
                $item['checked'] = true;
            }
            if ($item['parent_id'] == 0) {
                $tree[$item['id']] = $item;
            } else {
                $tree[$item['parent_id']]['children'][] = $item;
            }
        }
        $tree = array_merge($tree);

        return $this->toSuccess($tree);

    }


    public function UpdateTreeAction()
    {

        $types = [
            'android' => 1,
            'ios' => 2,
        ];
        // 获取设备token
        $deviceToken = $this->request->getHeader('deviceToken');
        // 获取包名
        $packageName = $this->request->getHeader('packageName');
        // 设备类型
        $type = $this->request->getHeader('type');

        $deviceType = $types[strtolower($type)];

        $appUmeng = AppUmeng::findFirst(['conditions' => 'package_name = :package_name: and app_type = :app_type: and is_delete = 1','bind' => ['package_name' => $packageName,'app_type' => $deviceType]]);
        if ($appUmeng == false) {
            return $this->toError(500,'当前app未注册!');
        }
        $appId = $appUmeng->getAppId();

        $json = $this->request->getJsonRawBody(true);

        if (isset($json['ids']) && is_array($json['ids']) ) {
            $ids = $json['ids'];
        } else {
            return $this->toError(500,'IDS数组必传!');
        }

        if (isset($json['status'])) {
            $status = $json['status'];
        } else {
            return $this->toError(500,'状态必传!');
        }

        if ($status == 1) {
            //删除driverAppEvent
            $phql = "delete from app\models\dispatch\DriverEventBlacklist where app_id = {app_id} and driver_id = {driver_id} and device_token = {device_token} and event_id IN ({ids:array})";
            $robots = $this->modelsManager->executeQuery(
                $phql,
                ['app_id' => $appId,'driver_id' => $this->authed->userId,'device_token'=>$deviceToken,'ids' => $ids]
            );

            if ($robots == false) {
                return $this->toError(500,'操作失败!');
            }
        } else {
            //禁用 增加
            $insert = [];
            $time = time();
            foreach($ids as $item) {
                $list = [];
                $list['app_id'] = $appId;
                $list['driver_id'] = $this->authed->userId;
                $list['device_token'] = $deviceToken;
                $list['event_id'] = $item;
                $list['create_time'] = $time;
                $insert[] = $list;
            }
            $driverAppEvent = new DriverEventBlacklist();
            $sql = $driverAppEvent->batch_insert($insert);

            $result = $this->dw_dispatch->query($sql);

            if ($result == false) {
                return $this->toError(500,'操作失败!');
            }
        }
        return $this->toSuccess(true);



    }
}
<?php
namespace app\modules\driversapp;

use app\modules\BaseController;
use app\services\data\MessagePushData;
use app\models\dispatch\DriverMessage;
use app\models\service\MessageTemplate;

class MsgController extends BaseController
{
    /**
     * 骑手APP消息列表
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function MsglistAction()
    {
        $limit = isset($_GET['pageSize']) ? $_GET['pageSize'] : 20;
        $pageNum = isset($_GET['pageNum']) ? $_GET['pageNum'] : 1;
        $driverId = $this->authed->userId;
        $MessagePushData = new MessagePushData();
        // 获取包名
        $packageName = $this->request->getHeader('packageName');
        // 查询应用APPID
        $AppId = $MessagePushData->getAppIdByPackageName($packageName);
        if (false === $AppId){
            return $this->toError(500, '未认可的客户端');
        }
        $where = [
            'app_id' => $AppId,
            'driver_id' => $driverId,
            'is_delete' => 1,
        ];
        $queryArr = $this->arrToQuery($where);
        // 总条数
        $count = DriverMessage::count($queryArr);
        // 分页
        $queryArr['order'] = 'id DESC';
        $queryArr['limit'] = $limit;
        $queryArr['offset'] = ($pageNum-1)*$limit;
        // 查询
        $list = DriverMessage::find($queryArr)->toArray();
        $meta = [
            'total' => $count,
            'pageSize' => $limit,
            'pageNum' => $pageNum,
        ];
        return $this->toSuccess($list, $meta);
    }

    /**
     * 骑手APP消息详情
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function ReadmsgAction($id)
    {
        $driverId = $this->authed->userId;
        // 查询
        $msg = DriverMessage::findFirst([
            'conditions' => 'id = ?1',
            'bind'       => [
                1 => $id,
            ],
        ]);
        if (false === $msg){
            return $this->toError(500, '未查询到消息');
        }
        // 判断骑手身份
        if ($driverId != $msg->driver_id){
            return $this->toError(500, '身份验证不通过');
        }
        // 获取模版信息
        $template = MessageTemplate::findFirst([
            'id = :id:',
            'bind' => [
                'id' => $msg->template_id,
            ]
        ]);
        if (false === $template){
            return $this->toError(500, '消息模版不存在');
        }
        // 标记消息已读
        if (1 == $msg->is_read){
            $msg->is_read = 2;
            $msg->read_time = time();
            $msg->save();
        }
        // 整合消息图片
        $msg = $msg->toArray();
        $msg['template_pic'] = $template->template_pic;
        // 是否需要按钮,1需要 2不需要
        if (1 == $template->template_need_button){
            $msg['buttonText'] = $template->template_button;
        }
        return $this->toSuccess($msg);
    }

    /**
     * 批量删除
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function DelmsgsAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['idList']) || empty($request['idList']) || !is_array($request['idList'])){
            return $this->toError(500, '参数错误');
        }
        // 查询消息
        $msgs = DriverMessage::find([
            'id IN ({id:array}) and driver_id = :driver_id:',
            'bind' => [
                'id' => array_values(array_unique($request['idList'])),
                'driver_id' => $driverId,
            ]
        ]);
        $bol = $msgs->update([
            'is_delete' => 2,
        ]);
        if (false===$bol){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }

    /**
     * 批量已读
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function ReadmsgsAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['idList']) || empty($request['idList']) || !is_array($request['idList'])){
            return $this->toError(500, '参数错误');
        }
        // 查询消息
        $msgs = DriverMessage::find([
            'id IN ({id:array}) and driver_id = :driver_id:',
            'bind' => [
                'id' => array_values(array_unique($request['idList'])),
                'driver_id' => $driverId,
            ]
        ]);
        $bol = $msgs->update([
            'is_read' => 2,
            'read_time' => time(),
        ]);
        if (false===$bol){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }

    /**
     * 获取未读消息数
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function UnreadtotalAction()
    {
        $MessagePushData = new MessagePushData();
        $driverId = $this->authed->userId;
        // 获取包名
        $packageName = $this->request->getHeader('packageName');
        // 查询应用APPID
        $AppId = $MessagePushData->getAppIdByPackageName($packageName);
        if (false === $AppId){
            return $this->toError(500, '未认可的客户端');
        }
        // 获取骑手未读消息条数
        $count = $MessagePushData->getUnReadTotal($driverId, $AppId);
        return $this->toSuccess(['total'=>$count]);
    }
}

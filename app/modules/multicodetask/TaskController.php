<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace  app\modules\multicodetask;


use app\models\service\MulticodeTask;
use app\models\service\MulticodeTaskDetail;
use app\models\service\MulticodeTaskUser;
use app\models\users\User;
use app\modules\BaseController;
use Phalcon\Exception;

class TaskController extends BaseController
{

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 列表
     */
    public function ListAction()
    {
        /**
         * 获取参数
         */
        $indexText = $this->request->getQuery('indexText','string',null);
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);
        /**
         * 查询分页数据
         */
        $multiCodeTaks = $this->taskData->getTaskPage($pageNum,$pageSize,$indexText,null,$this->authed->userId,null);
        return $this->toSuccess($multiCodeTaks['data'],$multiCodeTaks['meta']);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 查询单个
     */
    public function OneAction($id)
    {
        /**
         * 获取task详情
         *
         */
        $task = $this->taskData->getTask($id);
        //$user = User::findFirst($task->user_id);
        //$task->user_name = $user->getUserName();
        $user = $this->userData->getUserById($task->user_id);
        $task->real_name = $user['realName'];
        return $this->toSuccess($task);
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\MicroException
     * 创建
     */
    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['task_user_id']))
            return $this->toError(500,'任务操作人员不能为空');
        /**
         * 判断当前用户ID是否是供应商子用户
         * 获取当前用户的userid
         */
        //调用微服务接口获取数据
        $params = ["code" => "10004", "parameter" => ['id'=> $json['task_user_id'],'parentId' => $this->authed->userId,'userType' => 5]];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (!isset($result['statusCode']) && $result['statusCode'] != '200')
            return $this->toError(500,'非法用户!');
        if (!isset($result['content']['users'][0])) {
            return $this->toError(500,'非法用户!');
        }

        try {
            $this->dw_service->begin();
            $multicodeTask = new MulticodeTask();
            $multicodeTask->assign($json);
            $multicodeTask->setTaskStatus(1);
            /**
             * 生成task_code
             */
            $params = ["code" => "11030", "parameter" => ["prefix" => 'MTC']];
            $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
            if (!isset($result['statusCode']) && $result['statusCode'] != '200')
                return $this->toError(500,'生成任务代码失败!');
            $code = isset($result['content']['data']) ? $result['content']['data'] : null;
            if ($code == null) {
                return $this->toError(500,'生成任务代码失败!');
            }
            $multicodeTask->setTaskCode($code);
            $multicodeTask->setCreateUserId($this->authed->userId);
            if ($multicodeTask->save() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$multicodeTask->getMessages()[0]->getMessage());
            }
            $TaskUser = new MulticodeTaskUser();
            $TaskUser->setTaskId($multicodeTask->getId());
            $TaskUser->setUserId($json['task_user_id']);
            if ($TaskUser->save() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$multicodeTask->getMessages()[0]->getMessage());
            }
            $this->dw_service->commit();
            return $this->toSuccess($multicodeTask,'',200,'新增成功!');
        } catch (\Exception $e) {
            $this->dw_service->rollback();
            return $this->toError(500,$e->getMessage());
        }
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 状态
     */
    public function StatusAction()
    {
        /**
         * 获取ids和status
         */
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['ids']) || !is_array($json['ids']) || count($json['ids']) < 1) {
            return $this->toError(500,'缺少必要参数(ids)或参数格式不正确!');
        }
        if (!isset($json['status'])) {
            return $this->toError(500,'缺少必要参数(status)!');
        }
        $this->dw_service->begin();
        if ($json['status'] == 2) { //提交就是1=>2
            foreach($json['ids'] as $item ) {
                $task = MulticodeTask::findFirst($item);
                if ($task->getTaskStatus() != 1) {
                    $this->dw_service->rollback();
                    return $this->toError(500, '当前任务不可提交!');
                }
                $task->setTaskStatus(2);
                if ($task->update() == false) {
                    $this->dw_service->rollback();
                    return $this->toError(500, $task->getMessages()[0]->getMessage());
                }
            }
            $this->dw_service->commit();
            return $this->toSuccess(true);
        }

        if ($json['status'] == 1) { //撤销 2 => 1
            foreach($json['ids'] as $item ) {
                $task = MulticodeTask::findFirst($item);
                if ($task->getTaskStatus() != 2) {
                    $this->dw_service->rollback();
                    return $this->toError(500, '当前任务不可撤销!');
                }
                $task->setTaskStatus(1);
                if ($task->update() == false) {
                    $this->dw_service->rollback();
                    return $this->toError(500, $task->getMessages()[0]->getMessage());
                }
            }
            $this->dw_service->commit();
            return $this->toSuccess(true);
        }
        $this->dw_service->rollback();
        return $this->toError(500,'状态错误!');

    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 更新任务
     */
    public function UpdateAction($id)
    {
        $task = MulticodeTask::findFirst($id);
        if ($task->getTaskStatus() != 1) {
            return $this->toError(500,'当前任务状态不能删除更新!');
        }

        if ($task->getCreateUserId() != $this->authed->userId) {
            return $this->toError(500,'任务不属于当前用户!');
        }

        $json = $this->request->getJsonRawBody(true);

        if (!isset($json['task_user_id']))
            return $this->toError(500,'任务操作人员不能为空');
        /**
         * 判断当前用户ID是否是供应商子用户
         * 获取当前用户的userid
         */
        //调用微服务接口获取数据
        $params = ["code" => "10004", "parameter" => ['id'=> $json['task_user_id'],'parentId' => $this->authed->userId,'userType' => 5]];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (!isset($result['statusCode']) && $result['statusCode'] != '200')
            return $this->toError(500,'非法用户!');
        if (!isset($result['content']['users'][0])) {
            return $this->toError(500,'非法用户!');
        }

        try {
            $this->dw_service->begin();
            $multicodeTask = MulticodeTask::findFirst($id);
            unset($json['task_status']);
            unset($json['task_code']);
            unset($json['task_completed_num']);
            unset($json['create_at']);
            $multicodeTask->assign($json);
            if ($multicodeTask->update() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$multicodeTask->getMessages()[0]->getMessage());
            }
            $TaskUser = MulticodeTaskUser::findFirst(['task_id = :task_id:','bind' => ['task_id' => $id]]);
            if ($TaskUser == null) {
                $this->dw_service->rollback();
                return $this->toError(500,'数据有误,缺少用户,请联系管理员!');
            }
            $user_id = $json['task_user_id'];
            $TaskUser->setUserId($user_id);
            if ($TaskUser->update() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$multicodeTask->getMessages()[0]->getMessage());
            }
            $this->dw_service->commit();
            return $this->toSuccess($multicodeTask,'',200,'更新成功!');
        } catch (\Exception $e) {
            $this->dw_service->rollback();
            return $this->toError(500,$e->getMessage());
        }

    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 删除任务
     */
    public function DeleteAction($id)
    {
        $task = MulticodeTask::findFirst($id);
        if ($task->getTaskStatus() != 1) {
            return $this->toError(500,'当前任务状态不能删除!');
        }

        if ($task->getCreateUserId() != $this->authed->userId) {
            return $this->toError(500,'任务不属于当前用户!');
        }

        if ($task->delete() == false) {
            return $this->toError(500,$task->getMessages()[0]->getMessage());
        }
        return $this->toSuccess($id,'',200,'删除成功!');

    }



    public function DetailAction($id)
    {
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


    public function UserAction()
    {
        $user = User::find(['columns' => 'id,real_name','conditions' => 'parent_id = :parent_id: and user_status = :user_status: and is_delete = 0','bind' => ['parent_id' => $this->authed->userId,'user_status' => 1]]);
        return $this->toSuccess($user);
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 导入 车牌号车架号关联更新
     */
    public function LeadInAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $result = $this->userData->postCommon($json,$this->Zuul->vehicle,60016);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

}
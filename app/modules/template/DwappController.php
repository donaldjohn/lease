<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: DwappController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\template;


use app\models\service\AppEvent;
use app\models\service\AppEventRelation;
use app\models\service\AppList;
use app\models\service\AppType;
use app\models\service\AppUmeng;
use app\modules\BaseController;

class DwappController extends BaseController
{

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 获取app分页数据
     */
    public function PageAction()
    {
        /**
         * 获取url参数
         */
        $app_name = $this->request->getQuery('app_name','string',null,true);
        $app_code = $this->request->getQuery('app_code','string',null,true);
        $app_status = $this->request->getQuery('app_status','int',null,true);
        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);

        $app = $this->templateData->getAppPage($pageNum,$pageSize,$app_name,$app_code,$app_status);

        return  $this->toSuccess($app['data'],$app['meta']);

    }

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 获取app列表数据
     */
    public function listAction()
    {

        $app_code = $this->request->getQuery('event_code','string',null,true);
        $app_type = $this->request->getQuery('event_level','int',null,true);
        $app_status = $this->request->getQuery('event_status','int',1,true);

        $events = AppList::query()
            ->andWhere('app_status = :app_status: and is_delete = 1',['app_status' => $app_status]);
        if ($app_type != null) {
            $events->andWhere('app_type = :app_type:', ['app_type' => $app_type]);
        }

        if ($app_code != null) {
            $events->andWhere('app_code like :app_code:' ,['app_code' => '%'.$app_code.'%']);
        }

        $result = $events->execute()->toArray();
        return $this->toSuccess($result);
    }


    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $app = new AppList();
        $app->assign($json);
        if ($app->save() == false) {
            return $this->toError(500,$app->getMessages()[0]->getMessage());
        }
        return $this->toSuccess($app);
    }


    public function OneAction($id)
    {
        $app = AppList::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前app不存在!');
        }
        return $this->toSuccess($app);
    }


    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $app = AppList::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前app不存在!');
        }
        unset($json['app_name']);
        unset($json['create_time']);
        $app->assign($json);
        if ($app->update() == false) {
            return $this->toError(500,$app->getMessages()[0]->getMessage());
        }
        return $this->toSuccess($app);

    }


    public function StatusAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['app_status'])) {
            return $this->toError(500,'缺少状态参数!');
        }
        $app = AppList::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前app不存在!');
        }

        //禁用需要判断是否有在用的
        if ($json['app_status'] == 2) {
            $umeng = AppUmeng::findFirst(['conditions' => 'app_id = :app_id: and is_delete = 1 and app_status = 1','bind' => ['app_id' => $id]]);
            if ($umeng) {
                return $this->toError(500,'当前app正在被使用,不能禁用!');
            }
        }

        $app->setAppStatus($json['app_status']);
        if ($app->update() == false) {
            return $this->toError(500,$app->getMessages()[0]->getMessage());
        }

        return $this->toSuccess($app);

    }


    public function DeleteAction($id)
    {
        $this->dw_service->begin();
        $app = AppList::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前app不存在!');
        }
        if ($app->getAppStatus() != 2) {
            return $this->toError(500,'当前数据状态不能删除!');
        }

        $umeng = AppUmeng::findFirst(['conditions' => 'app_id = :app_id: and is_delete = 1','bind' => ['app_id' => $id]]);
        if ($umeng) {
            $this->dw_service->rollback();
            return $this->toError(500,'当前app正在被使用,不能禁用!');
        }

        $app->setIsDelete(2);

        if ($app->update() == false) {
            $this->dw_service->rollback();
            return $this->toError(500,$app->getMessages()[0]->getMessage());
        }

        //删除关系数据
        $this->dw_service->query('delete from dw_app_event_relation where app_id = :id',['id' => $id]);
        $this->dw_dispatch->query('delete from dw_driver_event_blacklist where app_id = :id',['id'=> $id]);
        $this->dw_service->commit();
        return $this->toSuccess(true);


    }


    public function EventAction($id)
    {
        $app = AppList::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前app不存在!');
        }
        if ($app->getAppStatus() == 1) {
            return $this->toError(500,'当前数据状态不能设置!');
        }
        if ($app->getIsDelete() != 1) {
            return $this->toError(500,'当前数据已删除!');
        }

        $type = $app->getAppType();

        //and if_show = 1
        $app = AppEvent::query()
            ->columns('app\models\service\AppEvent.id,event_name,event_code,event_level,if_show,parent_id,event_order,tem.notice_type')
            ->leftJoin('app\models\service\MessageTemplate','tem.id = app\models\service\AppEvent.template_id','tem')
            ->LeftJoin('app\models\service\AppEventType','et.event_id = app\models\service\AppEvent.id','et')
            ->LeftJoin('app\models\service\AppType','et.type_id = t.id','t')
            ->andWhere('app\models\service\AppEvent.is_delete = 1 and event_status = 1 and et.type_id = :type_id:',['type_id' => $type])
            ->execute()->toArray();

        $event = AppEventRelation::find(['conditions' => 'app_id = :app_id:','bind' => ['app_id' => $id]])->toArray();
        $events = [];
        foreach ($event as $item ) {
            array_push($events,$item['event_id']);
        }
        $result = ['list' => $app,'events' => $events];
        return $this->toSuccess($result);
    }



    public function UpdateEventAction($id)
    {
        try {
            $this->dw_service->begin();
            $json = $this->request->getJsonRawBody(true);
            if (!isset($json['events']) || !is_array($json['events'])) {
                return  $this->toError(500,'参数格式不正确!');
            }

            $relation = AppEventRelation::find(['conditions' => 'app_id = :app_id:','bind' => ['app_id' => $id]])->toArray();

            foreach ($relation as $item) {
                if (!in_array($item['event_id'],$json['events'])) {
                    AppEventRelation::find(['conditions' => 'app_id = :app_id: and event_id = :event_id:','bind' => ['app_id' => $id,'event_id' => $item['event_id']]])->delete();

                } else {
                    $json['events'] =  array_diff($json['events'],[$item['event_id']]);
                }
            }
            $time = time();
            if (count($json['events']) > 0) {
                foreach($json['events'] as $item) {
                    $appEventRelation = new AppEventRelation();
                    $appEventRelation->setAppId($id);
                    $appEventRelation->setEventId($item);
                    $appEventRelation->setCreateTime($time);
                    if ($appEventRelation->save() == false) {
                        $this->dw_service->rollback();
                        return $this->toError(500,$appEventRelation->getMessages()[0]->getMessage());
                    }
                }
            }
            $this->dw_service->commit();
            return $this->toSuccess(true);

        } catch (\Exception $e) {
            $this->dw_service->rollback();
            return $this->toError(500,$e->getMessage());
        }
    }


    public function AppTypeAction(){
        $type = AppType::find(['conditions' => 'status = :status:','bind' => ['status' => 1]]);
        return $this->toSuccess($type);
    }




}
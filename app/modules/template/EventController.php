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
use app\models\service\AppEventType;
use app\modules\BaseController;

class EventController extends BaseController
{

    public function PageAction()
    {
        $event_name = $this->request->getQuery('event_name','string',null,true);
        $event_code = $this->request->getQuery('event_code','string',null,true);
        $event_status = $this->request->getQuery('event_status','int',null,true);
        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);

        $event = $this->templateData->getEventPage($pageNum,$pageSize,$event_name,$event_code,$event_status);

        return  $this->toSuccess($event['data'],$event['meta']);

    }

    public function ListAction()
    {
        $event_code = $this->request->getQuery('event_code','string',null,true);
        $event_level = $this->request->getQuery('event_level','int',null,true);
        $event_status = $this->request->getQuery('event_status','int',1,true);

        $events = AppEvent::query()
            ->andWhere('event_status = :event_status: and is_delete = 1',['event_status' => $event_status]);
        if ($event_code != null) {
            $events->andWhere('event_code LIKE :event_code:', ['event_code' => '%'.$event_code.'%']);
        }

        if ($event_level != null) {
            $events->andWhere('event_level = :event_level:' ,['event_level' => $event_level]);
        }
        $events->orderBy('event_order asc');

        $result = $events->execute()->toArray();
        return $this->toSuccess($result);

    }


    public function CreateAction()
    {
        try {
            $this->dw_service->begin();
            $json= $this->request->getJsonRawBody(true);

            /**
             * 如果event_level 为 1 event_type 必传
             * 如果event_level 为 2 event_type 和上级相同
             */
            //判断是否存在type
            if (!isset($json['event_level'])) {
                return $this->toError(500,'事件等级必填!');
            }

            if ($json['event_level'] == 1) {
                $json['template_id'] = 0;
                $json['parent_id'] = 0;
            }

            if (!isset($json['parent_id'])) {
                return $this->toError(500,'上级事件必填!');
            }


            if(!isset($json['event_type']) && $json['event_level'] == 1) {
                return $this->toError(500,'事件类型必填!');
            }

            //根据上级ID获取对应的类型
            if ($json['event_level'] == 2) {
                if ($json['parent_id'] > 0) {
                    $relation = AppEventType::find(['conditions' => 'event_id = :event_id:','bind' => ['event_id' => $json['parent_id']]])->toArray();
                    $json['event_type'] = [];
                    foreach ($relation as $item) {
                        $json['event_type'][]  = $item['type_id'];
                    }
                } else {
                    return $this->toError(500,'上级ID必填!');
                }
            }


            //判断是否存在type
            if(!isset($json['event_type']) || !is_array($json['event_type'])) {
                return $this->toError(500,'缺少必要参数或参数格式不正确!');
            }

            $event = new AppEvent();
            $event->assign($json);
            if ($event->save() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$event->getMessages()[0]->getMessage());
            }

            $time = time();
            foreach ($json['event_type'] as $id) {
                $type = new AppEventType();
                $type->setEventId($event->getId());
                $type->setTypeId($id);
                $type->setCreateTime($time);
                if ($type->save() == false) {
                    $this->dw_service->rollback();
                    return $this->toError(500,$type->getMessages()[0]->getMessage());
                }
            }
            $this->dw_service->commit();
            $event->event_type = $json['event_type'];
            return $this->toSuccess($event);

        } catch (\Exception $e) {
            $this->dw_service->rollback();
            return $this->toError(500,$e->getMessage());
        }
    }

    public function OneAction($id)
    {

        $app = $this->modelsManager->createBuilder()
            ->columns('e.id,e.event_name,e.event_code,e.event_level,e.if_show,e.parent_id,e.event_order,e.template_id,e.event_status,
            e.is_delete,e.event_text,FROM_UNIXTIME(e.create_time) as create_time,
            e1.event_name as parent_event_name,e1.event_code as parent_event_code,t.template_sn,t.template_name,GROUP_CONCAT(ty.id) as event_type')
            ->addFrom('app\models\service\AppEvent','e')
            ->leftJoin('app\models\service\AppEvent','e.parent_id = e1.id','e1')
            ->leftJoin('app\models\service\MessageTemplate','e.template_id = t.id','t')
            ->leftJoin('app\models\service\AppEventType','e.id = et.event_id','et')
            ->leftJoin('app\models\service\AppType','ty.id = et.type_id','ty')
            ->andWhere('e.id = :id:',['id' => $id])
            ->getQuery()->getSingleResult();
        return $this->toSuccess($app);
    }

    public function UpdateAction($id)
    {
        try {
            $this->dw_service->begin();
            $json= $this->request->getJsonRawBody(true);


            //判断是否存在type
            if (!isset($json['event_level'])) {
                return $this->toError(500,'事件等级必填!');
            }
            if ($json['event_level'] == 1) {
                $json['template_id'] = 0;
                $json['parent_id'] = 0;
            }
            if (!isset($json['parent_id'])) {
                return $this->toError(500,'上级事件必填!');
            }


            if(!isset($json['event_type']) && $json['event_level'] == 1) {
                return $this->toError(500,'事件类型必填!');
            }

            //根据上级ID获取对应的类型
//            if ($json['event_level'] == 2) {
//                if ($json['parent_id'] > 0) {
//                    $relation = AppEventType::find(['conditions' => 'event_id = :event_id:','bind' =>['event_id' => $json['parent_id']]])->toArray();
//                    $json['event_type'] = [];
//                    foreach ($relation as $item) {
//                        $json['event_type'][]  = $item['type_id'];
//                    }
//                } else {
//                    return $this->toError(500,'上级ID必填!');
//                }
//            }
//
//            $return_type = $json['event_type'];


            //判断是否存在type
            if(!isset($json['event_type']) || !is_array($json['event_type'])) {
                return $this->toError(500,'缺少必要参数或参数格式不正确!');
            }

            $event = AppEvent::findFirst($id);

            unset($json['create_time']);
            unset($json['event_code']);
            unset($json['event_level']);
            $event->assign($json);
            if ($event->update() == false) {
                $this->dw_service->rollback();
                return $this->toError(500,$event->getMessages()[0]->getMessage());
            }

            if ($event->getEventLevel() == 1)
            {

                //原有数据
                $evntTypes = AppEventType::find(['conditions' => 'event_id = :event_id:','bind' =>['event_id' => $id]])->toArray();
                $deleteTypes = [];
                foreach ($evntTypes as $item) {
                    //旧删新增
                    if (!in_array($item['type_id'], $json['event_type'])) {
                        $deleteTypes[] = $item['type_id'];
                    } else {
                        $json['event_type'] = array_diff($json['event_type'], [$item['type_id']]);
                    }
                }
                $addTypes = $json['event_type'];

                $ids = [$id];
                $children = AppEvent::find(['conditions' => 'parent_id = :parent_id:','bind' => ['parent_id' => $id]])->toArray();
                foreach($children as $item) {
                    $ids[] = $item['id'];
                }



                if (count($deleteTypes) > 0) {
                    // 删除当前的
                    $phql = "delete from app\models\service\AppEventType where type_id IN ({types:array}) and event_id IN ({ids:array})";
                    $robots = $this->modelsManager->executeQuery(
                        $phql,
                        ['types' => $deleteTypes,'ids' => $ids]
                    );
                }

                $insertData = [];
                $time = time();
                if (count($addTypes) > 0) {
                        foreach($addTypes as $item) {
                            foreach($ids as $id) {
                                $list = [];
                                $list['type_id'] = $item;
                                $list['event_id'] = $id;
                                $list['create_time'] = $time;
                                $insertData[] = $list;
                            }
                        }
                        $appEventType = new AppEventType();
                        if (count($insertData) > 0) {
                           $sql =  $appEventType->batch_insert($insertData);
                           $this->dw_service->query($sql);
                        }
                }

            }
            $this->dw_service->commit();
            return $this->toSuccess($event);

        } catch (\Exception $e) {
            $this->dw_service->rollback();
            return $this->toError(500,$e->getMessage());
        }

    }


    //TODO::禁用判断.使用中不能禁用
    public function StatusAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['event_status'])) {
            return $this->toError(500,'缺少状态参数!');
        }
        $this->dw_service->begin();
        $app = AppEvent::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前事件不存在!');
        }
        if ($json['event_status'] == 2) {
            //判断是否又使用的.
            $appEventRelation = AppEventRelation::findFirst(['conditions' => 'event_id = :event_id:','bind' => ['event_id' => $id]]);
            if ($appEventRelation) {
                return $this->toError(500,'当前事件正在使用,禁止禁用!');
            }
        }
        //判断当前level=2 的上级是否已经启用
        if ($app->getEventLevel() == 2) {
            if ($json['event_status'] == 1) {
                $parentApp = AppEvent::findFirst($app->getParentId());
                if ($parentApp->getEventStatus() == 2) {
                    return $this->toError(500,'上级事件禁用中,请先启用!');
                }
            }
        }


        $app->setEventStatus($json['event_status']);
        if ($app->update() == false) {
            $this->dw_service->rollback();
            return $this->toError(500,$app->getMessages()[0]->getMessage());
        }

        //如果当前是1级 启用禁用都要更新下级状态
        if ($app->getEventLevel() == 1) {
            $childrenEvents = AppEvent::find(['conditions' => 'parent_id = :parent_id: and is_delete = 1','bind' => ['parent_id' => $id]])->toArray();

            foreach($childrenEvents as $item) {
                $event = AppEvent::findFirst($item['id']);
                $event->setEventStatus($json['event_status']);
                if ($event->update() == false) {
                    $this->dw_service->rollback();
                    return $this->toError(500,$event->getMessages()[0]->getMessage());
                }
            }
        }


        $this->dw_service->commit();
        return $this->toSuccess($app);


    }


    public function DeleteAction($id)
    {
        $app = AppEvent::findFirst($id);
        if ($app == false) {
            return $this->toError(500,'当前事件不存在!');
        }
        if ($app->getEventStatus() != 2) {
            return $this->toError(500,'当前数据状态不能删除!');
        }

        $app->setIsDelete(2);

        if ($app->update() == false) {
            return $this->toError(500,$app->getMessages()[0]->getMessage());
        }

        return $this->toSuccess(true);

    }




    public function OrderAction()
    {
        $used = [];
        $appEvent = AppEvent::find()->toArray();
        foreach($appEvent as $item) {
            $used[] = $item['event_order'];
        }
        for( $i=1;$i<100;$i++){
            $result[] = $i;
        }

        $result = array_merge(array_diff($result,$used));

        return $this->toSuccess($result);


    }









}
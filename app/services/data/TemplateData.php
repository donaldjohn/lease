<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: TaskData.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\services\data;



use app\models\service\MulticodeTask;
use app\services\auth\Authentication;
use Phalcon\Paginator\Adapter\QueryBuilder;

class TemplateData extends BaseData
{

    /**
     * @param $pageNum
     * @param $pageSize
     * @param null $app_name
     * @param null $app_code
     * @param null $app_status
     * @return array
     */
    public function getAppPage($pageNum,$pageSize,$app_name = null, $app_code = null, $app_status = null)
    {

        $builder = $this->modelsManager->createBuilder()
            ->columns('a.id,a.app_name,a.app_code,a.app_status,a.app_type,a.is_delete,FROM_UNIXTIME(a.create_time) as create_time,FROM_UNIXTIME(a.update_time) as update_time,t.name as app_type_name')
            ->addFrom('app\models\service\AppList','a')
            ->leftJoin('app\models\service\AppType','a.app_type = t.id','t')
            ->andWhere('a.is_delete = 1');
        if ($app_name != null) {
            $builder->andWhere('a.app_name like :app_name:',['app_name' => '%'.$app_name.'%']);
        }
        if ($app_code != null) {
            $builder->andWhere('a.app_code like :app_code:',['app_code' => '%'.$app_code.'%']);
        }
        if ($app_status != null) {
            $builder->andWhere('a.app_status like :app_status:',['app_status' => $app_status]);
        }
        $builder->orderBy("t.create_time desc");
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();

        $result = $this->dataIntegration($pages);
        return $result;
    }




    public function getEventPage($pageNum,$pageSize,$event_name = null,$event_code = null,$event_status = null)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('e.id,e.event_name,e.event_code,e.event_level,e.if_show,e.parent_id,e.event_order,e.template_id,e.event_status,e.is_delete,e.event_text,FROM_UNIXTIME(e.create_time) as create_time,e1.event_name as parent_event_name,e1.event_code as parent_event_code')
            ->addFrom('app\models\service\AppEvent','e')
            ->leftJoin('app\models\service\AppEvent','e.parent_id = e1.id','e1')
            ->andWhere('e.is_delete = 1');
        if ($event_name != null) {
            $builder->andWhere('e.event_name like :event_name:',['event_name' => '%'.$event_name.'%']);
        }
        if ($event_code != null) {
            $builder->andWhere('e.event_code like :event_code:',['event_code' => '%'.$event_code.'%']);
        }
        if ($event_status != null) {
            $builder->andWhere('e.event_status like :event_status:',['event_status' => $event_status]);
        }
        $builder->orderBy("e.create_time desc");
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();

        $result = $this->dataIntegration($pages);
        return $result;

    }


    public function getUmengPage($pageNum,$pageSize,$app_name = null,$app_code = null,$app_status = null)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('u.id,u.umeng_name,u.app_id,u.package_name,u.app_type,u.appkey,u.mastersecret,u.app_status,FROM_UNIXTIME(u.create_at) as create_at,FROM_UNIXTIME(u.update_at) as update_at
            ,a.app_name,a.app_code')
            ->addFrom('app\models\service\AppUmeng','u')
            ->leftJoin('app\models\service\AppList','a.id = u.app_id','a')
            ->andWhere('u.is_delete = 1');
        if ($app_name != null) {
            $builder->andWhere('a.app_name like :app_name:',['app_name' => '%'.$app_name.'%']);
        }
        if ($app_code != null) {
            $builder->andWhere('a.app_code like :app_code:',['app_code' => '%'.$app_code.'%']);
        }
        if ($app_status != null) {
            $builder->andWhere('u.app_status = :app_status:',['app_status' => $app_status]);
        }
        $builder->orderBy("u.create_at desc");
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();

        $result = $this->dataIntegration($pages);
        return $result;

    }









}
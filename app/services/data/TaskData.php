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

class TaskData extends BaseData
{

    const STATUS_DONE = 4;
    public function getTaskPage($pageNum,$pageSize,$indexText = null,$done = null,$create_user_id = null,$userId = null)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('t.id,t.task_code,t.order_num,t.product_id,t.product_sku_relation_id,t.product_info,t.task_type,t.task_num,t.task_completed_num,t.task_status,FROM_UNIXTIME(t.create_at) as create_at,FROM_UNIXTIME(t.update_at) as update_at')
            ->addFrom('app\models\service\MulticodeTask','t')
            ->leftJoin('app\models\service\MulticodeTaskUser','t.id = tu.task_id','tu');

        if($create_user_id != null) {
            $builder->andWhere('t.create_user_id = :user_id:',['user_id' => $create_user_id]);
        }

        if ($indexText != null) {
            $builder->andWhere("t.order_num like :search: or t.product_info like :search: or t.task_code like :search:", array( 'search' => '%'.$indexText.'%'));
        }
        if ($done == true) {
            $builder->andWhere("t.task_status = :status:", array( 'status' => self::STATUS_DONE));
        }
        if ($done === false) {
            $builder->andWhere("t.task_status > 1 and t.task_status < :status:", array( 'status' => self::STATUS_DONE));
        }

        if ($userId != null) {
            $builder->andWhere('tu.user_id = :user_id:',array('user_id' => $userId));
        }
        $builder->orderBy("t.create_at desc");
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


    public function getTaskDetailPage($taskId,$pageNum,$pageSize,$indexText = null)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('td.id,td.task_id,td.qrcode,td.udid,td.vin,td.plate_num,FROM_UNIXTIME(td.sweep_time) as sweep_time,FROM_UNIXTIME(td.create_at) as create_at,FROM_UNIXTIME(td.update_at) as update_at')
            ->addFrom('app\models\service\MulticodeTaskDetail','td')
            ->where('td.task_id = :task_id:',array('task_id' => $taskId));

        if ($indexText != null) {
            $builder->andWhere("td.qrcode like :search: or td.udid like :search: or td.vin like :search: or td.plate_num like :search:", array( 'search' => '%'.$indexText.'%'));
        }
        $builder->orderBy("td.create_at desc");
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


    public function getTask($id)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('t.id,t.task_code,t.order_num,t.product_id,t.product_sku_relation_id,t.product_info,t.task_type,t.task_num,t.task_completed_num,t.task_status,FROM_UNIXTIME(t.create_at) as create_at,FROM_UNIXTIME(t.update_at) as update_at,tu.user_id')
            ->addFrom('app\models\service\MulticodeTask','t')
            ->leftJoin('app\models\service\MulticodeTaskUser','t.id = tu.task_id','tu')
            ->andWhere('t.id = :id:',['id' => $id])
            ->getQuery()
            ->getSingleResult();
        return $builder;
    }


    //整理数据为insert做准备
    public function setInsetTaskDetailData(MulticodeTask $task,array $json) {
        $result = [];
        $time =time();
        if (count($json) == 0) {
            return false;
        }

        foreach($json['multicode'] as $item) {
            $data = [];
            $data['task_id'] = $task->getId();
            if (isset($item['qrcode'])) {
                $data['qrcode'] = $item['qrcode'];
            } else {
                return false;
            }
            if (isset($item['udid'])) {
                $data['udid'] = $item['udid'];
            } else {
                return false;
            }

            if (isset($item['vin'])) {
                $data['vin'] = $item['vin'];
            } else {
                return false;
            }

            if ($task->getTaskType() == 2 && !isset($item['plate_num'])) {
                return false;
            }

            if ($task->getTaskType() == 2 && isset($item['plate_num'])) {
                $data['plate_num'] = $item['plate_num'];
            }

            if ($task->getTaskType() == 2 && !isset($data['plate_num'])) {
                return false;
            }

            if (isset($item['sweep_time'])) {
                $data['sweep_time'] = $item['sweep_time'];
            } else {
                return false;
            }
            $data['create_at'] = $time;
            $result[] = $data;
        }
        return $result;
    }


    /**
     * @param MulticodeTask $task
     * @param array $json
     * @return array|bool
     * 整理数据为insert做准备
     *
     * 批量插入验证数据唯一性
     * qrcode 唯一
     * udid加device_model_id 唯一
     *
     */
    public function setInsetVehicleData(MulticodeTask $task,array $json) {
        $result = [];
        $time =time();
        if (count($json) == 0) {
            return false;
        }

        $qrcodeList = [];
        $udidList = [];
        $vinList = [];

        foreach($json['multicode'] as $item) {
            $data = [];
            if (isset($item['qrcode'])) {
                if (!in_array($item['qrcode'],$qrcodeList)) {
                    $data['bianhao'] = $item['qrcode'];
                    $qrcodeList[] = $item['qrcode'];
                } else {
                    return false;
                }
            } else {
                return false;
            }
            if (isset($item['device_model_id'])) {
                $data['device_model_id'] = $item['device_model_id'];
            } else {
                return false;
            }
            if (isset($item['udid'])) {
                if (!in_array($item['device_model_id'].$item['udid'],$qrcodeList)) {
                    $data['udid'] = $item['udid'];
                    $udidList[] = $item['device_model_id'].$item['udid'];
                } else {
                    return false;
                }
            } else {
                return false;
            }

            if (isset($item['vin'])) {
                if (!in_array($item['vin'],$vinList)) {
                    $data['vin'] = $item['vin'];
                    $vinList[] = $item['vin'];
                } else {
                    return false;
                }
            } else {
                return false;
            }

            if ($task->getTaskType() == 2 && !isset($item['plate_num'])) {
                return false;
            }

            if ($task->getTaskType() == 2 && isset($item['plate_num'])) {
                $data['plate_num'] = $item['plate_num'];
            }

            $data['pull_time'] = $time;
            $data['mileage_time'] = $time;
            $data['create_time'] = $time;
            //$data['product_id'] = $task->getProductId();
            //$data['product_sku_relation_id'] = $task->getProductSkuRelationId();
            $data['vehicle_model_id'] = $task->getProductId();
            $data['data_source'] = 1;
            $result[] = $data;
        }
        return $result;
    }










}
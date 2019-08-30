<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\exam;

use app\models\dispatch\DriverLicence;
use app\models\dispatch\RegionDrivers;
use app\models\phpems\X2Basic;
use app\models\users\Company;
use app\models\users\Institution;
use app\modules\BaseController;
use Phalcon\Paginator\Adapter\QueryBuilder;

/**
 * Class IndexController
 * @package app\modules\exam
 * 临时
 */
class IndexController extends BaseController
{
    /**
     * 获取考场列表
     * x2basic
     */
    public function ListAction()
    {
        //获取用户省级区域 子系统查询主系统company
        //$ins = Institution::findFirst($this->authed->insId);

        $company = Company::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' =>$this->authed->insId]]);

        if ($company == false) {
            return $this->toError(500,'用户机构有问题！');
        }

        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);

        $builder = $this->modelsManager->createBuilder()
            ->columns('b.basicid as id,b.basic,b.basicsubjectid,b.basicexam,s.subject,b.usednumber')
            ->addFrom('app\models\phpems\X2Basic','b')
            ->leftJoin('app\models\phpems\X2Subject','b.basicsubjectid = s.subjectid','s')
            ->andWhere('b.basicareaid = :areaId: and b.basicclosed = 0',['areaId' => $company->getProvinceId()])
            ->orderBy('b.basicid desc');
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();

        $result = $this->dataIntegration($pages);

        foreach($result['data'] as $key => $item) {
            $value = unserialize($item['basicexam']);
            unset($result['data'][$key]['basicexam']);

            if (isset($value['opentime']['start'])) {
                $result['data'][$key]['starttime'] = date("Y-m-d H:i:s",$value["opentime"]["start"]);
            }

            if (isset($value['opentime']['end'])) {
                $result['data'][$key]['endtime'] = date("Y-m-d H:i:s",$value["opentime"]["end"]);
            }

            if (isset($value['countnumber'])) {
                $result['data'][$key]['countnumber'] = $value['countnumber'];
            } else {
                $result['data'][$key]['countnumber'] = 100;
            }

        }

        return  $this->toSuccess($result['data'],$result['meta']);
    }





    /**
     * 历史成绩查询
     */
    public function HistoryAction()
    {
        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);
        $realName = $this->request->getQuery('realName','string',null,true);
        $licence = $this->request->getQuery('licence','string',null,true);

        $builder = $this->modelsManager->createBuilder()
            ->columns('eh.ehid,eh.ehexamid,eh.ehexam,eh.ehbasicid,eh.ehtime,eh.ehscore,eh.ehuserid,eh.ehusername,FROM_UNIXTIME(eh.ehstarttime) as ehstarttime,FROM_UNIXTIME(eh.ehendtime) as ehendtime,eh.ehispass,b.batch_code,u.username,u.usertruename,u.usergender,ba.basic,s.subject')
            ->addFrom('app\models\phpems\X2Examhistory','eh')
            ->leftJoin('app\models\phpems\X2User','u.userid = eh.ehuserid','u')
            ->leftJoin('app\models\phpems\X2Basic','ba.basicid = eh.ehbasicid','ba')
            ->leftJoin('app\models\phpems\X2Openbasics','ob.obuserid = u.userid and ob.obbasicid = ba.basicid ','ob')
            ->leftJoin('app\models\phpems\X2Batch','ob.batch_id = b.id','b')
            ->leftJoin('app\models\phpems\X2Subject','s.subjectid = ba.basicsubjectid','s')
            ->andWhere('b.ins_id = :ins_id: and eh.ehstatus = 1',['ins_id' => $this->authed->insId]);


        if ($realName != null) {
            $builder->andWhere('u.usertruename like :realName:',['realName' => '%'.$realName.'%']);
        }

        if ($licence != null) {
            $builder->andWhere('u.username like :userName:',['userName' => '%'.$licence.'%']);
        }


       // $builder->groupBy('eh.ehid');
        $builder->orderBy('eh.ehid desc')
            ->groupBy('eh.ehid');
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();
        $result = $this->dataIntegration($pages);
        return  $this->toSuccess($result['data'],$result['meta']);
    }

    /**
     * 骑手驾照列表
     */
    public function LicenceAction()
    {

        /**
         * 当前机构为主系统机构
         * 根据主系统机构id获取子系统机构ID
         */

        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);
        $realName = $this->request->getQuery('realName','string',null,true);
        $licence = $this->request->getQuery('licence','string',null,true);

        $builder = $this->modelsManager->createBuilder()
            ->columns('dl.id,dl.driver_id,dl.has_licence,dl.licence_num,dl.valid_starttime,dl.valid_starttime,
            dl.valid_endtime,dl.licence_score,dl.get_time,dl.front_img,IF(dl.front_img <> null or dl.front_img <> "","1","2") as licence_img_status,
            dl.back_img,d.real_name,d.sex,dl.ins_id,dl.ins_name,d.identification_photo,dl.is_send, rd.ins_id as companyId')
            ->addFrom('app\models\dispatch\DriverLicence','dl')
            ->leftJoin('app\models\dispatch\Drivers','d.id = dl.driver_id','d')
            ->leftJoin('app\models\dispatch\RegionDrivers','rd.driver_id = dl.driver_id','rd');
        if($this->authed->userType == 3) {
            $builder->andWhere('dl.ins_id = :ins_id:',['ins_id' => $this->authed->insId]);
        } else {
            $builder->andWhere('rd.ins_id = :ins_id:',['ins_id' => $this->authed->insId]);
        }


        if ($realName != null) {
            $builder->andWhere('d.real_name = :real_name:',['real_name' => $realName]);
        }
        if ($licence != null) {
            $builder->andWhere('dl.licence_num = :licence:',['licence' => $licence]);
        }
        $builder->orderBy("dl.get_time desc");
        $paginator = new QueryBuilder(
            array(
                "builder" => $builder,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();

        $result = $this->dataIntegration($pages);
        foreach($result['data'] as $key => $item) {

            if (isset($item['get_time']) && $item['get_time'] > 0) {
                $result['data'][$key]['get_time'] = date('Y-m-d H:i:s',$item['get_time']) ;
            }
            if (isset($item['valid_starttime']) && $item['valid_starttime'] > 0) {
                $result['data'][$key]['valid_starttime'] = date('Y-m-d H:i:s',$item['valid_starttime']) ;
            }
            if (isset($item['valid_endtime']) && $item['valid_endtime'] > 0) {
                $result['data'][$key]['valid_endtime'] = date('Y-m-d H:i:s',$item['valid_endtime']) ;
            }
            $result['data'][$key]['companyName'] = '';
            if (isset($item['companyId'])) {
                $company = Company::findFirst(['ins_id = :ins_id:',
                    'bind' => [
                        'ins_id' => $item['companyId'],
                    ],]);
                if ($company) {
                    $result['data'][$key]['companyName'] = $company->company_name;
                }
            }
        }
        return  $this->toSuccess($result['data'],$result['meta']);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 获取驾照详情
     */
    public function LicenceDetailAction($id) {
        $builder = $this->modelsManager->createBuilder()
            ->columns('dl.id,dl.driver_id,dl.has_licence,dl.licence_num,dl.valid_starttime,dl.valid_starttime,dl.valid_endtime,dl.licence_score,dl.get_time,dl.front_img,dl.back_img,d.real_name,d.sex,dl.ins_id,dl.ins_name,d.identification_photo,dl.is_send')
            ->addFrom('app\models\dispatch\DriverLicence','dl')
            ->leftJoin('app\models\dispatch\Drivers','d.id = dl.driver_id','d')
            ->leftJoin('app\models\dispatch\RegionDrivers','rd.driver_id = dl.driver_id','rd')
            ->andWhere('dl.id = :id:',['id' => $id])
            ->getQuery()
            ->getSingleResult();
        return $this->toSuccess($builder);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * 更新驾照图片
     */
    public function UpdateLicenceAction($id) {
        $driverLicence = DriverLicence::findFirst($id);
        if ($driverLicence == false) {
            return $this->toError(500,"驾照不存在！");
        }
        $json = $this->request->getJsonRawBody(true);
        if (isset($json['front_img'])) {
            $driverLicence->setFrontImg($json['front_img']);
        }
        if (isset($json['back_img'])) {
            $driverLicence->setBackImg($json['back_img']);
        }

        if ($driverLicence->update() == false) {
            return $this->toError(500,'上传数据格式不正确!');
        } else {
            return $this->toSuccess(true);
        }

    }


    /**
     * @param $id
     * 验证骑手是否发送
     */
    public function checkLicenceIsSendAction($id)
    {
        /**
         * 验证权限
         */
        $driverLicence = DriverLicence::findFirst(['conditions' => 'driver_id = :driver_id:','bind' => ['driver_id' => $id]]);
        if (!$driverLicence) {
            return $this->toError(500,"骑手驾照不存在！");
        }
        if ($driverLicence->getIsSend() == 1) {
            return $this->toSuccess(['status' => true]);
        } else {
            return $this->toSuccess(['status' => false]);
        }

    }


    /**
     * @param $id
     * 更新骑手驾照是否发送字段
     */
    public function updateLicenceIsSendAction()
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['ids'])) {
            return $this->toError(500,"参数不正确！");
        }

        $this->dw_dispatch->begin();
        foreach($json['ids'] as $id) {
            $driverLicence = DriverLicence::findFirst(['conditions' => 'driver_id = :driver_id:','bind' => ['driver_id' => $id]]);
            if (!$driverLicence) {
                $this->dw_dispatch->rollback();
                return $this->toError(500,"骑手驾照不存在！");
            }
            /**
             * 验证权限
             */
            if($this->authed->userType == 3) {
                if ($this->authed->insId != $driverLicence->getInsId()) {
                    $this->dw_dispatch->rollback();
                    return $this->toError(500,"用户无权限！");
                }
            } else {
                $regionDriver = RegionDrivers::findFirst(['conditions' => 'driver_id = :driver_id: and ins_id = :ins_id:','bind' => ['driver_id' => $id,'ins_id' => $this->authed->insId]]);
                if (!$regionDriver) {
                    $this->dw_dispatch->rollback();
                    return $this->toError(500,"用户无权限！");
                }
            }
            $driverLicence->setIsSend(1);
            if ($driverLicence->update() == false) {
                $this->dw_dispatch->rollback();
                $this->logger->error($driverLicence->getMessages()[0]->getMessage());
                return $this->toError(500,"骑手驾照更新失败！");
            }
        }
        $this->dw_dispatch->commit();
        return $this->toSuccess(true);
    }


}
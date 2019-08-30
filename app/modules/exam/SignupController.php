<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\exam;

use app\models\dispatch\Drivers;
use app\models\phpems\X2Basic;
use app\models\phpems\X2Batch;
use app\models\phpems\X2Openbasics;
use app\models\phpems\X2User;
use app\models\users\Institution;
use app\models\users\User;
use app\modules\auth\TrafficpoliceController;
use app\modules\BaseController;
use Phalcon\Paginator\Adapter\QueryBuilder;
class SignupController extends BaseController
{

    /**
     * 报名数据
     * 1。已报名
     * 2。未报名（过滤已获得驾照骑手） 2018.11.14 需求变更   骑手驾照分数 <= 0
     * x2_user
     */
    public function ListAction($basicId)
    {

        $basic = X2Basic::findFirst($basicId);
        $examDate = unserialize($basic->getBasicexam());
        $sTime = $examDate['opentime']['start'];
        $eTime = $examDate['opentime']['end'];
        /**
         * 未报名 查询快递协会对应骑手insID（region_drivers 去除driver_licence）
         *
         */
        /**
         * 当前机构为主系统机构
         * 根据主系统机构id获取子系统机构ID
         *
         * TODO:: hack 系统设计有问题
         */
        //$ins = Institution::findFirst(['conditions' => 'parent_id = :parent_id:','bind' => ['parent_id' => $this->authed->insId]]);


        $builder = $this->modelsManager->createBuilder()
            ->columns('d.id,d.user_name,d.identify,d.real_name,d.sex,l.id as licenceId,l.licence_score')
            ->addFrom('app\models\dispatch\Drivers','d')
            ->rightJoin('app\models\dispatch\RegionDrivers','d.id = rd.driver_id','rd')
            ->leftJoin('app\models\dispatch\DriverLicence','l.driver_id = d.id','l')
            ->andWhere('rd.ins_id = :ins_id: and status = 1',['ins_id' => $this->authed->insId])
            ->orderBy('d.create_time desc')
            ->getQuery()
            ->execute();
        $result = ['in' => [],'notIn' => []];
        $all = [];
        $allIds = [];
        foreach($builder as $item) {
            if ($item['licenceId'] == null || $item['licence_score'] <= 0) {
                $all[]= $item;
                $allIds[] = $item['id'];
            }
        }

        /**
         * 已报名 phpems
         *（x2user x2openbasics）
         */
        $inBuilder = $this->modelsManager->createBuilder()
            ->columns('d.userid as id,d.usertruename as user_name,d.usergender as sex,d.username as identify')
            ->addFrom('app\models\phpems\X2Openbasics','ob')
            ->leftJoin('app\models\phpems\X2User','d.userid = ob.obuserid','d')
            ->andWhere('ob.obbasicid = :obbasicid: and d.ins_id = :insId:',['obbasicid' => $basicId,'insId' => $this->authed->insId])
            ->getQuery()
            ->execute();
        $result['in'] = $inBuilder;
        $in = [];
        foreach($inBuilder as $item) {
            $in[] = $item['id'];
        }
        /**
         * 减去时间上有冲突的
         */
        $openBuilder = $this->modelsManager->createBuilder()
            ->columns("bh.id,b.basicexam,ob.obuserid,b.basicid")
            ->addFrom('app\models\phpems\X2Batch','bh')
            ->leftJoin('app\models\phpems\X2Basic','b.basicid = bh.basic_id','b')
            ->leftJoin('app\models\phpems\X2Openbasics','ob.obbasicid = bh.basic_id','ob')
            ->where('bh.ins_id = :insId:',['insId'=>$this->authed->insId])
            ->inWhere('ob.obuserid',$allIds)
            ->getQuery()
            ->execute()
            ->toArray();
        /**
         * 比较时间段是否冲突
         * 开始时间
         */
        foreach ($openBuilder as $item) {

            if ($item['obuserid'] != $basicId) {
                $examDate = unserialize($item['basicexam']);
                $newStartTime = $examDate['opentime']['start'];
                $newEndTime = $examDate['opentime']['end'];
                if (($newStartTime>=$sTime && $newStartTime < $eTime ) || ($newEndTime > $sTime && $newEndTime <= $eTime) || ($newStartTime<$sTime && $newEndTime > $eTime)) {
                    if (!in_array($item['obuserid'],$in)) {
                        $in[] = $item['obuserid'];
                    }
                }
            }
        }

        foreach($all as $item) {
            if (!in_array($item['id'],$in)) {
                $result['notIn'][] = $item;
            }
        }

        return $this->toSuccess($result);
    }




    /**
     * 创建报名数据 x2_user  x2_openbasics
     */
    public function CreateAction($basicId)
    {

        $basic = X2Basic::findFirst($basicId);
        if (!isset($basic)) {
            return $this->toError(500, '未找到对应考场！');
        }
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['userIds']) || !is_array($json['userIds'])) {
            return $this->toError(500, '参数格式不正确！');
        }

        //获取当前考场人数
        $b = unserialize($basic->getBasicexam());
        if(isset($b['countnumber']) && $b['countnumber'] != 0) {
            if($basic->getUsednumber() >= $b['countnumber']){
                return $this->toError(500, '本考场已报满，请下次再报名');
            }
            $newSum = $basic->getUsednumber() + count($json['userIds']);
            if ($newSum > $b['countnumber']) {
                $lessNum = $newSum - $b['countnumber'];
                return $this->toError(500, '报名人数超出限制，请去掉 '.$lessNum .' 人');
            }
        }
//        $ob = X2Openbasics::count(['conditions' => 'obbasicid = :obbasicid:', 'bind' => ['obbasicid' => $basicId]]);
//        $newSum = $ob + count($json['userIds']);
//        if ($b['examnumber'] != 0 && $newSum > $b['examnumber']) {
//            return $this->toError(500, '超出考场人数！');
//        }

        /**
         * 存 发证单位 所属市级快递协会
         */
//        $models = $this->modelsManager->createBuilder()
//            ->columns('ir.ins_id,a.association_name')
//            ->addFrom('app\models\users\InstitutionRelation','ir')
//            ->leftJoin('app\models\users\Institution','ir.ins_id = i.id','i')
//            ->join('app\models\users\Association','a.ins_id = ir.ins_id','a')
//            ->where("ir.relation_id = :id: and i.type_id = 3",['id' => $this->authed->insId])
//            ->getQuery()
//            ->getSingleResult();

        $models = $this->modelsManager->createBuilder()
            ->columns('i.id as ins_id,a.association_name')
            ->addFrom('app\models\users\Institution','i0')
            ->leftJoin('app\models\users\Institution','i0.parent_id = i.id','i')
            ->join('app\models\users\Association','a.ins_id = i.id','a')
            ->where("i0.id = :id: and i.type_id = 3",['id' => $this->authed->insId])
            ->getQuery()
            ->getSingleResult();
        if ($models == false) {
            return $this->toError(500, '用户没有对应的市级快递协会');
        }


        //确保所有用户导入考试系统
        foreach ($json['userIds'] as $item) {
            //获取当前数据
            $user = Drivers::findFirst($item);
            if ($user == false || $user->status != 1) {
                return $this->toError(500, '上传用户数据有误！');
            }

            //判断phpems是否已经存在
            $emsUser = X2User::findFirst($item);
            if ($emsUser == false) {
                //考试系统没有用户,新增用户信息
                $emsUser = new X2User();
                $emsUser->setInsId($this->authed->insId);
                $emsUser->setUsername($user->identify);
                $emsUser->setUseremail($user->identify . '@dewin.com');
                $emsUser->setuserid($user->id);
                $password = substr($user->identify, -6);
                //$pw = $this->security->hash($password);
                $emsUser->setUserpassword(md5($password));
                $emsUser->setUsercoin(0);
                $emsUser->setUsergroupid(8);
                $emsUser->setUsertruename($user->real_name);
                $emsUser->setParentInsId($models->ins_id);
                $emsUser->setParentInsName($models->association_name);
                if ($user->sex == 1) {
                    $sex = '男';
                } else {
                    $sex = '女';
                }
                $emsUser->setUsergender($sex);
                if ($emsUser->save() == false) {
                    return $this->toError(500, '操作失败！');
                }
            } else {
                //更新数据
                $emsUser->setUsertruename($user->real_name);
                $emsUser->setUsername($user->identify);
                $emsUser->setParentInsId($models->ins_id);
                $emsUser->setParentInsName($models->association_name);
                if ($emsUser->update() == false) {
                    return $this->toError(500, '操作失败！');
                }
            }
        }
        $this->phpems->begin();
        /**
         * 生成批次
         */
        $batch = new X2Batch();
        $user = User::findFirst($this->authed->userId);
        $code = $user->getUserName() . time()."001";
        $batch->setBatchCode($code);
        $batch->setExamId($basicId);
        $batch->setSubjectId($basic->getBasicsubjectid());
        $batch->setPassUserNum(0);
        $batch->setCreateTime(time());
        $batch->setBasicId($basicId);
        $batch->setBatchNum(count($json['userIds']));
        $batch->setInsId($this->authed->insId);
        if ($batch->save() == false) {
            $this->phpems->rollback();
            return $this->toError(500, '保存失败！');
        }
        $insetData = [];
        //用户匹配考场
        foreach ($json['userIds'] as $item) {
            $list = [];
            $list['obuserid'] = $item;
            $list['obbasicid'] = $basicId;
            $list['obtime'] = time();
            $list['obendtime'] = time() + 86400 * 60;
            $list['batch_id'] = $batch->getId();
            $insetData[] = $list;
        }
        $openbasic = new X2Openbasics();
        $sql = $openbasic->batch_insert($insetData);
        $result = $this->phpems->query($sql);

        $insertNumber = count($json['userIds']);
        $basicSql = 'update x2_basic set usednumber = usednumber + '.$insertNumber.' where basicid = '.$basic->getBasicid().' and usednumber= '.$basic->getUsednumber();
        $this->phpems->execute($basicSql);
        $rows = $this->phpems->affectedRows();
        if($rows != 1) {
            $this->phpems->rollback();
            return $this->toError(500, '创建失败!');
        }
        if ($result) {
            $this->phpems->commit();
            return $this->toSuccess(true);
        } else {
            $this->phpems->rollback();
            return $this->toError(500, '创建失败!');
        }
    }


    /**
     * 批次列表
     */
    public function BatchAction()
    {

        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);
        $batch_code = $this->request->getQuery('batch_code','string');

        $builder = $this->modelsManager->createBuilder()
            ->columns('b.id,ba.basicid,b.batch_code,b.exam_id,b.subject_id,b.pass_user_num,b.batch_num, FROM_UNIXTIME(b.create_time) as create_time,b.ins_id,s.subject,ba.basic,ba.basicexam')
            ->addFrom('app\models\phpems\X2Batch','b')
            ->leftJoin('app\models\phpems\X2Basic','ba.basicid = b.basic_id','ba')
            ->leftJoin('app\models\phpems\X2Subject','s.subjectid = b.subject_id','s')
            ->andWhere('b.ins_id = :ins_id:',['ins_id' => $this->authed->insId]);

        if (!empty($batch_code)) {
            $builder->andWhere('b.batch_code like :batch_code:',['batch_code' => '%'.$batch_code.'%']);
        }
        $builder->orderBy('b.create_time desc');

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
                $result['data'][$key]['starttime'] = date('Y-m-d H:i:s',$value['opentime']['start']);
            }

            if (isset($value['opentime']['end'])) {
                $result['data'][$key]['endtime'] = date('Y-m-d H:i:s',$value['opentime']['end']);
            }

        }

        return  $this->toSuccess($result['data'],$result['meta']);
    }

    /**
     * @param $id
     * 批次详情
     */
    public function BatchDetailAction($id)
    {

        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);
        $batch = $this->modelsManager->createBuilder()
            ->columns('b.id,b.batch_code,b.exam_id,b.subject_id,b.pass_user_num,FROM_UNIXTIME(b.create_time) as create_time,b.ins_id,ba.basic,s.subject')
            ->addFrom('app\models\phpems\X2Batch','b')
            ->leftJoin('app\models\phpems\X2Basic','ba.basicid = b.basic_id','ba')
            ->leftJoin('app\models\phpems\X2Subject','s.subjectid = b.subject_id','s')
            ->andWhere('b.id = :id:',['id' => $id])
            ->getQuery()->getSingleResult()->toArray();

        if ($batch == false ) {
            return $this->toError(500,'暂无此批次！');
        }


        $select = $this->modelsManager->createBuilder()
            ->columns('eh.ehid,eh.ehexam,eh.ehbasicid,eh.ehtime,eh.ehscore,eh.ehuserid,eh.ehusername,eh.ehstarttime,u.usertruename,u.username,u.usergender,eh.ehispass')
            ->addFrom('app\models\phpems\X2Openbasics','ob')
            ->leftJoin('app\models\phpems\X2User','u.userid = ob.obuserid','u')
            ->leftJoin('app\models\phpems\X2Examhistory','ob.obuserid = eh.ehuserid and eh.ehbasicid = ob.obbasicid','eh')
            ->andWhere('ob.batch_id = :id:',['id' => $id])
            ->orderBy('eh.ehid desc');
        $paginator = new QueryBuilder(
            array(
                "builder" => $select,
                "limit"   => $pageSize,
                "page"    => $pageNum
            )
        );
        $pages = $paginator->getPaginate();
        $result = $this->dataIntegration($pages);
        $data['history'] = $result['data'];
        $data['detail'] = $batch;
        return  $this->toSuccess($data,$result['meta']);
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/2 0002
 * Time: 16:12
 */
namespace app\services\data;
use app\common\errors\DataException;
use app\models\phpems\X2Examhistory;
use app\models\phpems\X2Questype;
use Phalcon\Exception;
use Phalcon\Paginator\Adapter\QueryBuilder;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;

class ExamData extends BaseData
{
    // 生成二维码内容
    public function getExamInfo($driverId)
    {

//        $robots = $this->di->get("modelsCache")->get("exam");;
//        if ($robots === null) {
            //获取试卷ID
            $models = $this->modelsManager->createBuilder()
                ->columns('ba.basicsection,ba.basicknows,ba.basicexam,ba.basic,ba.basicid,s.subject')
                ->addFrom('app\models\phpems\X2User','u')
                ->join('app\models\phpems\X2Openbasics','u.userid = ob.obuserid','ob')
                ->join('app\models\phpems\X2Batch','b.id = ob.batch_id','b')
                ->join('app\models\phpems\X2Basic','ba.basicid = b.basic_id','ba')
                ->join('app\models\phpems\X2Subject','s.subjectid = b.subject_id','s')
                ->where("u.userid = :userid: and ob.obendtime > :time: and ba.basicclosed = :status:",
                    ['userid' => $driverId, 'time' => time(), 'status' => 0])
                ->orderBy('b.create_time desc')
                ->getQuery()
                ->getSingleResult();
//            $this->di->get("modelsCache")->save("exam", $models);
            return $models;
//        } else {
//            return $robots;
//        }

    }

    /**
     * @return array
     */
    public function getQuestType()
    {
        $data = [];
        $result = X2Questype::query()->columns('questid,questype')
            ->execute()->toArray();
        if ($result) {
            foreach ($result as $key => $val) {
                $data[$val['questid']] = $val['questype'];
            }
        }
        return $data;
    }

    /**
     * @param $subject
     * @param $qType
     * @return mixed
     */
    public function getQuestion($subject,$qType)
    {
        //获取缓存的题目
        $robots = $this->di->get("modelsCache")->get($subject.$qType);
        if ($robots === null) {
            if ($subject) {
                $condition = "q.questionstatus = :status: and q.questiontype = :qType: and FIND_IN_SET(qk.qkknowsid, :qkknowsid:)";
                $bind =  ['status' => 1, 'qType' => $qType, 'qkknowsid' => $subject];
            } else {
                $condition = "q.questionstatus = :status: and q.questiontype in (:qType:)";
                $bind =  ['status' => 1, 'qType' => $qType];
            }
            $models = $this->modelsManager->createBuilder()
                ->columns('q.questionid, q.questiontype,q.question,q.questionselect,q.questionselectnumber, 
            q.questionanswer,q.questiondescribe, q.questionknowsid,q.questionlevel,t.questchoice')
                ->addFrom('app\models\phpems\X2Questions','q')
                ->join('app\models\phpems\X2Quest2knows','q.questionid = qk.qkquestionid','qk')
                ->join('app\models\phpems\X2Questype', 't.questid = q.questiontype', 't')
                ->where($condition, $bind)
                ->groupBy('q.questionid')
                ->getQuery()
                ->execute()
                ->toArray();
            $this->di->get("modelsCache")->save($subject.$qType, $models);
            return $models;
        } else {
            return $robots;
        }

    }

    /**
     * 随机抽取题目（按照难易程度进行）
     * @param $question
     * @param $level
     * @param $total
     * @return array
     */
    public function randQuestion($question, $level, $total)
    {
        $data = [];
        if ($level) {
            //有配比时 questionlevel = 1,2,3 易中难
            $level_arr = explode(',', $level);
            $questionLevel = $this->questionRate($level_arr, $total);
            //给题目分类
            foreach ($question as $key => $value) {
                if ($value['questionlevel'] == 1) {
                    $arr[0][] = $value;
                } else if($value['questionlevel'] == 2) {
                    $arr[1][] = $value;
                }else {
                    $arr[2][]= $value;
                }
                unset($question[$key]);
            }
            //分类之后，按照配比随机抽题
            foreach ($questionLevel as $key => $val) {
                if (isset($arr[$key])) {
                    $qu = $this->selectQuestion($arr[$key], $val);
                    if (sizeof($qu)) {
                        $data = array_merge($data, $qu);
                    }
                }
            }
        } else {
            //无配比的时候，随机抽取
            $data = $this->selectQuestion($question, $total);
        }

        return $data;
    }

    /**
     * @param $question
     * @param $total
     * @return array
     */
    private function selectQuestion($question, $total)
    {
        $data = [];
        if (count($question) < $total) {
            $total = count($question);
        }
        if ($total <= 0) {
            return $data;
        }
        $selected = array_rand($question, $total);
        if (is_array($selected) && $total > 0) {
            foreach ($selected as $key => $val) {
                $data[] = $question[$val];
            }
        } else {
            $data[] = $question[$selected];
        }

        return $data;
    }
    /**
     * 获取考生的历史考试记录
     * @param $userId
     * @return mixed
     */
    public function history($userId, $pageNum, $pageSize)
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('ba.basicexam,ba.basic,h.ehscore,h.ehexam,h.ehispass')
            ->addFrom('app\models\phpems\X2Examhistory','h')
            ->join('app\models\phpems\X2Basic','ba.basicid = h.ehbasicid','ba')
            ->where("h.ehuserid = :userid: ",['userid' => $userId])
            ->orderBy('h.ehstarttime desc');

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

    /**
     * 获取考生的快递公司信息
     */
    public function getCompany($insId)
    {
        $models = $this->modelsManager->createBuilder()
            ->columns('c.company_name')
            ->addFrom('app\models\users\Company','c')
            ->where("c.ins_id = :id: ",['id' => $insId])
            ->getQuery()
            ->getSingleResult();
        if ($models == false) {
            throw new Exception('用户没有对应的快递公司');
        }
        return $models;
    }

    /**
     * 获取用户考试次数
     * @param $userId
     * @param $ehbasicid
     * @return int
     */
    public function examNumber($userId,$ehbasicid)
    {
        $count = X2Examhistory::query()
            ->where('ehuserid = :userid: and ehbasicid = :ehbasicid:', ['userid' => $userId, 'ehbasicid' => $ehbasicid])
            ->columns('count(ehid) as count')
            ->execute()->toArray();
        if ($count && isset($count[0]) && isset($count[0]['count'])) {
            $total = $count[0]['count'];
        } else {
            $total = 0;
        }
        return $total;
    }

    /**
     * @param $level [1,1,3]
     * @param $total 5
     * @return array
     */
    private function questionRate($level, $total)
    {
        $questionLevel = [];
        //选择题目难易程度为易的题目
        for ($i = 0 ; $i < 3; $i++) {
            if(isset($level[$i]) && $level[$i] > 0) {
                $questionLevel[$i] = $this->getQuestionCount($level[$i], $total - array_sum($questionLevel));
            }
        }
        return $questionLevel;
    }

    /**
     * @param $level 配比的数量
     * @param $total 剩余需要的题目数量
     * @return mixed
     */
    private function getQuestionCount($level, $total)
    {
        if ($level >= $total) {
            $count = $total;
        } else {
            $count = $level;
        }
        return $count;
    }
}
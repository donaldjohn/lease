<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28 0028
 * Time: 19:33
 */
namespace app\modules\postofficeapp;

use app\models\dispatch\DriverLicence;
use app\models\dispatch\Drivers;
use app\models\dispatch\RegionDrivers;
use app\models\phpems\X2Basic;
use app\models\phpems\X2Batch;
use app\models\phpems\X2Examhistory;
use app\models\phpems\X2Exams;
use app\models\phpems\X2Examsession;
use app\models\phpems\X2Openbasics;
use app\models\phpems\X2User;
use app\models\users\Company;
use app\models\users\User;
use app\modules\BaseController;
use app\services\data\ExamData;
use Phalcon\Exception;
use Phalcon\Mvc\Model\Transaction\Manager;

class ExamController extends BaseController {

    /**
     * 考试考生基本信息
     */
    public function InfoAction()
    {
        $driverId = $this->authed->userId;
        $driver = Drivers::findFirst($driverId);
        $data = [];
        try {
            if (!$driver) {
                throw new Exception('未找到改骑手信息');
            }
            $data = [
                "id" => $driver->id,
                "sex" => $driver->sex,
                "real_name" => $driver->real_name,
                "identify" => $driver->identify,
            ];
            $rd = RegionDrivers::arrFindFirst([
                'driver_id' => $driverId,
            ]);
            $examData = new ExamData();
            //获取考试信息
            $result = $examData->getExamInfo($driverId);
            if (!$result) {
                //无考试信息
                return $this->toSuccess(null);
            }
            $examsession = X2Examsession::findFirst($driverId);
            $basicexam = unserialize($result->basicexam);

            if (!$examsession || ($examsession->examsessionstarttime + $examsession->examsessiontime * 60 < time())
                || $examsession->examsessionstatus == 2) {
                $exam_total = $examData->examNumber($driverId, $result->basicid);
//                if ($exam_total > 0) {
//                    return $this->toError('500', '您已经参加过本次考试，请报名下次考试');
//                }
                if ($exam_total >= $basicexam['examnumber'] && $basicexam['examnumber'] != 0) {
                    return $this->toError('500', '考试次数已用完，请联系快递公司在下一考场给您报名！');
                }
            }

            $models = $examData->getCompany($rd->ins_id);
            $data['company_name'] =  $models->company_name;
            //绑定快递公司
            if (!$rd) {
                throw new Exception('用户没有对应的快递公司');
            }
            if (isset($basicexam['opentime'])) {
                $time = $basicexam['opentime'];
            } else {
                $time = [
                    'start' => null,
                    'end' => null
                ];
            }
            $data['basic'] = $result->basic;
            $data['start_time'] = isset($time['start']) ? $time['start'] : null;
            $data['subject'] = $result->subject;
            $data['end_time'] = isset($time['end']) ? $time['end'] : null;
            $exam = X2Exams::findFirst($basicexam['self']);
            $examInfo = unserialize($exam->examsetting);
            $data['examtime'] = $examInfo['examtime'];
        } catch (Exception $e) {
            return $this->toError(500,  $e->getMessage());
        }
        return $this->toSuccess($data);
    }

    /**
     * 获取考试的题目
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function QuestionAction()
    {
        $driverId = $this->authed->userId;
//        $driverId = 124771;
        /**
         * 查询 骑手是否已经获取驾照
         */
        //是否有驾照：
        $driverLicence = DriverLicence::findFirst(['conditions' => 'driver_id = :driverId: and licence_score > 0','bind' => ['driverId' => $driverId]]);
        if ($driverLicence) {
            return $this->toError(500, '考试已通过，无需重复考试！');
        }
        try {
            $examsession = X2Examsession::findFirst($driverId);
            if ($examsession && ($examsession->examsessionstarttime + $examsession->examsessiontime * 60 > time()) && $examsession->examsessionstatus == 0) {
                $data = unserialize($examsession->examsessionquestion);
                $flag = 1;
                $expire_time = $examsession->examsessiontime * 60 + $examsession->examsessionstarttime - time();
            } else {
                $flag = 0;
                if (!$examsession) {
                    $examsession = new X2Examsession();
                }
                $examData = new ExamData();
                //获取试卷ID
                $models = $examData->getExamInfo($driverId);
                $basicexam = unserialize($models->basicexam);
                if (isset($basicexam['opentime'])) {
                    if (isset($basicexam['opentime']['start']) && $basicexam['opentime']['start'] > time()) {
                        return $this->toError('500', '还未到考试时间，请在规定考试时间考试');
                    }
                    if (isset($basicexam['opentime']['end']) && $basicexam['opentime']['end'] < time()) {
                        return $this->toError('500', '考试时间已过');
                    }
                }

                $exam = X2Exams::findFirst($basicexam['self']);
                //根据产品要求目前考试只能考一次
                // 考试次数动态配置
                $exam_total = $examData->examNumber($driverId, $models->basicid);
//                if ($exam_total > 0) {
//                    return $this->toError('500', '您已经参加过本次考试，请报名下次考试');
//                }
                if ($exam_total >= $basicexam['examnumber'] && $basicexam['examnumber'] != 0) {
                    return $this->toError('500', '考试次数已用完，请联系快递公司在下一考场给您报名！');
                }
                $examInfo = unserialize($exam->examsetting);
                $expire_time = $examInfo['examtime'] * 60;
                $data = $this->selectQuestion($driverId, $examData, $examInfo);
                $manager = new Manager();
                $transaction = $manager->setDbService('phpems')->get();
                try {
                    $examId =  $exam->examid;
                    $ehexam =  $exam->exam;
                    $save_data = [
                        "examsession" =>$exam->exam,
                        "examsessionstarttime" => time(),
                        "examsessionid" => $driverId,
                        "examsessionsetting" => serialize($exam->toArray()),
                        "examsessiontime" => $examInfo['examtime'],
                        "examsessionquestion" => serialize($data),
                        "examsessionuserid" => $driverId,
                        "examsessionstatus" => 0,
                        "examsessionbasic" => $models->basicid,
                        "examsessiontype" => 2,//TODO：暂未找到是什么意思？
                    ];
                    $history = new X2Examhistory();
                    $historyData = [
                        'ehexamid' => $examId,
                        'ehexam' => $ehexam,
                        'ehtype' => 2,
                        'ehbasicid' => $models->basicid,
                        'ehscorelist' => '',
                        'ehuseranswer' => '',
                        'ehscore' => 0,
                        'ehuserid' => $driverId,
                        'ehendtime' => time(),
                        'ehstarttime' => time(),
                        'ehstatus' => 1,
                        'ehdecide' => 0,
                        'ehneedresit' => 0,
                        'ehispass' => 0,
                        'ehopenid' => '',
                        'ehdecidetime' => 0,
                        'ehquestion' => ''
                    ];
                    $examsession->setTransaction($transaction);
                    if($examsession->save($save_data) == false) {
                        $transaction->rollback($examsession->getMessages());
                    }
                    $history->setTransaction($transaction);
                    if ($history->save($historyData) == false) {
                        $transaction->rollback($history->getMessages());
                    }
                    $transaction->commit();
                } catch (\Exception $e) {
                    return $this->toError('500', $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            return $this->toError('500', $e->getMessage());
        }

        return $this->toSuccess($data, ['flag' => $flag, 'expire_time' => $expire_time]);
    }

    private function selectQuestion($driverId, $examData, $examInfo)
    {
        //获取题型
        $questype = $examInfo['questype'];
        //配比信息
        $data = [];
        foreach ($questype as $qtype => $val) {
            // $qtype 题目的类型 $val['number']//题目的数量
            $item = [];
            $question = [];
            if (isset($examInfo['examscale'][$qtype])) {
                $examScale = explode(':', $examInfo['examscale'][$qtype]);
                $total = isset($examScale[1]) ? $examScale[1] : $val['num'];
                $subject = isset($examScale[0]) ? $examScale[0] : 0;
                $question = $examData->getQuestion($subject, $qtype);
                $level = isset($examScale[2]) ? $examScale[2] : 0;
                $item = $examData->randQuestion($question, $level, $total);
            } else {
                $question = $examData->getQuestion(0, $qtype);
                $item = $examData->randQuestion($question, '', $val['number']);
            }
            $data = array_merge($data, $item);
        }
        return $data;
    }

    /**
     * 提交考试信息，保存成绩
     */
    public function SaveAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $driverId = $this->authed->userId;
//        $driverId = 124771;
        $examsession = X2Examsession::findFirst($driverId);
        if ($examsession->examsessionstatus == 2) {
            return $this->toError('200', '不能重复提交');
        }
        $question = unserialize($examsession->examsessionquestion);
        //获取每道题型的分数：
        $setting = unserialize($examsession->examsessionsetting);
        $exam = unserialize($setting['examsetting']);
        $score = 0;
        $score_list = [];
        $examsessionuseranswer = [];
        foreach ($question as $key => $val){
            //user的答案
            foreach ($data['data'] as $kk => $vv) {
                //找到题目跳出当次循环
                if ($vv['question_id'] == $val['questionid']) {
                    $examsessionuseranswer[$vv['question_id']] = $vv['answer'];
                    //答案正确：加分
                    if ($vv['answer'] == $val['questionanswer']) {
                        $score_list[$vv['question_id']] = $exam['questype'][$val['questiontype']]['score'];
                        $score +=  $score_list[$vv['question_id']] ;
                    }
                    continue;
                }
            }
        }
        $result = X2Examhistory::query()->where('ehuserid = :ehuserid: and ehbasicid = :ehbasicid:',
            ["ehuserid" => $driverId, "ehbasicid" =>$examsession->examsessionbasic ])->orderBy('ehid desc')->execute()->toArray();
        if ($result) {
            $history = X2Examhistory::findFirst($result[0]['ehid']);
            $saveData = [
                'ehexamid' => $setting['examid'],
                'ehscorelist' =>serialize($score_list),
                'ehuseranswer' => serialize($examsessionuseranswer),
                'ehscore' => $score,
                'ehuserid' => $driverId,
                'ehendtime' => time(),
                'ehispass' => $score >= $exam['passscore'] ? 1 :0,
            ];
        } else {
            $history = new X2Examhistory();
            $saveData = [
                'ehexamid' => $setting['examid'],
                'ehexam' => $examsession->examsession,
                'ehtype' => 2,
                'ehbasicid' =>$examsession->examsessionbasic,
                'ehscorelist' =>serialize($score_list),
                'ehuseranswer' => serialize($examsessionuseranswer),
                'ehscore' => $score,
                'ehuserid' => $driverId,
                'ehendtime' => time(),
                'ehstarttime' => $examsession->examsessionstarttime,
                'ehstatus' => 1,
                'ehdecide' => 0,
                'ehneedresit' => 0,
                'ehispass' => $score >= $exam['passscore'] ? 1 :0,
                'ehopenid' => '',
                'ehdecidetime' => 0,
                'ehquestion' => ''
            ];
        }
        $manager = new Manager();
        $transaction = $manager->setDbService('phpems')->get();
        try {
            //考试通过给当前批次通过人次加一
            if ( $score >= $exam['passscore'] ? 1 :0) {
//                $x2_open = X2Openbasics::findFirst(['obbasicid' => $examsession->examsessionbasic]);
                $x2_open = X2Openbasics::findFirst(['obbasicid = :obbasicid: and obuserid = :obuserid:','bind' =>
                    ['obbasicid' => $examsession->examsessionbasic, 'obuserid' => $driverId]]);
                if ($x2_open) {
                    $x2_batch = X2Batch::findFirst($x2_open->batch_id);
                    $x2_batch->setPassUserNum(($x2_batch->getPassUserNum() ? $x2_batch->getPassUserNum() : 0 )+ 1);
                    if ($x2_batch) {
                        $x2_batch->setTransaction($transaction);
                        if($x2_batch->save() == false) {
                            $transaction->rollback($x2_batch->getMessages());
                        }
                    }
                }
            }

            $history->setTransaction($transaction);
            if($history->save($saveData) == false) {
                $transaction->rollback($examsession->getMessages());
            }
            $examsession->setTransaction($transaction);
            if ($examsession->save(['examsessionstatus' => 2]) == false) {
                $transaction->rollback($examsession->getMessages());
            }
            //是否有驾照：
            $driverLicence = DriverLicence::findFirst(['conditions' => 'driver_id = :driverId:','bind' => ['driverId' => $driverId]]);
            if ($driverLicence && $driverLicence->getLicenceScore() <= 0) {
                if ($score >= $exam['passscore'] ? 1 :0) {
                    $res = DriverLicence::findFirst(['conditions' => 'driver_id = :driverId: and version = :version:',
                        'bind' => ['driverId' => $driverId, 'version' => $driverLicence->version]])->update(['licence_score' => $driverLicence->licence_score + 12,
                        'version' => $driverLicence->version ? $driverLicence->version + 1 : 1]);
                    if ($res == false) {
                        $transaction->rollback("更新驾照失败");
                    };
                }
            } else {
                if ($score >= $exam['passscore'] ? 1 :0) {
                    $user =X2User::findFirst($driverId);
                    $driverLicence = new DriverLicence();
                    $driverLicence->setLicenceScore(12);
                    $driverLicence->setInsId($user ? $user->getParentInsId() : "");
                    $driverLicence->setDriverId($driverId);
                    $driverLicence->setInsName($user ? $user->getParentInsName() : "");
                    $driverLicence->setLicenceNum($user ? $user->getUsername() : "");
                    $driverLicence->setHasLicence(1);
                    $driverLicence->setGetTime(time());
                    //默认是6年之后截止，如果是郑州市，截止时间是2021年11月30号
                    $endTime = time()+ 86400*365*6;
                    $regionDriver = RegionDrivers::findFirst(['driver_id = :driver_id:',
                        'bind' => [
                            'driver_id' => $driverId,
                        ],]);
                    if ($regionDriver) {
                        $company = Company::findFirst(['ins_id = :ins_id:',
                            'bind' => [
                                'ins_id' => $regionDriver->ins_id,
                            ],]);
                        if ($company && $company->city_id == "410100") {
                            $endTime = 1638201600;;
                        }
                    }
                    $driverLicence->setValidEndtime($endTime);
                    $driverLicence->setValidStarttime(time());
                    $driverLicence->setVersion(0);
                    if ($driverLicence->save() == false) {
                        $transaction->rollback("新增驾照失败");
                    };
                }
            }

            $transaction->commit();

        } catch (\Exception $e) {
            return $this->toError('500', $e->getMessage());
        }
        $exportData = [
            'ehscore' => $score,
            'use_time' => time() - $examsession->examsessionstarttime,
            'ehispass' => $score >= $exam['passscore'] ? 1 :0,
        ];
        return $this->toSuccess($exportData);
    }
    /**
     * 考试历史记录
     */
    public function HistoryAction()
    {
        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);
        $driverId = $this->authed->userId;
//        $driverId = "124748";
        $examData = new ExamData();
        $result = $examData->history($driverId, $pageNum, $pageSize);
        $data = [];
        foreach ($result['data'] as $key => $val) {
            $basicexam = unserialize($val['basicexam']);
            $item = [
                'basic' => $val['basic'],
                'score' => $val['ehscore'],
                'exam_name' => $val['ehexam'],
                'ehispass' => $val['ehispass'],
                'start_time' => isset($basicexam['opentime']['start']) ? $basicexam['opentime']['start'] :'--',
                'end_time' => isset($basicexam['opentime']['end']) ? $basicexam['opentime']['end'] :'--',
            ];
            $data[] = $item;
            unset($result[$key]);
        }
        return $this->toSuccess($data, $result['meta']);
    }
}

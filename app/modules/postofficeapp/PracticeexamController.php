<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26 0026
 * Time: 16:59
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
use app\models\phpems\X2Examsessionpractice;
use app\models\phpems\X2Openbasics;
use app\models\phpems\X2User;
use app\models\users\User;
use app\modules\BaseController;
use app\services\data\ExamData;
use Phalcon\Exception;
use Phalcon\Mvc\Model\Transaction\Manager;

class PracticeexamController extends BaseController{
    /**
     * 获取考试的题目
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function QuestionAction()
    {
        $driverId = $this->authed->userId;
        try {
            $examsession = X2Examsessionpractice::findFirst($driverId);
            if ($examsession && ($examsession->examsessionstarttime + $examsession->examsessiontime * 60 > time()) && $examsession->examsessionstatus == 0) {
                $data = unserialize($examsession->examsessionquestion);
                $flag = 1;
                $expire_time = $examsession->examsessiontime * 60 + $examsession->examsessionstarttime - time();
            } else {
                $flag = 0;
                if (!$examsession) {
                    $examsession = new X2Examsessionpractice();
                }
                $examData = new ExamData();
                //获取试卷ID 取最新的一条信息
//                $models = $examData->getExamInfo($driverId);
                $models = X2Basic::findFirst(array("order"=>"basicid DESC"));
                $basicexam = unserialize($models->basicexam);
                $exam = X2Exams::findFirst($basicexam['self']);

                $examInfo = unserialize($exam->examsetting);
                $expire_time = $examInfo['examtime'] * 60;
                $data = $this->selectQuestion($driverId, $examData, $examInfo);
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
                ];//print_r($save_data);exit;
                if($examsession->save($save_data) == false) {
                    return $this->toError('500', $examsession->getMessage());
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
        $examsession = X2Examsessionpractice::findFirst($driverId);
        $question = unserialize($examsession->examsessionquestion);
        //获取每道题型的分数：
        $setting = unserialize($examsession->examsessionsetting);
        $exam = unserialize($setting['examsetting']);
        $score = 0;
        $score_list = [];
        $examsessionuseranswer = [];
        foreach ($question as $key => $val) {
            //user的答案
            foreach ($data['data'] as $kk => $vv) {
                //找到题目跳出当次循环
                if ($vv['question_id'] == $val['questionid']) {
                    $examsessionuseranswer[$vv['question_id']] = $vv['answer'];
                    //答案正确：加分
                    if ($vv['answer'] == $val['questionanswer']) {
                        $score_list[$vv['question_id']] = $exam['questype'][$val['questiontype']]['score'];
                        $score += $score_list[$vv['question_id']];
                    }
                    continue;
                }
            }
        }
        $examsession->save(['examsessionstatus' => 2]);
        $models = $this->modelsManager->createBuilder()
            ->columns('s.subject')
            ->addFrom('app\models\phpems\X2Subject','s')
            ->join('app\models\phpems\X2Batch','s.subjectid = b.subject_id','b')
            ->join('app\models\phpems\X2Basic','b.subject_id = s.subjectid','ba')
            ->where("ba.basicid = :basicid:",
                ['basicid' => $examsession->examsessionbasic])
            ->getQuery()
            ->getSingleResult();
        $exportData = [
            'ehscore' => $score,
            'use_time' => time() - $examsession->examsessionstarttime,
            'ehispass' => $score >= $exam['passscore'] ? 1 :0,
            'subject' => $models->subject,
        ];
        return $this->toSuccess($exportData);
    }

}
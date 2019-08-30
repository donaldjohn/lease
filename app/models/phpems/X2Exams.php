<?php

namespace app\models\phpems;

class X2Exams extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $examid;

    /**
     *
     * @var integer
     */
    protected $examsubject;

    /**
     *
     * @var string
     */
    protected $exam;

    /**
     *
     * @var string
     */
    protected $examsetting;

    /**
     *
     * @var string
     */
    protected $examquestions;

    /**
     *
     * @var string
     */
    protected $examscore;

    /**
     *
     * @var integer
     */
    protected $examstatus;

    /**
     *
     * @var integer
     */
    protected $examtype;

    /**
     *
     * @var integer
     */
    protected $examauthorid;

    /**
     *
     * @var string
     */
    protected $examauthor;

    /**
     *
     * @var integer
     */
    protected $examtime;

    /**
     *
     * @var string
     */
    protected $examkeyword;

    /**
     *
     * @var integer
     */
    protected $examdecide;

    /**
     *
     * @var integer
     */
    protected $ins_id;

    /**
     * Method to set the value of field examid
     *
     * @param integer $examid
     * @return $this
     */
    public function setExamid($examid)
    {
        $this->examid = $examid;

        return $this;
    }

    /**
     * Method to set the value of field examsubject
     *
     * @param integer $examsubject
     * @return $this
     */
    public function setExamsubject($examsubject)
    {
        $this->examsubject = $examsubject;

        return $this;
    }

    /**
     * Method to set the value of field exam
     *
     * @param string $exam
     * @return $this
     */
    public function setExam($exam)
    {
        $this->exam = $exam;

        return $this;
    }

    /**
     * Method to set the value of field examsetting
     *
     * @param string $examsetting
     * @return $this
     */
    public function setExamsetting($examsetting)
    {
        $this->examsetting = $examsetting;

        return $this;
    }

    /**
     * Method to set the value of field examquestions
     *
     * @param string $examquestions
     * @return $this
     */
    public function setExamquestions($examquestions)
    {
        $this->examquestions = $examquestions;

        return $this;
    }

    /**
     * Method to set the value of field examscore
     *
     * @param string $examscore
     * @return $this
     */
    public function setExamscore($examscore)
    {
        $this->examscore = $examscore;

        return $this;
    }

    /**
     * Method to set the value of field examstatus
     *
     * @param integer $examstatus
     * @return $this
     */
    public function setExamstatus($examstatus)
    {
        $this->examstatus = $examstatus;

        return $this;
    }

    /**
     * Method to set the value of field examtype
     *
     * @param integer $examtype
     * @return $this
     */
    public function setExamtype($examtype)
    {
        $this->examtype = $examtype;

        return $this;
    }

    /**
     * Method to set the value of field examauthorid
     *
     * @param integer $examauthorid
     * @return $this
     */
    public function setExamauthorid($examauthorid)
    {
        $this->examauthorid = $examauthorid;

        return $this;
    }

    /**
     * Method to set the value of field examauthor
     *
     * @param string $examauthor
     * @return $this
     */
    public function setExamauthor($examauthor)
    {
        $this->examauthor = $examauthor;

        return $this;
    }

    /**
     * Method to set the value of field examtime
     *
     * @param integer $examtime
     * @return $this
     */
    public function setExamtime($examtime)
    {
        $this->examtime = $examtime;

        return $this;
    }

    /**
     * Method to set the value of field examkeyword
     *
     * @param string $examkeyword
     * @return $this
     */
    public function setExamkeyword($examkeyword)
    {
        $this->examkeyword = $examkeyword;

        return $this;
    }

    /**
     * Method to set the value of field examdecide
     *
     * @param integer $examdecide
     * @return $this
     */
    public function setExamdecide($examdecide)
    {
        $this->examdecide = $examdecide;

        return $this;
    }

    /**
     * Method to set the value of field ins_id
     *
     * @param integer $ins_id
     * @return $this
     */
    public function setInsId($ins_id)
    {
        $this->ins_id = $ins_id;

        return $this;
    }

    /**
     * Returns the value of field examid
     *
     * @return integer
     */
    public function getExamid()
    {
        return $this->examid;
    }

    /**
     * Returns the value of field examsubject
     *
     * @return integer
     */
    public function getExamsubject()
    {
        return $this->examsubject;
    }

    /**
     * Returns the value of field exam
     *
     * @return string
     */
    public function getExam()
    {
        return $this->exam;
    }

    /**
     * Returns the value of field examsetting
     *
     * @return string
     */
    public function getExamsetting()
    {
        return $this->examsetting;
    }

    /**
     * Returns the value of field examquestions
     *
     * @return string
     */
    public function getExamquestions()
    {
        return $this->examquestions;
    }

    /**
     * Returns the value of field examscore
     *
     * @return string
     */
    public function getExamscore()
    {
        return $this->examscore;
    }

    /**
     * Returns the value of field examstatus
     *
     * @return integer
     */
    public function getExamstatus()
    {
        return $this->examstatus;
    }

    /**
     * Returns the value of field examtype
     *
     * @return integer
     */
    public function getExamtype()
    {
        return $this->examtype;
    }

    /**
     * Returns the value of field examauthorid
     *
     * @return integer
     */
    public function getExamauthorid()
    {
        return $this->examauthorid;
    }

    /**
     * Returns the value of field examauthor
     *
     * @return string
     */
    public function getExamauthor()
    {
        return $this->examauthor;
    }

    /**
     * Returns the value of field examtime
     *
     * @return integer
     */
    public function getExamtime()
    {
        return $this->examtime;
    }

    /**
     * Returns the value of field examkeyword
     *
     * @return string
     */
    public function getExamkeyword()
    {
        return $this->examkeyword;
    }

    /**
     * Returns the value of field examdecide
     *
     * @return integer
     */
    public function getExamdecide()
    {
        return $this->examdecide;
    }

    /**
     * Returns the value of field ins_id
     *
     * @return integer
     */
    public function getInsId()
    {
        return $this->ins_id;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("x2_exams");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_exams';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Exams[]|X2Exams|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Exams|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * Independent Column Mapping.
     * Keys are the real names in the table and the values their names in the application
     *
     * @return array
     */
    public function columnMap()
    {
        return [
            'examid' => 'examid',
            'examsubject' => 'examsubject',
            'exam' => 'exam',
            'examsetting' => 'examsetting',
            'examquestions' => 'examquestions',
            'examscore' => 'examscore',
            'examstatus' => 'examstatus',
            'examtype' => 'examtype',
            'examauthorid' => 'examauthorid',
            'examauthor' => 'examauthor',
            'examtime' => 'examtime',
            'examkeyword' => 'examkeyword',
            'examdecide' => 'examdecide',
            'ins_id' => 'ins_id'
        ];
    }

}

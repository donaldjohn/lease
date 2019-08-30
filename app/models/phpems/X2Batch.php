<?php

namespace app\models\phpems;

class X2Batch extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var integer
     */
    protected $basic_id;

    /**
     *
     * @var string
     */
    protected $batch_code;

    /**
     *
     * @var integer
     */
    protected $exam_id;

    /**
     *
     * @var integer
     */
    protected $subject_id;

    /**
     *
     * @var integer
     */
    protected $batch_num;

    /**
     *
     * @var integer
     */
    protected $pass_user_num;

    /**
     *
     * @var integer
     */
    protected $create_time;

    /**
     *
     * @var integer
     */
    protected $ins_id;

    /**
     * Method to set the value of field id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Method to set the value of field basic_id
     *
     * @param integer $basic_id
     * @return $this
     */
    public function setBasicId($basic_id)
    {
        $this->basic_id = $basic_id;

        return $this;
    }

    /**
     * Method to set the value of field batch_code
     *
     * @param string $batch_code
     * @return $this
     */
    public function setBatchCode($batch_code)
    {
        $this->batch_code = $batch_code;

        return $this;
    }

    /**
     * Method to set the value of field exam_id
     *
     * @param integer $exam_id
     * @return $this
     */
    public function setExamId($exam_id)
    {
        $this->exam_id = $exam_id;

        return $this;
    }

    /**
     * Method to set the value of field subject_id
     *
     * @param integer $subject_id
     * @return $this
     */
    public function setSubjectId($subject_id)
    {
        $this->subject_id = $subject_id;

        return $this;
    }

    /**
     * Method to set the value of field batch_num
     *
     * @param integer $batch_num
     * @return $this
     */
    public function setBatchNum($batch_num)
    {
        $this->batch_num = $batch_num;

        return $this;
    }

    /**
     * Method to set the value of field pass_user_num
     *
     * @param integer $pass_user_num
     * @return $this
     */
    public function setPassUserNum($pass_user_num)
    {
        $this->pass_user_num = $pass_user_num;

        return $this;
    }

    /**
     * Method to set the value of field create_time
     *
     * @param integer $create_time
     * @return $this
     */
    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;

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
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field basic_id
     *
     * @return integer
     */
    public function getBasicId()
    {
        return $this->basic_id;
    }

    /**
     * Returns the value of field batch_code
     *
     * @return string
     */
    public function getBatchCode()
    {
        return $this->batch_code;
    }

    /**
     * Returns the value of field exam_id
     *
     * @return integer
     */
    public function getExamId()
    {
        return $this->exam_id;
    }

    /**
     * Returns the value of field subject_id
     *
     * @return integer
     */
    public function getSubjectId()
    {
        return $this->subject_id;
    }

    /**
     * Returns the value of field batch_num
     *
     * @return integer
     */
    public function getBatchNum()
    {
        return $this->batch_num;
    }

    /**
     * Returns the value of field pass_user_num
     *
     * @return integer
     */
    public function getPassUserNum()
    {
        return $this->pass_user_num;
    }

    /**
     * Returns the value of field create_time
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
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
        $this->setSource("x2_batch");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_batch';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Batch[]|X2Batch|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Batch|\Phalcon\Mvc\Model\ResultInterface
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
            'id' => 'id',
            'basic_id' => 'basic_id',
            'batch_code' => 'batch_code',
            'exam_id' => 'exam_id',
            'subject_id' => 'subject_id',
            'batch_num' => 'batch_num',
            'pass_user_num' => 'pass_user_num',
            'create_time' => 'create_time',
            'ins_id' => 'ins_id'
        ];
    }

}

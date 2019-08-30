<?php

namespace app\models\phpems;

class X2Subject extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $subjectid;

    /**
     *
     * @var string
     */
    protected $subject;

    /**
     *
     * @var string
     */
    protected $subjectsetting;

    /**
     *
     * @var integer
     */
    protected $ins_id;

    /**
     * Method to set the value of field subjectid
     *
     * @param integer $subjectid
     * @return $this
     */
    public function setSubjectid($subjectid)
    {
        $this->subjectid = $subjectid;

        return $this;
    }

    /**
     * Method to set the value of field subject
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Method to set the value of field subjectsetting
     *
     * @param string $subjectsetting
     * @return $this
     */
    public function setSubjectsetting($subjectsetting)
    {
        $this->subjectsetting = $subjectsetting;

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
     * Returns the value of field subjectid
     *
     * @return integer
     */
    public function getSubjectid()
    {
        return $this->subjectid;
    }

    /**
     * Returns the value of field subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Returns the value of field subjectsetting
     *
     * @return string
     */
    public function getSubjectsetting()
    {
        return $this->subjectsetting;
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
        $this->setSource("x2_subject");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_subject';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Subject[]|X2Subject|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Subject|\Phalcon\Mvc\Model\ResultInterface
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
            'subjectid' => 'subjectid',
            'subject' => 'subject',
            'subjectsetting' => 'subjectsetting',
            'ins_id' => 'ins_id'
        ];
    }

}

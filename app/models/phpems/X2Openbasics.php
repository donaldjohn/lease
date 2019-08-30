<?php

namespace app\models\phpems;

class X2Openbasics extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $obid;

    /**
     *
     * @var integer
     */
    protected $obuserid;

    /**
     *
     * @var integer
     */
    protected $obbasicid;

    /**
     *
     * @var integer
     */
    protected $obtime;

    /**
     *
     * @var integer
     */
    protected $obendtime;

    /**
     *
     * @var integer
     */
    protected $batch_id;

    /**
     * Method to set the value of field obid
     *
     * @param integer $obid
     * @return $this
     */
    public function setObid($obid)
    {
        $this->obid = $obid;

        return $this;
    }

    /**
     * Method to set the value of field obuserid
     *
     * @param integer $obuserid
     * @return $this
     */
    public function setObuserid($obuserid)
    {
        $this->obuserid = $obuserid;

        return $this;
    }

    /**
     * Method to set the value of field obbasicid
     *
     * @param integer $obbasicid
     * @return $this
     */
    public function setObbasicid($obbasicid)
    {
        $this->obbasicid = $obbasicid;

        return $this;
    }

    /**
     * Method to set the value of field obtime
     *
     * @param integer $obtime
     * @return $this
     */
    public function setObtime($obtime)
    {
        $this->obtime = $obtime;

        return $this;
    }

    /**
     * Method to set the value of field obendtime
     *
     * @param integer $obendtime
     * @return $this
     */
    public function setObendtime($obendtime)
    {
        $this->obendtime = $obendtime;

        return $this;
    }

    /**
     * Method to set the value of field batch_id
     *
     * @param integer $batch_id
     * @return $this
     */
    public function setBatchId($batch_id)
    {
        $this->batch_id = $batch_id;

        return $this;
    }

    /**
     * Returns the value of field obid
     *
     * @return integer
     */
    public function getObid()
    {
        return $this->obid;
    }

    /**
     * Returns the value of field obuserid
     *
     * @return integer
     */
    public function getObuserid()
    {
        return $this->obuserid;
    }

    /**
     * Returns the value of field obbasicid
     *
     * @return integer
     */
    public function getObbasicid()
    {
        return $this->obbasicid;
    }

    /**
     * Returns the value of field obtime
     *
     * @return integer
     */
    public function getObtime()
    {
        return $this->obtime;
    }

    /**
     * Returns the value of field obendtime
     *
     * @return integer
     */
    public function getObendtime()
    {
        return $this->obendtime;
    }

    /**
     * Returns the value of field batch_id
     *
     * @return integer
     */
    public function getBatchId()
    {
        return $this->batch_id;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
       parent::initialize();
        $this->setSource("x2_openbasics");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_openbasics';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Openbasics[]|X2Openbasics|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Openbasics|\Phalcon\Mvc\Model\ResultInterface
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
            'obid' => 'obid',
            'obuserid' => 'obuserid',
            'obbasicid' => 'obbasicid',
            'obtime' => 'obtime',
            'obendtime' => 'obendtime',
            'batch_id' => 'batch_id'
        ];
    }

    public  function batch_insert(array $data)
    {
        if (count($data) == 0) {
            return false;
        }
        $keys = array_keys(reset($data));
        $keys = array_map(function ($key) {
            return "`{$key}`";
        }, $keys);
        $keys = implode(',', $keys);
        $sql = "INSERT INTO " . $this->getSource() . " ({$keys}) VALUES ";
        foreach ($data as $v) {
            $v = array_map(function ($value) {
                return "'{$value}'";
            }, $v);
            $values = implode(',', array_values($v));
            $sql .= " ({$values}), ";
        }
        $sql = rtrim(trim($sql), ',');
        return $sql;
    }

}

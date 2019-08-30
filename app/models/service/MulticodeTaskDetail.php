<?php
namespace app\models\service;


class MulticodeTaskDetail extends BaseModel
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
    protected $task_id;

    /**
     *
     * @var string
     */
    protected $qrcode;

    /**
     *
     * @var string
     */
    protected $udid;

    /**
     *
     * @var string
     */
    protected $vin;

    /**
     *
     * @var string
     */
    protected $plate_num;

    /**
     *
     * @var integer
     */
    protected $sweep_time;

    /**
     *
     * @var integer
     */
    protected $create_at;

    /**
     *
     * @var integer
     */
    protected $update_at;

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
     * Method to set the value of field task_id
     *
     * @param integer $task_id
     * @return $this
     */
    public function setTaskId($task_id)
    {
        $this->task_id = $task_id;

        return $this;
    }

    /**
     * Method to set the value of field qrcode
     *
     * @param string $qrcode
     * @return $this
     */
    public function setQrcode($qrcode)
    {
        $this->qrcode = $qrcode;

        return $this;
    }

    /**
     * Method to set the value of field udid
     *
     * @param string $udid
     * @return $this
     */
    public function setUdid($udid)
    {
        $this->udid = $udid;

        return $this;
    }

    /**
     * Method to set the value of field vin
     *
     * @param string $vin
     * @return $this
     */
    public function setVin($vin)
    {
        $this->vin = $vin;

        return $this;
    }

    /**
     * Method to set the value of field plate_num
     *
     * @param string $plate_num
     * @return $this
     */
    public function setPlateNum($plate_num)
    {
        $this->plate_num = $plate_num;

        return $this;
    }

    /**
     * Method to set the value of field sweep_time
     *
     * @param integer $sweep_time
     * @return $this
     */
    public function setSweepTime($sweep_time)
    {
        $this->sweep_time = $sweep_time;

        return $this;
    }

    /**
     * Method to set the value of field create_at
     *
     * @param integer $create_at
     * @return $this
     */
    public function setCreateAt($create_at)
    {
        $this->create_at = $create_at;

        return $this;
    }

    /**
     * Method to set the value of field update_at
     *
     * @param integer $update_at
     * @return $this
     */
    public function setUpdateAt($update_at)
    {
        $this->update_at = $update_at;

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
     * Returns the value of field task_id
     *
     * @return integer
     */
    public function getTaskId()
    {
        return $this->task_id;
    }

    /**
     * Returns the value of field qrcode
     *
     * @return string
     */
    public function getQrcode()
    {
        return $this->qrcode;
    }

    /**
     * Returns the value of field udid
     *
     * @return string
     */
    public function getUdid()
    {
        return $this->udid;
    }

    /**
     * Returns the value of field vin
     *
     * @return string
     */
    public function getVin()
    {
        return $this->vin;
    }

    /**
     * Returns the value of field plate_num
     *
     * @return string
     */
    public function getPlateNum()
    {
        return $this->plate_num;
    }

    /**
     * Returns the value of field sweep_time
     *
     * @return integer
     */
    public function getSweepTime()
    {
        return $this->sweep_time;
    }

    /**
     * Returns the value of field create_at
     *
     * @return integer
     */
    public function getCreateAt()
    {
        return $this->create_at;
    }

    /**
     * Returns the value of field update_at
     *
     * @return integer
     */
    public function getUpdateAt()
    {
        return $this->update_at;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSchema("dewin_service");
        $this->setSource("dw_multicode_task_detail");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_multicode_task_detail';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MulticodeTaskDetail[]|MulticodeTaskDetail|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MulticodeTaskDetail|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }



    public function beforeCreate()
    {
        $this->setCreateAt(time());
    }
    public function beforeUpdate()
    {
        $this->setUpdateAt(time());
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

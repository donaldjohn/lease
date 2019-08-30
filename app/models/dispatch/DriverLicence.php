<?php

namespace app\models\dispatch;

class DriverLicence extends BaseModel
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
    protected $driver_id;

    /**
     *
     * @var integer
     */
    protected $has_licence;

    /**
     *
     * @var string
     */
    protected $licence_num;

    /**
     *
     * @var integer
     */
    protected $valid_starttime;

    /**
     *
     * @var integer
     */
    protected $valid_endtime;

    /**
     *
     * @var integer
     */
    protected $licence_score;

    /**
     *
     * @var integer
     */
    protected $get_time;


    protected $front_img;


    protected $back_img;
    /**
     *
     * @var integer
     */
    protected $ins_id;
    /**
     *
     * @var string
     */
    protected $ins_name;

    /**
     * @var
     */
    protected $version;

    /**
     * @var
     */
    protected $is_send;

    /**
     * @return int
     */
    public function getInsId(): int
    {
        return $this->ins_id;
    }

    /**
     * @param int $ins_id
     */
    public function setInsId(int $ins_id): void
    {
        $this->ins_id = $ins_id;
    }

    /**
     * @return string
     */
    public function getInsName(): string
    {
        return $this->ins_name;
    }

    /**
     * @param string $ins_name
     */
    public function setInsName(string $ins_name): void
    {
        $this->ins_name = $ins_name;
    }


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
     * Method to set the value of field driver_id
     *
     * @param integer $driver_id
     * @return $this
     */
    public function setDriverId($driver_id)
    {
        $this->driver_id = $driver_id;

        return $this;
    }

    /**
     * Method to set the value of field has_licence
     *
     * @param integer $has_licence
     * @return $this
     */
    public function setHasLicence($has_licence)
    {
        $this->has_licence = $has_licence;

        return $this;
    }

    /**
     * Method to set the value of field licence_num
     *
     * @param string $licence_num
     * @return $this
     */
    public function setLicenceNum($licence_num)
    {
        $this->licence_num = $licence_num;

        return $this;
    }

    /**
     * Method to set the value of field valid_starttime
     *
     * @param integer $valid_starttime
     * @return $this
     */
    public function setValidStarttime($valid_starttime)
    {
        $this->valid_starttime = $valid_starttime;

        return $this;
    }

    /**
     * Method to set the value of field valid_endtime
     *
     * @param integer $valid_endtime
     * @return $this
     */
    public function setValidEndtime($valid_endtime)
    {
        $this->valid_endtime = $valid_endtime;

        return $this;
    }

    /**
     * Method to set the value of field licence_score
     *
     * @param integer $licence_score
     * @return $this
     */
    public function setLicenceScore($licence_score)
    {
        $this->licence_score = $licence_score;

        return $this;
    }

    /**
     * Method to set the value of field get_time
     *
     * @param integer $get_time
     * @return $this
     */
    public function setGetTime($get_time)
    {
        $this->get_time = $get_time;

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
     * Returns the value of field driver_id
     *
     * @return integer
     */
    public function getDriverId()
    {
        return $this->driver_id;
    }

    /**
     * Returns the value of field has_licence
     *
     * @return integer
     */
    public function getHasLicence()
    {
        return $this->has_licence;
    }

    /**
     * Returns the value of field licence_num
     *
     * @return string
     */
    public function getLicenceNum()
    {
        return $this->licence_num;
    }

    /**
     * Returns the value of field valid_starttime
     *
     * @return integer
     */
    public function getValidStarttime()
    {
        return $this->valid_starttime;
    }

    /**
     * Returns the value of field valid_endtime
     *
     * @return integer
     */
    public function getValidEndtime()
    {
        return $this->valid_endtime;
    }

    /**
     * Returns the value of field licence_score
     *
     * @return integer
     */
    public function getLicenceScore()
    {
        return $this->licence_score;
    }

    /**
     * Returns the value of field get_time
     *
     * @return integer
     */
    public function getGetTime()
    {
        return $this->get_time;
    }

    /**
     * @return mixed
     */
    public function getFrontImg()
    {
        return $this->front_img;
    }

    /**
     * @param mixed $front_img
     */
    public function setFrontImg($front_img): void
    {
        $this->front_img = $front_img;
    }

    /**
     * @return mixed
     */
    public function getBackImg()
    {
        return $this->back_img;
    }

    /**
     * @param mixed $back_img
     */
    public function setBackImg($back_img): void
    {
        $this->back_img = $back_img;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $back_img
     */
    public function setVersion($version): void
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getIsSend()
    {
        return $this->is_send;
    }

    /**
     * @param mixed $back_img
     */
    public function setIsSend($is_send): void
    {
        $this->is_send = $is_send;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_driver_licence");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_driver_licence';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DriverLicence[]|DriverLicence|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DriverLicence|\Phalcon\Mvc\Model\ResultInterface
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
            'driver_id' => 'driver_id',
            'has_licence' => 'has_licence',
            'licence_num' => 'licence_num',
            'valid_starttime' => 'valid_starttime',
            'valid_endtime' => 'valid_endtime',
            'licence_score' => 'licence_score',
            'get_time' => 'get_time',
            'front_img' =>  'front_img',
            'back_img' =>  'back_img',
            'ins_id' =>  'ins_id',
            'ins_name' =>  'ins_name',
            'version' =>  'version',
            'is_send' => 'is_send',
        ];
    }

}

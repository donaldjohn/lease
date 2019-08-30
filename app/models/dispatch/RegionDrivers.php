<?php
namespace app\models\dispatch;

class RegionDrivers extends BaseModel
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
    protected $region_id;

    /**
     *
     * @var integer
     */
    protected $driver_id;

    /**
     *
     * @var integer
     */
    protected $ins_id;

    /**
     *
     * @var integer
     */
    protected $update_time;

    /**
     *
     * @var integer
     */
    protected $create_time;

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
     * Method to set the value of field region_id
     *
     * @param integer $region_id
     * @return $this
     */
    public function setRegionId($region_id)
    {
        $this->region_id = $region_id;

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
     * Method to set the value of field update_time
     *
     * @param integer $update_time
     * @return $this
     */
    public function setUpdateTime($update_time)
    {
        $this->update_time = $update_time;

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
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field region_id
     *
     * @return integer
     */
    public function getRegionId()
    {
        return $this->region_id;
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
     * Returns the value of field ins_id
     *
     * @return integer
     */
    public function getInsId()
    {
        return $this->ins_id;
    }

    /**
     * Returns the value of field update_time
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
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
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_region_drivers");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_region_drivers';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwRegionDrivers[]|DwRegionDrivers|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwRegionDrivers|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}

<?php
namespace app\models\service;

class RegionVehicle extends BaseModel
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $region_id;

    public $ins_id;

    /**
     *
     * @var integer
     */
    public $vehicle_id;

    /**
     *
     * @var integer
     */
    public $bind_status;

    /**
     *
     * @var integer
     */
    public $driver_id;

    /**
     *
     * @var integer
     */
    public $bind_time;

    /**
     *
     * @var integer
     */
    public $update_time;

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
     * Method to set the value of field vehicle_id
     *
     * @param integer $vehicle_id
     * @return $this
     */
    public function setVehicleId($vehicle_id)
    {
        $this->vehicle_id = $vehicle_id;

        return $this;
    }

    /**
     * Method to set the value of field bind_status
     *
     * @param integer $bind_status
     * @return $this
     */
    public function setBindStatus($bind_status)
    {
        $this->bind_status = $bind_status;

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
     * Method to set the value of field bind_time
     *
     * @param integer $bind_time
     * @return $this
     */
    public function setBindTime($bind_time)
    {
        $this->bind_time = $bind_time;

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
     * Returns the value of field vehicle_id
     *
     * @return integer
     */
    public function getVehicleId()
    {
        return $this->vehicle_id;
    }

    /**
     * Returns the value of field bind_status
     *
     * @return integer
     */
    public function getBindStatus()
    {
        return $this->bind_status;
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
     * Returns the value of field bind_time
     *
     * @return integer
     */
    public function getBindTime()
    {
        return $this->bind_time;
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
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_region_vehicle");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_region_vehicle';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwRegionVehicle[]|DwRegionVehicle|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwRegionVehicle|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}

<?php
namespace app\models\dispatch;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;
class DriverEventBlacklist extends BaseModel
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
    protected $app_id;

    /**
     *
     * @var integer
     */
    protected $driver_id;

    /**
     *
     * @var string
     */
    protected $device_token;

    /**
     *
     * @var integer
     */
    protected $event_id;

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
     * Method to set the value of field app_id
     *
     * @param integer $app_id
     * @return $this
     */
    public function setAppId($app_id)
    {
        $this->app_id = $app_id;

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
     * Method to set the value of field device_token
     *
     * @param string $device_token
     * @return $this
     */
    public function setDeviceToken($device_token)
    {
        $this->device_token = $device_token;

        return $this;
    }

    /**
     * Method to set the value of field event_id
     *
     * @param integer $event_id
     * @return $this
     */
    public function setEventId($event_id)
    {
        $this->event_id = $event_id;

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
     * Returns the value of field app_id
     *
     * @return integer
     */
    public function getAppId()
    {
        return $this->app_id;
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
     * Returns the value of field device_token
     *
     * @return string
     */
    public function getDeviceToken()
    {
        return $this->device_token;
    }

    /**
     * Returns the value of field event_id
     *
     * @return integer
     */
    public function getEventId()
    {
        return $this->event_id;
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
        $this->setSchema("dewin_dispatch");
        $this->setSource("dw_driver_event_blacklist");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_driver_event_blacklist';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DriverEventBlacklist[]|DriverEventBlacklist|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DriverEventBlacklist|\Phalcon\Mvc\Model\ResultInterface
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
            'app_id' => 'app_id',
            'driver_id' => 'driver_id',
            'device_token' => 'device_token',
            'event_id' => 'event_id',
            'create_time' => 'create_time'
        ];
    }

    public function beforeCreate()
    {
        $this->setCreateTime(time());
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

<?php

namespace app\models\users;

class System extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $system_name;

    /**
     *
     * @var integer
     */
    protected $system_type;

    /**
     *
     * @var string
     */
    protected $system_code;

    /**
     *
     * @var integer
     */
    protected $system_status;

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
     * Method to set the value of field system_name
     *
     * @param string $system_name
     * @return $this
     */
    public function setSystemName($system_name)
    {
        $this->system_name = $system_name;

        return $this;
    }

    /**
     * Method to set the value of field system_type
     *
     * @param integer $system_type
     * @return $this
     */
    public function setSystemType($system_type)
    {
        $this->system_type = $system_type;

        return $this;
    }

    /**
     * Method to set the value of field system_code
     *
     * @param string $system_code
     * @return $this
     */
    public function setSystemCode($system_code)
    {
        $this->system_code = $system_code;

        return $this;
    }

    /**
     * Method to set the value of field system_status
     *
     * @param integer $system_status
     * @return $this
     */
    public function setSystemStatus($system_status)
    {
        $this->system_status = $system_status;

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
     * Returns the value of field system_name
     *
     * @return string
     */
    public function getSystemName()
    {
        return $this->system_name;
    }

    /**
     * Returns the value of field system_type
     *
     * @return integer
     */
    public function getSystemType()
    {
        return $this->system_type;
    }

    /**
     * Returns the value of field system_code
     *
     * @return string
     */
    public function getSystemCode()
    {
        return $this->system_code;
    }

    /**
     * Returns the value of field system_status
     *
     * @return integer
     */
    public function getSystemStatus()
    {
        return $this->system_status;
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
        $this->setSchema("dewin_users");
        $this->setSource("dw_system");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_system';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return System[]|System|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return System|\Phalcon\Mvc\Model\ResultInterface
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
            'system_name' => 'system_name',
            'system_type' => 'system_type',
            'system_code' => 'system_code',
            'system_status' => 'system_status',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

}

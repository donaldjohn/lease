<?php

namespace app\models\users;

class Api extends BaseModel
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
    protected $api_name;

    /**
     *
     * @var string
     */
    protected $api_code;

    /**
     *
     * @var integer
     */
    protected $api_status;

    /**
     *
     * @var integer
     */
    protected $is_common;

    /**
     *
     * @var integer
     */
    protected $need_login;

    /**
     *
     * @var integer
     */
    protected $need_permission;

    /**
     *
     * @var integer
     */
    protected $show_priority;

    /**
     *
     * @var integer
     */
    protected $module_id;

    /**
     *
     * @var integer
     */
    protected $api_function;

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
     *
     * @var string
     */
    protected $api_addr;

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
     * Method to set the value of field api_name
     *
     * @param string $api_name
     * @return $this
     */
    public function setApiName($api_name)
    {
        $this->api_name = $api_name;

        return $this;
    }

    /**
     * Method to set the value of field api_code
     *
     * @param string $api_code
     * @return $this
     */
    public function setApiCode($api_code)
    {
        $this->api_code = $api_code;

        return $this;
    }

    /**
     * Method to set the value of field api_status
     *
     * @param integer $api_status
     * @return $this
     */
    public function setApiStatus($api_status)
    {
        $this->api_status = $api_status;

        return $this;
    }

    /**
     * Method to set the value of field is_common
     *
     * @param integer $is_common
     * @return $this
     */
    public function setIsCommon($is_common)
    {
        $this->is_common = $is_common;

        return $this;
    }

    /**
     * Method to set the value of field need_login
     *
     * @param integer $need_login
     * @return $this
     */
    public function setNeedLogin($need_login)
    {
        $this->need_login = $need_login;

        return $this;
    }

    /**
     * Method to set the value of field need_permission
     *
     * @param integer $need_permission
     * @return $this
     */
    public function setNeedPermission($need_permission)
    {
        $this->need_permission = $need_permission;

        return $this;
    }

    /**
     * Method to set the value of field show_priority
     *
     * @param integer $show_priority
     * @return $this
     */
    public function setShowPriority($show_priority)
    {
        $this->show_priority = $show_priority;

        return $this;
    }

    /**
     * Method to set the value of field module_id
     *
     * @param integer $module_id
     * @return $this
     */
    public function setModuleId($module_id)
    {
        $this->module_id = $module_id;

        return $this;
    }

    /**
     * Method to set the value of field api_function
     *
     * @param integer $api_function
     * @return $this
     */
    public function setApiFunction($api_function)
    {
        $this->api_function = $api_function;

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
     * Method to set the value of field api_addr
     *
     * @param string $api_addr
     * @return $this
     */
    public function setApiAddr($api_addr)
    {
        $this->api_addr = $api_addr;

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
     * Returns the value of field api_name
     *
     * @return string
     */
    public function getApiName()
    {
        return $this->api_name;
    }

    /**
     * Returns the value of field api_code
     *
     * @return string
     */
    public function getApiCode()
    {
        return $this->api_code;
    }

    /**
     * Returns the value of field api_status
     *
     * @return integer
     */
    public function getApiStatus()
    {
        return $this->api_status;
    }

    /**
     * Returns the value of field is_common
     *
     * @return integer
     */
    public function getIsCommon()
    {
        return $this->is_common;
    }

    /**
     * Returns the value of field need_login
     *
     * @return integer
     */
    public function getNeedLogin()
    {
        return $this->need_login;
    }

    /**
     * Returns the value of field need_permission
     *
     * @return integer
     */
    public function getNeedPermission()
    {
        return $this->need_permission;
    }

    /**
     * Returns the value of field show_priority
     *
     * @return integer
     */
    public function getShowPriority()
    {
        return $this->show_priority;
    }

    /**
     * Returns the value of field module_id
     *
     * @return integer
     */
    public function getModuleId()
    {
        return $this->module_id;
    }

    /**
     * Returns the value of field api_function
     *
     * @return integer
     */
    public function getApiFunction()
    {
        return $this->api_function;
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
     * Returns the value of field api_addr
     *
     * @return string
     */
    public function getApiAddr()
    {
        return $this->api_addr;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSchema("dewin_users");
        $this->setSource("dw_api");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_api';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Api[]|Api|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Api|\Phalcon\Mvc\Model\ResultInterface
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
            'api_name' => 'api_name',
            'api_code' => 'api_code',
            'api_status' => 'api_status',
            'is_common' => 'is_common',
            'need_login' => 'need_login',
            'need_permission' => 'need_permission',
            'show_priority' => 'show_priority',
            'module_id' => 'module_id',
            'api_function' => 'api_function',
            'create_at' => 'create_at',
            'update_at' => 'update_at',
            'api_addr' => 'api_addr'
        ];
    }

}

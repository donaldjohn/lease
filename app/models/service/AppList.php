<?php
namespace app\models\service;


use Phalcon\Validation;

class AppList extends BaseModel
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
    protected $app_name;

    /**
     *
     * @var string
     */
    protected $app_code;

    /**
     *
     * @var integer
     */
    protected $app_type;

    /**
     *
     * @var integer
     */
    protected $app_status;

    /**
     *
     * @var integer
     */
    protected $is_delete;

    /**
     *
     * @var integer
     */
    protected $create_time;

    /**
     *
     * @var integer
     */
    protected $update_time;


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
     * Method to set the value of field app_name
     *
     * @param string $app_name
     * @return $this
     */
    public function setAppName($app_name)
    {
        $this->app_name = $app_name;

        return $this;
    }

    /**
     * Method to set the value of field app_code
     *
     * @param string $app_code
     * @return $this
     */
    public function setAppCode($app_code)
    {
        $this->app_code = $app_code;

        return $this;
    }

    /**
     * Method to set the value of field app_type
     *
     * @param integer $app_type
     * @return $this
     */
    public function setAppType($app_type)
    {
        $this->app_type = $app_type;

        return $this;
    }

    /**
     * Method to set the value of field app_status
     *
     * @param integer $app_status
     * @return $this
     */
    public function setAppStatus($app_status)
    {
        $this->app_status = $app_status;

        return $this;
    }

    /**
     * Method to set the value of field is_delete
     *
     * @param integer $is_delete
     * @return $this
     */
    public function setIsDelete($is_delete)
    {
        $this->is_delete = $is_delete;

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
     * Returns the value of field app_name
     *
     * @return string
     */
    public function getAppName()
    {
        return $this->app_name;
    }

    /**
     * Returns the value of field app_code
     *
     * @return string
     */
    public function getAppCode()
    {
        return $this->app_code;
    }

    /**
     * Returns the value of field app_type
     *
     * @return integer
     */
    public function getAppType()
    {
        return $this->app_type;
    }

    /**
     * Returns the value of field app_status
     *
     * @return integer
     */
    public function getAppStatus()
    {
        return $this->app_status;
    }

    /**
     * Returns the value of field is_delete
     *
     * @return integer
     */
    public function getIsDelete()
    {
        return $this->is_delete;
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
        $this->setSchema("dewin_service");
        $this->setSource("dw_app_list");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_app_list';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return AppList[]|AppList|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AppList|\Phalcon\Mvc\Model\ResultInterface
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
            'app_name' => 'app_name',
            'app_code' => 'app_code',
            'app_type' => 'app_type',
            'app_status' => 'app_status',
            'is_delete' => 'is_delete',
            'create_time' => 'create_time',
            'update_time' => 'update_time'
        ];
    }


    public function beforeCreate()
    {
        $this->setCreateTime(time());
    }
    public function beforeUpdate()
    {
        $this->setUpdateTime(time());
    }
}

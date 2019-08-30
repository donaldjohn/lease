<?php
namespace app\models\service;


use Phalcon\Validation;
class AppUmeng extends BaseModel
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
    protected $umeng_name;

    /**
     *
     * @var integer
     */
    protected $app_id;

    /**
     *
     * @var string
     */
    protected $package_name;

    /**
     *
     * @var integer
     */
    protected $app_type;

    /**
     *
     * @var string
     */
    protected $appkey;

    /**
     *
     * @var string
     */
    protected $mastersecret;

    /**
     *
     * @var integer
     */
    protected $app_status;

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
     * @var integer
     */
    protected $is_delete;

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
     * Method to set the value of field umeng_name
     *
     * @param string $umeng_name
     * @return $this
     */
    public function setUmengName($umeng_name)
    {
        $this->umeng_name = $umeng_name;

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
     * Method to set the value of field package_name
     *
     * @param string $package_name
     * @return $this
     */
    public function setPackageName($package_name)
    {
        $this->package_name = $package_name;

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
     * Method to set the value of field appkey
     *
     * @param string $appkey
     * @return $this
     */
    public function setAppkey($appkey)
    {
        $this->appkey = $appkey;

        return $this;
    }

    /**
     * Method to set the value of field mastersecret
     *
     * @param string $mastersecret
     * @return $this
     */
    public function setMastersecret($mastersecret)
    {
        $this->mastersecret = $mastersecret;

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
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field umeng_name
     *
     * @return string
     */
    public function getUmengName()
    {
        return $this->umeng_name;
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
     * Returns the value of field package_name
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->package_name;
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
     * Returns the value of field appkey
     *
     * @return string
     */
    public function getAppkey()
    {
        return $this->appkey;
    }

    /**
     * Returns the value of field mastersecret
     *
     * @return string
     */
    public function getMastersecret()
    {
        return $this->mastersecret;
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
     * Returns the value of field is_delete
     *
     * @return integer
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSchema("dewin_service");
        $this->setSource("dw_app_umeng");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_app_umeng';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return AppUmeng[]|AppUmeng|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AppUmeng|\Phalcon\Mvc\Model\ResultInterface
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
            'umeng_name' => 'umeng_name',
            'app_id' => 'app_id',
            'package_name' => 'package_name',
            'app_type' => 'app_type',
            'appkey' => 'appkey',
            'mastersecret' => 'mastersecret',
            'app_status' => 'app_status',
            'create_at' => 'create_at',
            'update_at' => 'update_at',
            'is_delete' => 'is_delete'
        ];
    }

    public function beforeCreate()
    {
        $this->setCreateAt(time());
    }
    public function beforeUpdate()
    {
        $this->setUpdateAt(time());
    }
}

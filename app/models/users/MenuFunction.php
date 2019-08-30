<?php

namespace app\models\users;

class MenuFunction extends BaseModel
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
    protected $menu_id;

    /**
     *
     * @var string
     */
    protected $func_name;

    /**
     *
     * @var string
     */
    protected $func_code;

    /**
     *
     * @var integer
     */
    protected $api_id;

    /**
     *
     * @var integer
     */
    protected $status;

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
     * Method to set the value of field menu_id
     *
     * @param integer $menu_id
     * @return $this
     */
    public function setMenuId($menu_id)
    {
        $this->menu_id = $menu_id;

        return $this;
    }

    /**
     * Method to set the value of field func_name
     *
     * @param string $func_name
     * @return $this
     */
    public function setFuncName($func_name)
    {
        $this->func_name = $func_name;

        return $this;
    }

    /**
     * Method to set the value of field func_code
     *
     * @param string $func_code
     * @return $this
     */
    public function setFuncCode($func_code)
    {
        $this->func_code = $func_code;

        return $this;
    }

    /**
     * Method to set the value of field api_id
     *
     * @param integer $api_id
     * @return $this
     */
    public function setApiId($api_id)
    {
        $this->api_id = $api_id;

        return $this;
    }

    /**
     * Method to set the value of field status
     *
     * @param integer $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     * Returns the value of field menu_id
     *
     * @return integer
     */
    public function getMenuId()
    {
        return $this->menu_id;
    }

    /**
     * Returns the value of field func_name
     *
     * @return string
     */
    public function getFuncName()
    {
        return $this->func_name;
    }

    /**
     * Returns the value of field func_code
     *
     * @return string
     */
    public function getFuncCode()
    {
        return $this->func_code;
    }

    /**
     * Returns the value of field api_id
     *
     * @return integer
     */
    public function getApiId()
    {
        return $this->api_id;
    }

    /**
     * Returns the value of field status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
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
        $this->setSource("dw_menu_function");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_menu_function';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MenuFunction[]|MenuFunction|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MenuFunction|\Phalcon\Mvc\Model\ResultInterface
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
            'menu_id' => 'menu_id',
            'func_name' => 'func_name',
            'func_code' => 'func_code',
            'api_id' => 'api_id',
            'status' => 'status',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

}

<?php

namespace app\models\users;

class Menu extends BaseModel
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
    protected $menu_name;

    /**
     *
     * @var string
     */
    protected $menu_code;

    /**
     *
     * @var string
     */
    protected $menu_system;

    /**
     *
     * @var integer
     */
    protected $menu_type;

    /**
     *
     * @var integer
     */
    protected $parent_id;

    /**
     *
     * @var integer
     */
    protected $menu_status;

    /**
     *
     * @var integer
     */
    protected $menu_level;

    /**
     *
     * @var integer
     */
    protected $menu_order;

    /**
     *
     * @var string
     */
    protected $menu_uri;

    /**
     *
     * @var string
     */
    protected $menu_describe;

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
     * Method to set the value of field menu_name
     *
     * @param string $menu_name
     * @return $this
     */
    public function setMenuName($menu_name)
    {
        $this->menu_name = $menu_name;

        return $this;
    }

    /**
     * Method to set the value of field menu_code
     *
     * @param string $menu_code
     * @return $this
     */
    public function setMenuCode($menu_code)
    {
        $this->menu_code = $menu_code;

        return $this;
    }

    /**
     * Method to set the value of field menu_system
     *
     * @param string $menu_system
     * @return $this
     */
    public function setMenuSystem($menu_system)
    {
        $this->menu_system = $menu_system;

        return $this;
    }

    /**
     * Method to set the value of field menu_type
     *
     * @param integer $menu_type
     * @return $this
     */
    public function setMenuType($menu_type)
    {
        $this->menu_type = $menu_type;

        return $this;
    }

    /**
     * Method to set the value of field parent_id
     *
     * @param integer $parent_id
     * @return $this
     */
    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;

        return $this;
    }

    /**
     * Method to set the value of field menu_status
     *
     * @param integer $menu_status
     * @return $this
     */
    public function setMenuStatus($menu_status)
    {
        $this->menu_status = $menu_status;

        return $this;
    }

    /**
     * Method to set the value of field menu_level
     *
     * @param integer $menu_level
     * @return $this
     */
    public function setMenuLevel($menu_level)
    {
        $this->menu_level = $menu_level;

        return $this;
    }

    /**
     * Method to set the value of field menu_order
     *
     * @param integer $menu_order
     * @return $this
     */
    public function setMenuOrder($menu_order)
    {
        $this->menu_order = $menu_order;

        return $this;
    }

    /**
     * Method to set the value of field menu_uri
     *
     * @param string $menu_uri
     * @return $this
     */
    public function setMenuUri($menu_uri)
    {
        $this->menu_uri = $menu_uri;

        return $this;
    }

    /**
     * Method to set the value of field menu_describe
     *
     * @param string $menu_describe
     * @return $this
     */
    public function setMenuDescribe($menu_describe)
    {
        $this->menu_describe = $menu_describe;

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
     * Returns the value of field menu_name
     *
     * @return string
     */
    public function getMenuName()
    {
        return $this->menu_name;
    }

    /**
     * Returns the value of field menu_code
     *
     * @return string
     */
    public function getMenuCode()
    {
        return $this->menu_code;
    }

    /**
     * Returns the value of field menu_system
     *
     * @return string
     */
    public function getMenuSystem()
    {
        return $this->menu_system;
    }

    /**
     * Returns the value of field menu_type
     *
     * @return integer
     */
    public function getMenuType()
    {
        return $this->menu_type;
    }

    /**
     * Returns the value of field parent_id
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Returns the value of field menu_status
     *
     * @return integer
     */
    public function getMenuStatus()
    {
        return $this->menu_status;
    }

    /**
     * Returns the value of field menu_level
     *
     * @return integer
     */
    public function getMenuLevel()
    {
        return $this->menu_level;
    }

    /**
     * Returns the value of field menu_order
     *
     * @return integer
     */
    public function getMenuOrder()
    {
        return $this->menu_order;
    }

    /**
     * Returns the value of field menu_uri
     *
     * @return string
     */
    public function getMenuUri()
    {
        return $this->menu_uri;
    }

    /**
     * Returns the value of field menu_describe
     *
     * @return string
     */
    public function getMenuDescribe()
    {
        return $this->menu_describe;
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
        $this->setSource("dw_menu");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_menu';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Menu[]|Menu|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Menu|\Phalcon\Mvc\Model\ResultInterface
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
            'menu_name' => 'menu_name',
            'menu_code' => 'menu_code',
            'menu_system' => 'menu_system',
            'menu_type' => 'menu_type',
            'parent_id' => 'parent_id',
            'menu_status' => 'menu_status',
            'menu_level' => 'menu_level',
            'menu_order' => 'menu_order',
            'menu_uri' => 'menu_uri',
            'menu_describe' => 'menu_describe',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

}

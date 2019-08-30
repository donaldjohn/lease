<?php

namespace app\models\users;

class Institution extends BaseModel
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
    protected $is_sub;

    /**
     *
     * @var integer
     */
    protected $group_id;

    /**
     *
     * @var integer
     */
    protected $parent_id;

    /**
     *
     * @var integer
     */
    protected $system_id;

    /**
     *
     * @var string
     */
    protected $ins_name;

    /**
     *
     * @var string
     */
    protected $foreign_table;

    /**
     *
     * @var integer
     */
    protected $type_id;

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
     * Method to set the value of field is_sub
     *
     * @param integer $is_sub
     * @return $this
     */
    public function setIsSub($is_sub)
    {
        $this->is_sub = $is_sub;

        return $this;
    }

    /**
     * Method to set the value of field group_id
     *
     * @param integer $group_id
     * @return $this
     */
    public function setGroupId($group_id)
    {
        $this->group_id = $group_id;

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
     * Method to set the value of field system_id
     *
     * @param integer $system_id
     * @return $this
     */
    public function setSystemId($system_id)
    {
        $this->system_id = $system_id;

        return $this;
    }

    /**
     * Method to set the value of field ins_name
     *
     * @param string $ins_name
     * @return $this
     */
    public function setInsName($ins_name)
    {
        $this->ins_name = $ins_name;

        return $this;
    }

    /**
     * Method to set the value of field foreign_table
     *
     * @param string $foreign_table
     * @return $this
     */
    public function setForeignTable($foreign_table)
    {
        $this->foreign_table = $foreign_table;

        return $this;
    }

    /**
     * Method to set the value of field type_id
     *
     * @param integer $type_id
     * @return $this
     */
    public function setTypeId($type_id)
    {
        $this->type_id = $type_id;

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
     * Returns the value of field is_sub
     *
     * @return integer
     */
    public function getIsSub()
    {
        return $this->is_sub;
    }

    /**
     * Returns the value of field group_id
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->group_id;
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
     * Returns the value of field system_id
     *
     * @return integer
     */
    public function getSystemId()
    {
        return $this->system_id;
    }

    /**
     * Returns the value of field ins_name
     *
     * @return string
     */
    public function getInsName()
    {
        return $this->ins_name;
    }

    /**
     * Returns the value of field foreign_table
     *
     * @return string
     */
    public function getForeignTable()
    {
        return $this->foreign_table;
    }

    /**
     * Returns the value of field type_id
     *
     * @return integer
     */
    public function getTypeId()
    {
        return $this->type_id;
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
        $this->setSource("dw_institution");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_institution';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Institution[]|Institution|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Institution|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }



}

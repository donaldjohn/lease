<?php

namespace app\models\users;

class Group extends BaseModel
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
    protected $group_name;

    /**
     *
     * @var string
     */
    protected $group_code;

    /**
     *
     * @var integer
     */
    protected $group_type;

    /**
     *
     * @var integer
     */
    protected $group_status;

    /**
     *
     * @var string
     */
    protected $group_remark;

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
     * Method to set the value of field group_name
     *
     * @param string $group_name
     * @return $this
     */
    public function setGroupName($group_name)
    {
        $this->group_name = $group_name;

        return $this;
    }

    /**
     * Method to set the value of field group_code
     *
     * @param string $group_code
     * @return $this
     */
    public function setGroupCode($group_code)
    {
        $this->group_code = $group_code;

        return $this;
    }

    /**
     * Method to set the value of field group_type
     *
     * @param integer $group_type
     * @return $this
     */
    public function setGroupType($group_type)
    {
        $this->group_type = $group_type;

        return $this;
    }

    /**
     * Method to set the value of field group_status
     *
     * @param integer $group_status
     * @return $this
     */
    public function setGroupStatus($group_status)
    {
        $this->group_status = $group_status;

        return $this;
    }

    /**
     * Method to set the value of field group_remark
     *
     * @param string $group_remark
     * @return $this
     */
    public function setGroupRemark($group_remark)
    {
        $this->group_remark = $group_remark;

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
     * Returns the value of field group_name
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->group_name;
    }

    /**
     * Returns the value of field group_code
     *
     * @return string
     */
    public function getGroupCode()
    {
        return $this->group_code;
    }

    /**
     * Returns the value of field group_type
     *
     * @return integer
     */
    public function getGroupType()
    {
        return $this->group_type;
    }

    /**
     * Returns the value of field group_status
     *
     * @return integer
     */
    public function getGroupStatus()
    {
        return $this->group_status;
    }

    /**
     * Returns the value of field group_remark
     *
     * @return string
     */
    public function getGroupRemark()
    {
        return $this->group_remark;
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
        $this->setSource("dw_group");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_group';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Group[]|Group|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Group|\Phalcon\Mvc\Model\ResultInterface
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
            'group_name' => 'group_name',
            'group_code' => 'group_code',
            'group_type' => 'group_type',
            'group_status' => 'group_status',
            'group_remark' => 'group_remark',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

}

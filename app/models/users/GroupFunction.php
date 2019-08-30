<?php

namespace app\models\users;

class GroupFunction extends BaseModel
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
    protected $group_id;

    /**
     *
     * @var string
     */
    protected $menufunc_id;

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
     * Method to set the value of field group_id
     *
     * @param string $group_id
     * @return $this
     */
    public function setGroupId($group_id)
    {
        $this->group_id = $group_id;

        return $this;
    }

    /**
     * Method to set the value of field menufunc_id
     *
     * @param string $menufunc_id
     * @return $this
     */
    public function setMenufuncId($menufunc_id)
    {
        $this->menufunc_id = $menufunc_id;

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
     * Returns the value of field group_id
     *
     * @return string
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * Returns the value of field menufunc_id
     *
     * @return string
     */
    public function getMenufuncId()
    {
        return $this->menufunc_id;
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
        $this->setSource("dw_group_function");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_group_function';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return GroupFunction[]|GroupFunction|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return GroupFunction|\Phalcon\Mvc\Model\ResultInterface
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
            'group_id' => 'group_id',
            'menufunc_id' => 'menufunc_id',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

}

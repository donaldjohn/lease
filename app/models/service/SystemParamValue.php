<?php
namespace app\models\service;

class SystemParamValue extends BaseModel
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
    protected $city_id;

    /**
     *
     * @var integer
     */
    protected $ins_id;

    /**
     *
     * @var integer
     */
    protected $system_param_id;

    /**
     *
     * @var integer
     */
    protected $value;

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
     * Method to set the value of field city_id
     *
     * @param integer $city_id
     * @return $this
     */
    public function setCityId($city_id)
    {
        $this->city_id = $city_id;

        return $this;
    }

    /**
     * Method to set the value of field ins_id
     *
     * @param integer $ins_id
     * @return $this
     */
    public function setInsId($ins_id)
    {
        $this->ins_id = $ins_id;

        return $this;
    }

    /**
     * Method to set the value of field system_param_id
     *
     * @param integer $system_param_id
     * @return $this
     */
    public function setSystemParamId($system_param_id)
    {
        $this->system_param_id = $system_param_id;

        return $this;
    }

    /**
     * Method to set the value of field value
     *
     * @param integer $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

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
     * Returns the value of field city_id
     *
     * @return integer
     */
    public function getCityId()
    {
        return $this->city_id;
    }

    /**
     * Returns the value of field ins_id
     *
     * @return integer
     */
    public function getInsId()
    {
        return $this->ins_id;
    }

    /**
     * Returns the value of field system_param_id
     *
     * @return integer
     */
    public function getSystemParamId()
    {
        return $this->system_param_id;
    }

    /**
     * Returns the value of field value
     *
     * @return integer
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_system_param_value");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_system_param_value';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return SystemParamValue[]|SystemParamValue|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return SystemParamValue|\Phalcon\Mvc\Model\ResultInterface
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
            'city_id' => 'city_id',
            'ins_id' => 'ins_id',
            'system_param_id' => 'system_param_id',
            'value' => 'value'
        ];
    }

}

<?php
namespace app\models\dispatch;

class Region extends BaseModel
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
    protected $ins_id;

    /**
     *
     * @var integer
     */
    protected $region_level;

    /**
     *
     * @var integer
     */
    protected $region_status;

    /**
     *
     * @var integer
     */
    protected $parent_id;

    /**
     *
     * @var string
     */
    protected $region_code;

    /**
     *
     * @var string
     */
    protected $region_name;

    /**
     *
     * @var string
     */
    protected $region_remark;

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
    protected $region_type;

    /**
     *
     * @var integer
     */
    protected $provice_id;

    /**
     *
     * @var integer
     */
    protected $city_id;

    /**
     *
     * @var string
     */
    protected $address;

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
     * Method to set the value of field region_level
     *
     * @param integer $region_level
     * @return $this
     */
    public function setRegionLevel($region_level)
    {
        $this->region_level = $region_level;

        return $this;
    }

    /**
     * Method to set the value of field region_status
     *
     * @param integer $region_status
     * @return $this
     */
    public function setRegionStatus($region_status)
    {
        $this->region_status = $region_status;

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
     * Method to set the value of field region_code
     *
     * @param string $region_code
     * @return $this
     */
    public function setRegionCode($region_code)
    {
        $this->region_code = $region_code;

        return $this;
    }

    /**
     * Method to set the value of field region_name
     *
     * @param string $region_name
     * @return $this
     */
    public function setRegionName($region_name)
    {
        $this->region_name = $region_name;

        return $this;
    }

    /**
     * Method to set the value of field region_remark
     *
     * @param string $region_remark
     * @return $this
     */
    public function setRegionRemark($region_remark)
    {
        $this->region_remark = $region_remark;

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
     * Method to set the value of field region_type
     *
     * @param integer $region_type
     * @return $this
     */
    public function setRegionType($region_type)
    {
        $this->region_type = $region_type;

        return $this;
    }

    /**
     * Method to set the value of field provice_id
     *
     * @param integer $provice_id
     * @return $this
     */
    public function setProviceId($provice_id)
    {
        $this->provice_id = $provice_id;

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
     * Method to set the value of field address
     *
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;

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
     * Returns the value of field ins_id
     *
     * @return integer
     */
    public function getInsId()
    {
        return $this->ins_id;
    }

    /**
     * Returns the value of field region_level
     *
     * @return integer
     */
    public function getRegionLevel()
    {
        return $this->region_level;
    }

    /**
     * Returns the value of field region_status
     *
     * @return integer
     */
    public function getRegionStatus()
    {
        return $this->region_status;
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
     * Returns the value of field region_code
     *
     * @return string
     */
    public function getRegionCode()
    {
        return $this->region_code;
    }

    /**
     * Returns the value of field region_name
     *
     * @return string
     */
    public function getRegionName()
    {
        return $this->region_name;
    }

    /**
     * Returns the value of field region_remark
     *
     * @return string
     */
    public function getRegionRemark()
    {
        return $this->region_remark;
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
     * Returns the value of field region_type
     *
     * @return integer
     */
    public function getRegionType()
    {
        return $this->region_type;
    }

    /**
     * Returns the value of field provice_id
     *
     * @return integer
     */
    public function getProviceId()
    {
        return $this->provice_id;
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
     * Returns the value of field address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_region");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_region';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwRegion[]|DwRegion|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwRegion|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}

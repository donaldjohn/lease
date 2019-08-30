<?php

namespace app\models\users;

class Company extends BaseModel
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
     * @var string
     */
    protected $company_name;

    /**
     *
     * @var string
     */
    protected $company_type;

    /**
     *
     * @var string
     */
    protected $legal_person;

    /**
     *
     * @var string
     */
    protected $scale;

    /**
     *
     * @var string
     */
    protected $org_code;

    /**
     *
     * @var string
     */
    protected $reg_mark;

    /**
     *
     * @var string
     */
    protected $bank_name;

    /**
     *
     * @var string
     */
    protected $bank_owner;

    /**
     *
     * @var string
     */
    protected $bank_card;

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @var string
     */
    protected $link_man;

    /**
     *
     * @var string
     */
    protected $link_card;

    /**
     *
     * @var string
     */
    protected $link_phone;

    /**
     *
     * @var string
     */
    protected $link_tel;

    /**
     *
     * @var string
     */
    protected $link_mail;

    /**
     *
     * @var integer
     */
    protected $province_id;

    /**
     *
     * @var integer
     */
    protected $city_id;

    /**
     *
     * @var integer
     */
    protected $area_id;

    /**
     *
     * @var string
     */
    protected $address;

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
     * Method to set the value of field company_name
     *
     * @param string $company_name
     * @return $this
     */
    public function setCompanyName($company_name)
    {
        $this->company_name = $company_name;

        return $this;
    }

    /**
     * Method to set the value of field company_type
     *
     * @param string $company_type
     * @return $this
     */
    public function setCompanyType($company_type)
    {
        $this->company_type = $company_type;

        return $this;
    }

    /**
     * Method to set the value of field legal_person
     *
     * @param string $legal_person
     * @return $this
     */
    public function setLegalPerson($legal_person)
    {
        $this->legal_person = $legal_person;

        return $this;
    }

    /**
     * Method to set the value of field scale
     *
     * @param string $scale
     * @return $this
     */
    public function setScale($scale)
    {
        $this->scale = $scale;

        return $this;
    }

    /**
     * Method to set the value of field org_code
     *
     * @param string $org_code
     * @return $this
     */
    public function setOrgCode($org_code)
    {
        $this->org_code = $org_code;

        return $this;
    }

    /**
     * Method to set the value of field reg_mark
     *
     * @param string $reg_mark
     * @return $this
     */
    public function setRegMark($reg_mark)
    {
        $this->reg_mark = $reg_mark;

        return $this;
    }

    /**
     * Method to set the value of field bank_name
     *
     * @param string $bank_name
     * @return $this
     */
    public function setBankName($bank_name)
    {
        $this->bank_name = $bank_name;

        return $this;
    }

    /**
     * Method to set the value of field bank_owner
     *
     * @param string $bank_owner
     * @return $this
     */
    public function setBankOwner($bank_owner)
    {
        $this->bank_owner = $bank_owner;

        return $this;
    }

    /**
     * Method to set the value of field bank_card
     *
     * @param string $bank_card
     * @return $this
     */
    public function setBankCard($bank_card)
    {
        $this->bank_card = $bank_card;

        return $this;
    }

    /**
     * Method to set the value of field scope
     *
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Method to set the value of field link_man
     *
     * @param string $link_man
     * @return $this
     */
    public function setLinkMan($link_man)
    {
        $this->link_man = $link_man;

        return $this;
    }

    /**
     * Method to set the value of field link_card
     *
     * @param string $link_card
     * @return $this
     */
    public function setLinkCard($link_card)
    {
        $this->link_card = $link_card;

        return $this;
    }

    /**
     * Method to set the value of field link_phone
     *
     * @param string $link_phone
     * @return $this
     */
    public function setLinkPhone($link_phone)
    {
        $this->link_phone = $link_phone;

        return $this;
    }

    /**
     * Method to set the value of field link_tel
     *
     * @param string $link_tel
     * @return $this
     */
    public function setLinkTel($link_tel)
    {
        $this->link_tel = $link_tel;

        return $this;
    }

    /**
     * Method to set the value of field link_mail
     *
     * @param string $link_mail
     * @return $this
     */
    public function setLinkMail($link_mail)
    {
        $this->link_mail = $link_mail;

        return $this;
    }

    /**
     * Method to set the value of field province_id
     *
     * @param integer $province_id
     * @return $this
     */
    public function setProvinceId($province_id)
    {
        $this->province_id = $province_id;

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
     * Method to set the value of field area_id
     *
     * @param integer $area_id
     * @return $this
     */
    public function setAreaId($area_id)
    {
        $this->area_id = $area_id;

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
     * Returns the value of field ins_id
     *
     * @return integer
     */
    public function getInsId()
    {
        return $this->ins_id;
    }

    /**
     * Returns the value of field company_name
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * Returns the value of field company_type
     *
     * @return string
     */
    public function getCompanyType()
    {
        return $this->company_type;
    }

    /**
     * Returns the value of field legal_person
     *
     * @return string
     */
    public function getLegalPerson()
    {
        return $this->legal_person;
    }

    /**
     * Returns the value of field scale
     *
     * @return string
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Returns the value of field org_code
     *
     * @return string
     */
    public function getOrgCode()
    {
        return $this->org_code;
    }

    /**
     * Returns the value of field reg_mark
     *
     * @return string
     */
    public function getRegMark()
    {
        return $this->reg_mark;
    }

    /**
     * Returns the value of field bank_name
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Returns the value of field bank_owner
     *
     * @return string
     */
    public function getBankOwner()
    {
        return $this->bank_owner;
    }

    /**
     * Returns the value of field bank_card
     *
     * @return string
     */
    public function getBankCard()
    {
        return $this->bank_card;
    }

    /**
     * Returns the value of field scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Returns the value of field link_man
     *
     * @return string
     */
    public function getLinkMan()
    {
        return $this->link_man;
    }

    /**
     * Returns the value of field link_card
     *
     * @return string
     */
    public function getLinkCard()
    {
        return $this->link_card;
    }

    /**
     * Returns the value of field link_phone
     *
     * @return string
     */
    public function getLinkPhone()
    {
        return $this->link_phone;
    }

    /**
     * Returns the value of field link_tel
     *
     * @return string
     */
    public function getLinkTel()
    {
        return $this->link_tel;
    }

    /**
     * Returns the value of field link_mail
     *
     * @return string
     */
    public function getLinkMail()
    {
        return $this->link_mail;
    }

    /**
     * Returns the value of field province_id
     *
     * @return integer
     */
    public function getProvinceId()
    {
        return $this->province_id;
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
     * Returns the value of field area_id
     *
     * @return integer
     */
    public function getAreaId()
    {
        return $this->area_id;
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
        $this->setSource("dw_company");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_company';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Company[]|Company|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Company|\Phalcon\Mvc\Model\ResultInterface
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
            'ins_id' => 'ins_id',
            'company_name' => 'company_name',
            'company_type' => 'company_type',
            'legal_person' => 'legal_person',
            'scale' => 'scale',
            'org_code' => 'org_code',
            'reg_mark' => 'reg_mark',
            'bank_name' => 'bank_name',
            'bank_owner' => 'bank_owner',
            'bank_card' => 'bank_card',
            'scope' => 'scope',
            'link_man' => 'link_man',
            'link_card' => 'link_card',
            'link_phone' => 'link_phone',
            'link_tel' => 'link_tel',
            'link_mail' => 'link_mail',
            'province_id' => 'province_id',
            'city_id' => 'city_id',
            'area_id' => 'area_id',
            'address' => 'address',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

}

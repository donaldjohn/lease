<?php

namespace app\models\users;

class Store extends BaseModel
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
    protected $store_name;

    /**
     *
     * @var string
     */
    protected $legal_person;

    /**
     *
     * @var double
     */
    protected $lat;

    /**
     *
     * @var double
     */
    protected $lng;

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @var string
     */
    protected $img_url;

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
     * Method to set the value of field store_name
     *
     * @param string $store_name
     * @return $this
     */
    public function setStoreName($store_name)
    {
        $this->store_name = $store_name;

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
     * Method to set the value of field lat
     *
     * @param double $lat
     * @return $this
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Method to set the value of field lng
     *
     * @param double $lng
     * @return $this
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

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
     * Method to set the value of field img_url
     *
     * @param string $img_url
     * @return $this
     */
    public function setImgUrl($img_url)
    {
        $this->img_url = $img_url;

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
     * Returns the value of field store_name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->store_name;
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
     * Returns the value of field lat
     *
     * @return double
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Returns the value of field lng
     *
     * @return double
     */
    public function getLng()
    {
        return $this->lng;
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
     * Returns the value of field img_url
     *
     * @return string
     */
    public function getImgUrl()
    {
        return $this->img_url;
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
        $this->setSource("dw_store");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_store';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Store[]|Store|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Store|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    public static function getStoreNameById($store_id)
    {
        $res = parent::findFirst($store_id);
        if ($res) {
            return $res->store_name;
        } else {
            return '--';
        }
    }

}

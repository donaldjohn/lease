<?php
namespace app\models\users;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;

class User extends BaseModel
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $user_name;

    /**
     *
     * @var string
     */
    public $password;

    /**
     *
     * @var string
     */
    public $real_name;

    /**
     *
     * @var string
     */
    public $phone;

    /**
     *
     * @var integer
     */
    public $group_id;

    /**
     *
     * @var integer
     */
    public $role_id;

    /**
     *
     * @var integer
     */
    public $parent_id;

    /**
     *
     * @var integer
     */
    public $is_administrator;

    /**
     *
     * @var integer
     */
    public $user_status;

    /**
     *
     * @var integer
     */
    public $create_at;

    /**
     *
     * @var integer
     */
    public $update_at;

    /**
     *
     * @var integer
     */
    public $last_login;

    /**
     *
     * @var string
     */
    public $last_ip;

    /**
     *
     * @var integer
     */
    public $user_type;

    /**
     *
     * @var string
     */
    public $id_card;

    /**
     *
     * @var integer
     */
    public $sex;

    /**
     *
     * @var string
     */
    public $user_remark;

    /**
     *
     * @var integer
     */
    public $is_delete;

    /**
     *
     * @var string
     */
    public $email;

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
     * Method to set the value of field user_name
     *
     * @param string $user_name
     * @return $this
     */
    public function setUserName($user_name)
    {
        $this->user_name = $user_name;

        return $this;
    }

    /**
     * Method to set the value of field password
     *
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Method to set the value of field real_name
     *
     * @param string $real_name
     * @return $this
     */
    public function setRealName($real_name)
    {
        $this->real_name = $real_name;

        return $this;
    }

    /**
     * Method to set the value of field phone
     *
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

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
     * Method to set the value of field role_id
     *
     * @param integer $role_id
     * @return $this
     */
    public function setRoleId($role_id)
    {
        $this->role_id = $role_id;

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
     * Method to set the value of field is_administrator
     *
     * @param integer $is_administrator
     * @return $this
     */
    public function setIsAdministrator($is_administrator)
    {
        $this->is_administrator = $is_administrator;

        return $this;
    }

    /**
     * Method to set the value of field user_status
     *
     * @param integer $user_status
     * @return $this
     */
    public function setUserStatus($user_status)
    {
        $this->user_status = $user_status;

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
     * Method to set the value of field last_login
     *
     * @param integer $last_login
     * @return $this
     */
    public function setLastLogin($last_login)
    {
        $this->last_login = $last_login;

        return $this;
    }

    /**
     * Method to set the value of field last_ip
     *
     * @param string $last_ip
     * @return $this
     */
    public function setLastIp($last_ip)
    {
        $this->last_ip = $last_ip;

        return $this;
    }

    /**
     * Method to set the value of field user_type
     *
     * @param integer $user_type
     * @return $this
     */
    public function setUserType($user_type)
    {
        $this->user_type = $user_type;

        return $this;
    }

    /**
     * Method to set the value of field id_card
     *
     * @param string $id_card
     * @return $this
     */
    public function setIdCard($id_card)
    {
        $this->id_card = $id_card;

        return $this;
    }

    /**
     * Method to set the value of field sex
     *
     * @param integer $sex
     * @return $this
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Method to set the value of field user_remark
     *
     * @param string $user_remark
     * @return $this
     */
    public function setUserRemark($user_remark)
    {
        $this->user_remark = $user_remark;

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
     * Method to set the value of field email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

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
     * Returns the value of field user_name
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Returns the value of field password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the value of field real_name
     *
     * @return string
     */
    public function getRealName()
    {
        return $this->real_name;
    }

    /**
     * Returns the value of field phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
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
     * Returns the value of field role_id
     *
     * @return integer
     */
    public function getRoleId()
    {
        return $this->role_id;
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
     * Returns the value of field is_administrator
     *
     * @return integer
     */
    public function getIsAdministrator()
    {
        return $this->is_administrator;
    }

    /**
     * Returns the value of field user_status
     *
     * @return integer
     */
    public function getUserStatus()
    {
        return $this->user_status;
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
     * Returns the value of field last_login
     *
     * @return integer
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * Returns the value of field last_ip
     *
     * @return string
     */
    public function getLastIp()
    {
        return $this->last_ip;
    }

    /**
     * Returns the value of field user_type
     *
     * @return integer
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * Returns the value of field id_card
     *
     * @return string
     */
    public function getIdCard()
    {
        return $this->id_card;
    }

    /**
     * Returns the value of field sex
     *
     * @return integer
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Returns the value of field user_remark
     *
     * @return string
     */
    public function getUserRemark()
    {
        return $this->user_remark;
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
     * Returns the value of field email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Validations and business logic
     *
     * @return boolean
     */
    public function validation()
    {
        $validator = new Validation();

        $validator->add(
            'email',
            new EmailValidator(
                [
                    'model'   => $this,
                    'message' => 'Please enter a correct email address',
                ]
            )
        );

        return $this->validate($validator);
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSchema("dewin_users");
        $this->setSource("dw_user");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_user';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return User[]|User|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return User|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
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

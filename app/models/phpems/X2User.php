<?php
namespace app\models\phpems;

class X2User extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $userid;

    /**
     *
     * @var string
     */
    protected $useropenid = '';

    /**
     *
     * @var string
     */
    protected $username;

    /**
     *
     * @var string
     */
    protected $useremail = '';

    /**
     *
     * @var string
     */
    protected $userpassword;

    /**
     *
     * @var integer
     */
    protected $usercoin = '';

    /**
     *
     * @var string
     */
    protected $userregip = '';

    /**
     *
     * @var integer
     */
    protected $userregtime = '';

    /**
     *
     * @var integer
     */
    protected $userlogtime = '';

    /**
     *
     * @var integer
     */
    protected $userverifytime = '';

    /**
     *
     * @var integer
     */
    protected $usergroupid;

    /**
     *
     * @var integer
     */
    protected $usermoduleid = '';

    /**
     *
     * @var string
     */
    protected $manager_apps = '';

    /**
     *
     * @var string
     */
    protected $usertruename = '';

    /**
     *
     * @var string
     */
    protected $normal_favor = '';

    /**
     *
     * @var string
     */
    protected $teacher_subjects = '';

    /**
     *
     * @var string
     */
    protected $userprofile = '';

    /**
     *
     * @var string
     */
    protected $userpassport = '';

    /**
     *
     * @var string
     */
    protected $usergender = '';

    /**
     *
     * @var string
     */
    protected $userphone = '';

    /**
     *
     * @var string
     */
    protected $userdegree = '';

    /**
     *
     * @var string
     */
    protected $useraddress = '';

    /**
     *
     * @var string
     */
    protected $userphoto = '';

    /**
     *
     * @var integer
     */
    protected $ins_id;

    protected $parent_ins_id;

    protected $parent_ins_name;

    /**
     * @return mixed
     */
    public function getParentInsId()
    {
        return $this->parent_ins_id;
    }

    /**
     * @param mixed $parent_ins_id
     */
    public function setParentInsId($parent_ins_id): void
    {
        $this->parent_ins_id = $parent_ins_id;
    }

    /**
     * @return mixed
     */
    public function getParentInsName()
    {
        return $this->parent_ins_name;
    }

    /**
     * @param mixed $parent_ins_name
     */
    public function setParentInsName($parent_ins_name): void
    {
        $this->parent_ins_name = $parent_ins_name;
    }



    /**
     * Method to set the value of field userid
     *
     * @param integer $userid
     * @return $this
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Method to set the value of field useropenid
     *
     * @param string $useropenid
     * @return $this
     */
    public function setUseropenid($useropenid)
    {
        $this->useropenid = $useropenid;

        return $this;
    }

    /**
     * Method to set the value of field username
     *
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Method to set the value of field useremail
     *
     * @param string $useremail
     * @return $this
     */
    public function setUseremail($useremail)
    {
        $this->useremail = $useremail;

        return $this;
    }

    /**
     * Method to set the value of field userpassword
     *
     * @param string $userpassword
     * @return $this
     */
    public function setUserpassword($userpassword)
    {
        $this->userpassword = $userpassword;

        return $this;
    }

    /**
     * Method to set the value of field usercoin
     *
     * @param integer $usercoin
     * @return $this
     */
    public function setUsercoin($usercoin)
    {
        $this->usercoin = $usercoin;

        return $this;
    }

    /**
     * Method to set the value of field userregip
     *
     * @param string $userregip
     * @return $this
     */
    public function setUserregip($userregip)
    {
        $this->userregip = $userregip;

        return $this;
    }

    /**
     * Method to set the value of field userregtime
     *
     * @param integer $userregtime
     * @return $this
     */
    public function setUserregtime($userregtime)
    {
        $this->userregtime = $userregtime;

        return $this;
    }

    /**
     * Method to set the value of field userlogtime
     *
     * @param integer $userlogtime
     * @return $this
     */
    public function setUserlogtime($userlogtime)
    {
        $this->userlogtime = $userlogtime;

        return $this;
    }

    /**
     * Method to set the value of field userverifytime
     *
     * @param integer $userverifytime
     * @return $this
     */
    public function setUserverifytime($userverifytime)
    {
        $this->userverifytime = $userverifytime;

        return $this;
    }

    /**
     * Method to set the value of field usergroupid
     *
     * @param integer $usergroupid
     * @return $this
     */
    public function setUsergroupid($usergroupid)
    {
        $this->usergroupid = $usergroupid;

        return $this;
    }

    /**
     * Method to set the value of field usermoduleid
     *
     * @param integer $usermoduleid
     * @return $this
     */
    public function setUsermoduleid($usermoduleid)
    {
        $this->usermoduleid = $usermoduleid;

        return $this;
    }

    /**
     * Method to set the value of field manager_apps
     *
     * @param string $manager_apps
     * @return $this
     */
    public function setManagerApps($manager_apps)
    {
        $this->manager_apps = $manager_apps;

        return $this;
    }

    /**
     * Method to set the value of field usertruename
     *
     * @param string $usertruename
     * @return $this
     */
    public function setUsertruename($usertruename)
    {
        $this->usertruename = $usertruename;

        return $this;
    }

    /**
     * Method to set the value of field normal_favor
     *
     * @param string $normal_favor
     * @return $this
     */
    public function setNormalFavor($normal_favor)
    {
        $this->normal_favor = $normal_favor;

        return $this;
    }

    /**
     * Method to set the value of field teacher_subjects
     *
     * @param string $teacher_subjects
     * @return $this
     */
    public function setTeacherSubjects($teacher_subjects)
    {
        $this->teacher_subjects = $teacher_subjects;

        return $this;
    }

    /**
     * Method to set the value of field userprofile
     *
     * @param string $userprofile
     * @return $this
     */
    public function setUserprofile($userprofile)
    {
        $this->userprofile = $userprofile;

        return $this;
    }

    /**
     * Method to set the value of field userpassport
     *
     * @param string $userpassport
     * @return $this
     */
    public function setUserpassport($userpassport)
    {
        $this->userpassport = $userpassport;

        return $this;
    }

    /**
     * Method to set the value of field usergender
     *
     * @param string $usergender
     * @return $this
     */
    public function setUsergender($usergender)
    {
        $this->usergender = $usergender;

        return $this;
    }

    /**
     * Method to set the value of field userphone
     *
     * @param string $userphone
     * @return $this
     */
    public function setUserphone($userphone)
    {
        $this->userphone = $userphone;

        return $this;
    }

    /**
     * Method to set the value of field userdegree
     *
     * @param string $userdegree
     * @return $this
     */
    public function setUserdegree($userdegree)
    {
        $this->userdegree = $userdegree;

        return $this;
    }

    /**
     * Method to set the value of field useraddress
     *
     * @param string $useraddress
     * @return $this
     */
    public function setUseraddress($useraddress)
    {
        $this->useraddress = $useraddress;

        return $this;
    }

    /**
     * Method to set the value of field userphoto
     *
     * @param string $userphoto
     * @return $this
     */
    public function setUserphoto($userphoto)
    {
        $this->userphoto = $userphoto;

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
     * Returns the value of field userid
     *
     * @return integer
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Returns the value of field useropenid
     *
     * @return string
     */
    public function getUseropenid()
    {
        return $this->useropenid;
    }

    /**
     * Returns the value of field username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the value of field useremail
     *
     * @return string
     */
    public function getUseremail()
    {
        return $this->useremail;
    }

    /**
     * Returns the value of field userpassword
     *
     * @return string
     */
    public function getUserpassword()
    {
        return $this->userpassword;
    }

    /**
     * Returns the value of field usercoin
     *
     * @return integer
     */
    public function getUsercoin()
    {
        return $this->usercoin;
    }

    /**
     * Returns the value of field userregip
     *
     * @return string
     */
    public function getUserregip()
    {
        return $this->userregip;
    }

    /**
     * Returns the value of field userregtime
     *
     * @return integer
     */
    public function getUserregtime()
    {
        return $this->userregtime;
    }

    /**
     * Returns the value of field userlogtime
     *
     * @return integer
     */
    public function getUserlogtime()
    {
        return $this->userlogtime;
    }

    /**
     * Returns the value of field userverifytime
     *
     * @return integer
     */
    public function getUserverifytime()
    {
        return $this->userverifytime;
    }

    /**
     * Returns the value of field usergroupid
     *
     * @return integer
     */
    public function getUsergroupid()
    {
        return $this->usergroupid;
    }

    /**
     * Returns the value of field usermoduleid
     *
     * @return integer
     */
    public function getUsermoduleid()
    {
        return $this->usermoduleid;
    }

    /**
     * Returns the value of field manager_apps
     *
     * @return string
     */
    public function getManagerApps()
    {
        return $this->manager_apps;
    }

    /**
     * Returns the value of field usertruename
     *
     * @return string
     */
    public function getUsertruename()
    {
        return $this->usertruename;
    }

    /**
     * Returns the value of field normal_favor
     *
     * @return string
     */
    public function getNormalFavor()
    {
        return $this->normal_favor;
    }

    /**
     * Returns the value of field teacher_subjects
     *
     * @return string
     */
    public function getTeacherSubjects()
    {
        return $this->teacher_subjects;
    }

    /**
     * Returns the value of field userprofile
     *
     * @return string
     */
    public function getUserprofile()
    {
        return $this->userprofile;
    }

    /**
     * Returns the value of field userpassport
     *
     * @return string
     */
    public function getUserpassport()
    {
        return $this->userpassport;
    }

    /**
     * Returns the value of field usergender
     *
     * @return string
     */
    public function getUsergender()
    {
        return $this->usergender;
    }

    /**
     * Returns the value of field userphone
     *
     * @return string
     */
    public function getUserphone()
    {
        return $this->userphone;
    }

    /**
     * Returns the value of field userdegree
     *
     * @return string
     */
    public function getUserdegree()
    {
        return $this->userdegree;
    }

    /**
     * Returns the value of field useraddress
     *
     * @return string
     */
    public function getUseraddress()
    {
        return $this->useraddress;
    }

    /**
     * Returns the value of field userphoto
     *
     * @return string
     */
    public function getUserphoto()
    {
        return $this->userphoto;
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
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("x2_user");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_user';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2User[]|X2User|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2User|\Phalcon\Mvc\Model\ResultInterface
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
            'userid' => 'userid',
            'useropenid' => 'useropenid',
            'username' => 'username',
            'useremail' => 'useremail',
            'userpassword' => 'userpassword',
            'usercoin' => 'usercoin',
            'userregip' => 'userregip',
            'userregtime' => 'userregtime',
            'userlogtime' => 'userlogtime',
            'userverifytime' => 'userverifytime',
            'usergroupid' => 'usergroupid',
            'usermoduleid' => 'usermoduleid',
            'manager_apps' => 'manager_apps',
            'usertruename' => 'usertruename',
            'normal_favor' => 'normal_favor',
            'teacher_subjects' => 'teacher_subjects',
            'userprofile' => 'userprofile',
            'userpassport' => 'userpassport',
            'usergender' => 'usergender',
            'userphone' => 'userphone',
            'userdegree' => 'userdegree',
            'useraddress' => 'useraddress',
            'userphoto' => 'userphoto',
            'ins_id' => 'ins_id',
            'parent_ins_id' => 'parent_ins_id',
            'parent_ins_name' => 'parent_ins_name'
        ];
    }

}

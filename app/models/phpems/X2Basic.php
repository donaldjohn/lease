<?php
namespace app\models\phpems;
class X2Basic extends BaseModel
{

    /**
     *
     * @var integer
     */
    protected $basicid;

    /**
     *
     * @var string
     */
    protected $basic;

    /**
     *
     * @var integer
     */
    protected $basicareaid;

    /**
     *
     * @var integer
     */
    protected $basicsubjectid;

    /**
     *
     * @var string
     */
    protected $basicsection;

    /**
     *
     * @var string
     */
    protected $basicknows;

    /**
     *
     * @var string
     */
    protected $basicexam;

    /**
     *
     * @var string
     */
    protected $basicapi;

    /**
     *
     * @var integer
     */
    protected $basicdemo;

    /**
     *
     * @var string
     */
    protected $basicthumb;

    /**
     *
     * @var string
     */
    protected $basicprice;

    /**
     *
     * @var integer
     */
    protected $basicclosed;

    /**
     *
     * @var string
     */
    protected $basicdescribe;

    /**
     *
     * @var integer
     */
    protected $usednumber;

    /**
     * Method to set the value of field basicid
     *
     * @param integer $basicid
     * @return $this
     */
    public function setBasicid($basicid)
    {
        $this->basicid = $basicid;

        return $this;
    }

    /**
     * Method to set the value of field basic
     *
     * @param string $basic
     * @return $this
     */
    public function setBasic($basic)
    {
        $this->basic = $basic;

        return $this;
    }

    /**
     * Method to set the value of field basicareaid
     *
     * @param integer $basicareaid
     * @return $this
     */
    public function setBasicareaid($basicareaid)
    {
        $this->basicareaid = $basicareaid;

        return $this;
    }

    /**
     * Method to set the value of field basicsubjectid
     *
     * @param integer $basicsubjectid
     * @return $this
     */
    public function setBasicsubjectid($basicsubjectid)
    {
        $this->basicsubjectid = $basicsubjectid;

        return $this;
    }

    /**
     * Method to set the value of field basicsection
     *
     * @param string $basicsection
     * @return $this
     */
    public function setBasicsection($basicsection)
    {
        $this->basicsection = $basicsection;

        return $this;
    }

    /**
     * Method to set the value of field basicknows
     *
     * @param string $basicknows
     * @return $this
     */
    public function setBasicknows($basicknows)
    {
        $this->basicknows = $basicknows;

        return $this;
    }

    /**
     * Method to set the value of field basicexam
     *
     * @param string $basicexam
     * @return $this
     */
    public function setBasicexam($basicexam)
    {
        $this->basicexam = $basicexam;

        return $this;
    }

    /**
     * Method to set the value of field basicapi
     *
     * @param string $basicapi
     * @return $this
     */
    public function setBasicapi($basicapi)
    {
        $this->basicapi = $basicapi;

        return $this;
    }

    /**
     * Method to set the value of field basicdemo
     *
     * @param integer $basicdemo
     * @return $this
     */
    public function setBasicdemo($basicdemo)
    {
        $this->basicdemo = $basicdemo;

        return $this;
    }

    /**
     * Method to set the value of field basicthumb
     *
     * @param string $basicthumb
     * @return $this
     */
    public function setBasicthumb($basicthumb)
    {
        $this->basicthumb = $basicthumb;

        return $this;
    }

    /**
     * Method to set the value of field basicprice
     *
     * @param string $basicprice
     * @return $this
     */
    public function setBasicprice($basicprice)
    {
        $this->basicprice = $basicprice;

        return $this;
    }

    /**
     * Method to set the value of field basicclosed
     *
     * @param integer $basicclosed
     * @return $this
     */
    public function setBasicclosed($basicclosed)
    {
        $this->basicclosed = $basicclosed;

        return $this;
    }

    /**
     * Method to set the value of field basicdescribe
     *
     * @param string $basicdescribe
     * @return $this
     */
    public function setBasicdescribe($basicdescribe)
    {
        $this->basicdescribe = $basicdescribe;

        return $this;
    }

    public function setUsednumber($countnumber)
    {
        $this->usednumber = $countnumber;
        return $this;
    }

    /**
     * Returns the value of field basicid
     *
     * @return integer
     */
    public function getBasicid()
    {
        return $this->basicid;
    }


    public function getUsednumber()
    {
        return $this->usednumber;
    }

    /**
     * Returns the value of field basic
     *
     * @return string
     */
    public function getBasic()
    {
        return $this->basic;
    }

    /**
     * Returns the value of field basicareaid
     *
     * @return integer
     */
    public function getBasicareaid()
    {
        return $this->basicareaid;
    }

    /**
     * Returns the value of field basicsubjectid
     *
     * @return integer
     */
    public function getBasicsubjectid()
    {
        return $this->basicsubjectid;
    }

    /**
     * Returns the value of field basicsection
     *
     * @return string
     */
    public function getBasicsection()
    {
        return $this->basicsection;
    }

    /**
     * Returns the value of field basicknows
     *
     * @return string
     */
    public function getBasicknows()
    {
        return $this->basicknows;
    }

    /**
     * Returns the value of field basicexam
     *
     * @return string
     */
    public function getBasicexam()
    {
        return $this->basicexam;
    }

    /**
     * Returns the value of field basicapi
     *
     * @return string
     */
    public function getBasicapi()
    {
        return $this->basicapi;
    }

    /**
     * Returns the value of field basicdemo
     *
     * @return integer
     */
    public function getBasicdemo()
    {
        return $this->basicdemo;
    }

    /**
     * Returns the value of field basicthumb
     *
     * @return string
     */
    public function getBasicthumb()
    {
        return $this->basicthumb;
    }

    /**
     * Returns the value of field basicprice
     *
     * @return string
     */
    public function getBasicprice()
    {
        return $this->basicprice;
    }

    /**
     * Returns the value of field basicclosed
     *
     * @return integer
     */
    public function getBasicclosed()
    {
        return $this->basicclosed;
    }

    /**
     * Returns the value of field basicdescribe
     *
     * @return string
     */
    public function getBasicdescribe()
    {
        return $this->basicdescribe;
    }


    /**
     * Initialize method for model.
     */
    public function initialize()
    {
       parent::initialize();
        $this->setSource("x2_basic");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_basic';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Basic[]|X2Basic|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return X2Basic|\Phalcon\Mvc\Model\ResultInterface
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
            'basicid' => 'basicid',
            'basic' => 'basic',
            'basicareaid' => 'basicareaid',
            'basicsubjectid' => 'basicsubjectid',
            'basicsection' => 'basicsection',
            'basicknows' => 'basicknows',
            'basicexam' => 'basicexam',
            'basicapi' => 'basicapi',
            'basicdemo' => 'basicdemo',
            'basicthumb' => 'basicthumb',
            'basicprice' => 'basicprice',
            'basicclosed' => 'basicclosed',
            'basicdescribe' => 'basicdescribe',
            'usednumber' => 'usednumber'
        ];
    }

}

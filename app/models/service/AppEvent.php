<?php
namespace app\models\service;


use Phalcon\Validation;
class AppEvent extends BaseModel
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
    protected $event_name;

    /**
     *
     * @var string
     */
    protected $event_code;

    /**
     *
     * @var integer
     */
    protected $event_level;

    /**
     *
     * @var integer
     */
    protected $if_show;

    /**
     *
     * @var integer
     */
    protected $parent_id;

    /**
     *
     * @var integer
     */
    protected $event_order;

    /**
     *
     * @var integer
     */
    protected $template_id;

    /**
     *
     * @var integer
     */
    protected $event_status;

    /**
     *
     * @var integer
     */
    protected $is_delete;

    /**
     *
     * @var string
     */
    protected $event_text;

    /**
     *
     * @var integer
     */
    protected $create_time;

    /**
     *
     * @var integer
     */
    protected $update_time;

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
     * Method to set the value of field event_name
     *
     * @param string $event_name
     * @return $this
     */
    public function setEventName($event_name)
    {
        $this->event_name = $event_name;

        return $this;
    }

    /**
     * Method to set the value of field event_code
     *
     * @param string $event_code
     * @return $this
     */
    public function setEventCode($event_code)
    {
        $this->event_code = $event_code;

        return $this;
    }

    /**
     * Method to set the value of field event_level
     *
     * @param integer $event_level
     * @return $this
     */
    public function setEventLevel($event_level)
    {
        $this->event_level = $event_level;

        return $this;
    }

    /**
     * Method to set the value of field if_show
     *
     * @param integer $if_show
     * @return $this
     */
    public function setIfShow($if_show)
    {
        $this->if_show = $if_show;

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
     * Method to set the value of field event_order
     *
     * @param integer $event_order
     * @return $this
     */
    public function setEventOrder($event_order)
    {
        $this->event_order = $event_order;

        return $this;
    }

    /**
     * Method to set the value of field template_id
     *
     * @param integer $template_id
     * @return $this
     */
    public function setTemplateId($template_id)
    {
        $this->template_id = $template_id;

        return $this;
    }

    /**
     * Method to set the value of field event_status
     *
     * @param integer $event_status
     * @return $this
     */
    public function setEventStatus($event_status)
    {
        $this->event_status = $event_status;

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
     * Method to set the value of field event_text
     *
     * @param string $event_text
     * @return $this
     */
    public function setEventText($event_text)
    {
        $this->event_text = $event_text;

        return $this;
    }

    /**
     * Method to set the value of field create_time
     *
     * @param integer $create_time
     * @return $this
     */
    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;

        return $this;
    }

    /**
     * Method to set the value of field update_time
     *
     * @param integer $update_time
     * @return $this
     */
    public function setUpdateTime($update_time)
    {
        $this->update_time = $update_time;

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
     * Returns the value of field event_name
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->event_name;
    }

    /**
     * Returns the value of field event_code
     *
     * @return string
     */
    public function getEventCode()
    {
        return $this->event_code;
    }

    /**
     * Returns the value of field event_level
     *
     * @return integer
     */
    public function getEventLevel()
    {
        return $this->event_level;
    }

    /**
     * Returns the value of field if_show
     *
     * @return integer
     */
    public function getIfShow()
    {
        return $this->if_show;
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
     * Returns the value of field event_order
     *
     * @return integer
     */
    public function getEventOrder()
    {
        return $this->event_order;
    }

    /**
     * Returns the value of field template_id
     *
     * @return integer
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * Returns the value of field event_status
     *
     * @return integer
     */
    public function getEventStatus()
    {
        return $this->event_status;
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
     * Returns the value of field event_text
     *
     * @return string
     */
    public function getEventText()
    {
        return $this->event_text;
    }

    /**
     * Returns the value of field create_time
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Returns the value of field update_time
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSchema("dewin_service");
        $this->setSource("dw_app_event");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_app_event';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return AppEvent[]|AppEvent|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AppEvent|\Phalcon\Mvc\Model\ResultInterface
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
            'event_name' => 'event_name',
            'event_code' => 'event_code',
            'event_level' => 'event_level',
            'if_show' => 'if_show',
            'parent_id' => 'parent_id',
            'event_order' => 'event_order',
            'template_id' => 'template_id',
            'event_status' => 'event_status',
            'is_delete' => 'is_delete',
            'event_text' => 'event_text',
            'create_time' => 'create_time',
            'update_time' => 'update_time'
        ];
    }

    public function beforeCreate()
    {
        $this->setCreateTime(time());
    }
    public function beforeUpdate()
    {
        $this->setUpdateTime(time());
    }

}

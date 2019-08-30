<?php
namespace app\models\service;


use Phalcon\Validation;

class MessageTemplate extends BaseModel
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
    protected $template_sn;

    /**
     *
     * @var string
     */
    protected $template_name;

    /**
     *
     * @var integer
     */
    protected $template_type;

    /**
     *
     * @var integer
     */
    protected $notice_type;

    /**
     *
     * @var string
     */
    protected $notice_url;

    /**
     *
     * @var string
     */
    protected $notice_andriod;

    /**
     *
     * @var string
     */
    protected $notice_ios;

    /**
     *
     * @var integer
     */
    protected $template_status;

    /**
     *
     * @var string
     */
    protected $template_pic;

    /**
     *
     * @var string
     */
    protected $template_text;

    /**
     *
     * @var integer
     */
    protected $template_need_button;

    /**
     *
     * @var string
     */
    protected $template_button;

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
    protected $is_delete;

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
     * Method to set the value of field template_sn
     *
     * @param string $template_sn
     * @return $this
     */
    public function setTemplateSn($template_sn)
    {
        $this->template_sn = $template_sn;

        return $this;
    }

    /**
     * Method to set the value of field template_name
     *
     * @param string $template_name
     * @return $this
     */
    public function setTemplateName($template_name)
    {
        $this->template_name = $template_name;

        return $this;
    }

    /**
     * Method to set the value of field template_type
     *
     * @param integer $template_type
     * @return $this
     */
    public function setTemplateType($template_type)
    {
        $this->template_type = $template_type;

        return $this;
    }

    /**
     * Method to set the value of field notice_type
     *
     * @param integer $notice_type
     * @return $this
     */
    public function setNoticeType($notice_type)
    {
        $this->notice_type = $notice_type;

        return $this;
    }

    /**
     * Method to set the value of field notice_url
     *
     * @param string $notice_url
     * @return $this
     */
    public function setNoticeUrl($notice_url)
    {
        $this->notice_url = $notice_url;

        return $this;
    }

    /**
     * Method to set the value of field notice_andriod
     *
     * @param string $notice_andriod
     * @return $this
     */
    public function setNoticeAndriod($notice_andriod)
    {
        $this->notice_andriod = $notice_andriod;

        return $this;
    }

    /**
     * Method to set the value of field notice_ios
     *
     * @param string $notice_ios
     * @return $this
     */
    public function setNoticeIos($notice_ios)
    {
        $this->notice_ios = $notice_ios;

        return $this;
    }

    /**
     * Method to set the value of field template_status
     *
     * @param integer $template_status
     * @return $this
     */
    public function setTemplateStatus($template_status)
    {
        $this->template_status = $template_status;

        return $this;
    }

    /**
     * Method to set the value of field template_pic
     *
     * @param string $template_pic
     * @return $this
     */
    public function setTemplatePic($template_pic)
    {
        $this->template_pic = $template_pic;

        return $this;
    }

    /**
     * Method to set the value of field template_text
     *
     * @param string $template_text
     * @return $this
     */
    public function setTemplateText($template_text)
    {
        $this->template_text = $template_text;

        return $this;
    }

    /**
     * Method to set the value of field template_need_button
     *
     * @param integer $template_need_button
     * @return $this
     */
    public function setTemplateNeedButton($template_need_button)
    {
        $this->template_need_button = $template_need_button;

        return $this;
    }

    /**
     * Method to set the value of field template_button
     *
     * @param string $template_button
     * @return $this
     */
    public function setTemplateButton($template_button)
    {
        $this->template_button = $template_button;

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
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field template_sn
     *
     * @return string
     */
    public function getTemplateSn()
    {
        return $this->template_sn;
    }

    /**
     * Returns the value of field template_name
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Returns the value of field template_type
     *
     * @return integer
     */
    public function getTemplateType()
    {
        return $this->template_type;
    }

    /**
     * Returns the value of field notice_type
     *
     * @return integer
     */
    public function getNoticeType()
    {
        return $this->notice_type;
    }

    /**
     * Returns the value of field notice_url
     *
     * @return string
     */
    public function getNoticeUrl()
    {
        return $this->notice_url;
    }

    /**
     * Returns the value of field notice_andriod
     *
     * @return string
     */
    public function getNoticeAndriod()
    {
        return $this->notice_andriod;
    }

    /**
     * Returns the value of field notice_ios
     *
     * @return string
     */
    public function getNoticeIos()
    {
        return $this->notice_ios;
    }

    /**
     * Returns the value of field template_status
     *
     * @return integer
     */
    public function getTemplateStatus()
    {
        return $this->template_status;
    }

    /**
     * Returns the value of field template_pic
     *
     * @return string
     */
    public function getTemplatePic()
    {
        return $this->template_pic;
    }

    /**
     * Returns the value of field template_text
     *
     * @return string
     */
    public function getTemplateText()
    {
        return $this->template_text;
    }

    /**
     * Returns the value of field template_need_button
     *
     * @return integer
     */
    public function getTemplateNeedButton()
    {
        return $this->template_need_button;
    }

    /**
     * Returns the value of field template_button
     *
     * @return string
     */
    public function getTemplateButton()
    {
        return $this->template_button;
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
     * Returns the value of field is_delete
     *
     * @return integer
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSchema("dewin_service");
        $this->setSource("dw_message_template");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_message_template';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MessageTemplate[]|MessageTemplate|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MessageTemplate|\Phalcon\Mvc\Model\ResultInterface
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
            'template_sn' => 'template_sn',
            'template_name' => 'template_name',
            'template_type' => 'template_type',
            'notice_type' => 'notice_type',
            'notice_url' => 'notice_url',
            'notice_andriod' => 'notice_andriod',
            'notice_ios' => 'notice_ios',
            'template_status' => 'template_status',
            'template_pic' => 'template_pic',
            'template_text' => 'template_text',
            'template_need_button' => 'template_need_button',
            'template_button' => 'template_button',
            'create_at' => 'create_at',
            'update_at' => 'update_at',
            'is_delete' => 'is_delete'
        ];
    }

}

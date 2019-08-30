<?php
namespace app\models\service;


use Phalcon\Validation;

class MulticodeTask extends BaseModel
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
    protected $task_code;

    /**
     *
     * @var string
     */
    protected $order_num;

    /**
     *
     * @var integer
     */
    protected $product_id;

    /**
     *
     * @var integer
     */
    protected $product_sku_relation_id;

    /**
     *
     * @var string
     */
    protected $product_info;

    /**
     *
     * @var integer
     */
    protected $task_type;

    /**
     *
     * @var integer
     */
    protected $task_num;

    /**
     *
     * @var integer
     */
    protected $task_completed_num;

    /**
     *
     * @var integer
     */
    protected $task_status;

    /**
     *
     * @var integer
     */
    protected $create_user_id;

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
     * Method to set the value of field task_code
     *
     * @param string $task_code
     * @return $this
     */
    public function setTaskCode($task_code)
    {
        $this->task_code = $task_code;

        return $this;
    }

    /**
     * Method to set the value of field order_num
     *
     * @param string $order_num
     * @return $this
     */
    public function setOrderNum($order_num)
    {
        $this->order_num = $order_num;

        return $this;
    }

    /**
     * Method to set the value of field product_id
     *
     * @param integer $product_id
     * @return $this
     */
    public function setProductId($product_id)
    {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * Method to set the value of field product_sku_relation_id
     *
     * @param integer $product_sku_relation_id
     * @return $this
     */
    public function setProductSkuRelationId($product_sku_relation_id)
    {
        $this->product_sku_relation_id = $product_sku_relation_id;

        return $this;
    }

    /**
     * Method to set the value of field product_info
     *
     * @param string $product_info
     * @return $this
     */
    public function setProductInfo($product_info)
    {
        $this->product_info = $product_info;

        return $this;
    }

    /**
     * Method to set the value of field task_type
     *
     * @param integer $task_type
     * @return $this
     */
    public function setTaskType($task_type)
    {
        $this->task_type = $task_type;

        return $this;
    }

    /**
     * Method to set the value of field task_num
     *
     * @param integer $task_num
     * @return $this
     */
    public function setTaskNum($task_num)
    {
        $this->task_num = $task_num;

        return $this;
    }

    /**
     * Method to set the value of field task_completed_num
     *
     * @param integer $task_completed_num
     * @return $this
     */
    public function setTaskCompletedNum($task_completed_num)
    {
        $this->task_completed_num = $task_completed_num;

        return $this;
    }

    /**
     * Method to set the value of field task_status
     *
     * @param integer $task_status
     * @return $this
     */
    public function setTaskStatus($task_status)
    {
        $this->task_status = $task_status;

        return $this;
    }

    /**
     * Method to set the value of field create_user_id
     *
     * @param integer $create_user_id
     * @return $this
     */
    public function setCreateUserId($create_user_id)
    {
        $this->create_user_id = $create_user_id;

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
     * Returns the value of field task_code
     *
     * @return string
     */
    public function getTaskCode()
    {
        return $this->task_code;
    }

    /**
     * Returns the value of field order_num
     *
     * @return string
     */
    public function getOrderNum()
    {
        return $this->order_num;
    }

    /**
     * Returns the value of field product_id
     *
     * @return integer
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * Returns the value of field product_sku_relation_id
     *
     * @return integer
     */
    public function getProductSkuRelationId()
    {
        return $this->product_sku_relation_id;
    }

    /**
     * Returns the value of field product_info
     *
     * @return string
     */
    public function getProductInfo()
    {
        return $this->product_info;
    }

    /**
     * Returns the value of field task_type
     *
     * @return integer
     */
    public function getTaskType()
    {
        return $this->task_type;
    }

    /**
     * Returns the value of field task_num
     *
     * @return integer
     */
    public function getTaskNum()
    {
        return $this->task_num;
    }

    /**
     * Returns the value of field task_completed_num
     *
     * @return integer
     */
    public function getTaskCompletedNum()
    {
        return $this->task_completed_num;
    }

    /**
     * Returns the value of field task_status
     *
     * @return integer
     */
    public function getTaskStatus()
    {
        return $this->task_status;
    }

    /**
     * Returns the value of field create_user_id
     *
     * @return integer
     */
    public function getCreateUserId()
    {
        return $this->create_user_id;
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
        $this->setSchema("dewin_service");
        $this->setSource("dw_multicode_task");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_multicode_task';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MulticodeTask[]|MulticodeTask|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MulticodeTask|\Phalcon\Mvc\Model\ResultInterface
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
            'task_code' => 'task_code',
            'order_num' => 'order_num',
            'product_id' => 'product_id',
            'product_sku_relation_id' => 'product_sku_relation_id',
            'product_info' => 'product_info',
            'task_type' => 'task_type',
            'task_num' => 'task_num',
            'task_completed_num' => 'task_completed_num',
            'task_status' => 'task_status',
            'create_user_id' => 'create_user_id',
            'create_at' => 'create_at',
            'update_at' => 'update_at'
        ];
    }

    /**
     * 数据验证
     * @return bool
     */
    public function validation() {

        $validator = new Validation();

        $validator->add('task_code',new Validation\Validator\PresenceOf([
            "message" => '任务代码必填!',
            'cancelOnFail' => true]));
        $validator->add('order_num',new Validation\Validator\PresenceOf([
            "message" => '来源订单必填!',
            'cancelOnFail' => true]));
        $validator->add('product_id',new Validation\Validator\PresenceOf([
            "message" => '商品ID必填!',
            'cancelOnFail' => true]));
        $validator->add('product_sku_relation_id',new Validation\Validator\PresenceOf([
            "message" => '商品关系ID必填!',
            'cancelOnFail' => true]));
        $validator->add('product_info',new Validation\Validator\PresenceOf([
            "message" => '商品信息必填!',
            'cancelOnFail' => true]));
        $validator->add('task_type',new Validation\Validator\PresenceOf([
            "message" => '任务类型必填!',
            'cancelOnFail' => true]));
        $validator->add('task_num',new Validation\Validator\PresenceOf([
            "message" => '任务数量必填!',
            'cancelOnFail' => true]));


        return $this->validate($validator);
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

<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/1
 * Time: 10:28
 */
namespace app\models\service;


class VehicleLockQueue extends BaseModel
{

    const DEFAULT_STATUS = 1; //插入的默认状态
    const CHECK_STATUS = 2; //待审核
    const CHECKED_STATUS = 3; //审核之后待处理
    const FAULT_STATUS = 4;//锁车失败

    const UNBIND_STORE_TYPE = 1; //未绑定门店
    const UNBIND_RIDER_TYPE = 2; //绑定门店未绑定骑手
    const ORDER_EXPIRE_TYPE = 3; //订单逾期未还车

    static $LOCK_TYPR_LIST = [
        self::UNBIND_STORE_TYPE => "未绑定门店",
        self::UNBIND_RIDER_TYPE => "未绑定骑手",
        self::ORDER_EXPIRE_TYPE => "订单逾期未还车",
    ];
    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $vehicle_id;

    /**
     *
     * @var string
     */
    public $batch_num;

    /**
     *
     * @var integer
     */
    public $lock_type;

    /**
     *
     * @var string
     */
    public $lock_comment;

    /**
     *
     * @var integer
     */
    public $lock_status;

    /**
     *
     * @var integer
     */
    public $create_time;

    /**
     *
     * @var integer
     */
    public $update_time;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_vehicle_lock_queue");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_vehicle_lock_queue';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return VehicleLockQueue[]|VehicleLockQueue|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }
    
    public function beforeCreate()
    {
        $this->create_time = time();
        $this->update_time = 0;
    }
    public function beforeUpdate()
    {
        $this->update_time = time();
    }
    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return VehicleLockQueue|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     *删除锁车队列
     */
    public function del($vehicle_id)
    {
        $conditions ="vehicle_id = :vehicle_id:";
        $parameters = ['vehicle_id' => $vehicle_id,];
        $res = parent::findFirst([$conditions, 'bind' => $parameters]);
        if ($res) {
            if ($res->delete() === false) {
                return false;
            }
            $vehicle = Vehicle::findFirst($vehicle_id);
            $vehicle->lock_queue = Vehicle::NOT_IN_QUEUE;
            $result = $vehicle->save();
            if ($result === false) {
                return false;
            }
        }
        return true;
    }

}
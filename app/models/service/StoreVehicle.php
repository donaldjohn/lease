<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/1
 * Time: 17:39
 */
namespace app\models\service;

class StoreVehicle extends BaseModel
{

    const UN_RENT = 1;//未出租
    const RENT = 2;//已出租
    const UN_RETURN = 3;//未还车
    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $store_id;

    /**
     *
     * @var integer
     */
    public $vehicle_id;

    /**
     *
     * @var integer
     */
    public $rent_status;

    /**
     *
     * @var integer
     */
    public $driver_id;

    /**
     *
     * @var integer
     */
    public $bind_time;

    /**
     *
     * @var integer
     */
    public $update_time;

    /**
     *
     * @var integer
     */
    public $ready_rent_time;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_store_vehicle");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_store_vehicle';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwStoreVehicle[]|DwStoreVehicle|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwStoreVehicle|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}

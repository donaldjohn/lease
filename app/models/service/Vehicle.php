<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/1
 * Time: 10:32
 */
namespace app\models\service;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Alnum;

class Vehicle extends BaseModel
{

    //锁车状态
    const IS_NOT_LOCK = 0;
    const IS_LOCK = 1;
    //绑定状态
    const NOT_BIND = 1;
    const BIND = 2;
    //是否在锁车队列
    const NOT_IN_QUEUE = 1;
    const IN_QUEUE = 2;
    //车辆使用属性use_attribute
    const UNSET_ATTRIBUTE = 1;//未设置
    const STORE_ATTRIBUTE = 2;//门店
    const SITE_ATTRIBUTE = 3;//站点
    const AI_ATTRIBUTE = 4;//智能化服务
    // 是否参保
    const NOT_HAS_SECURE = 1;
    const HAS_SECURE = 2;
    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $udid;

    /**
     *
     * @var string
     */
    public $bianhao;

    /**
     *
     * @var string
     */
    public $vin;

    /**
     *
     * @var string
     */
    public $plate_num;

    /**
     *
     * @var integer
     */
    public $product_id;

    /**
     *
     * @var integer
     */
    public $product_sku_relation_id;

    /**
     *
     * @var integer
     */
    public $status;

    /**
     *
     * @var double
     */
    public $total_mile;

    /**
     *
     * @var double
     */
    public $expect_mile;

    /**
     *
     * @var double
     */
    public $speed;

    /**
     *
     * @var string
     */
    public $lng;

    /**
     *
     * @var string
     */
    public $lat;

    /**
     *
     * @var integer
     */
    public $fault;

    /**
     *
     * @var double
     */
    public $vcur;

    /**
     *
     * @var integer
     */
    public $battery;

    /**
     *
     * @var integer
     */
    public $chip_power;

    /**
     *
     * @var integer
     */
    public $duration;

    /**
     *
     * @var integer
     */
    public $communicate_time;

    /**
     *
     * @var integer
     */
    public $is_lock;

    /**
     *
     * @var integer
     */
    public $charge_number;

    /**
     *
     * @var integer
     */
    public $is_online;

    /**
     *
     * @var integer
     */
    public $charge;

    /**
     *
     * @var double
     */
    public $c_total_mileage;

    /**
     *
     * @var integer
     */
    public $before_duration;

    /**
     *
     * @var integer
     */
    public $before_mileage;

    /**
     *
     * @var integer
     */
    public $signal_;

    /**
     *
     * @var integer
     */
    public $contact;

    /**
     *
     * @var integer
     */
    public $max_voltage;

    /**
     *
     * @var integer
     */
    public $has_bind;

    /**
     *
     * @var integer
     */
    public $driver_bind;

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
     *
     * @var integer
     */
    public $driver_id;

    /**
     *
     * @var string
     */
    public $record_num;

    /**
     *
     * @var integer
     */
    public $has_secure;

    /**
     *
     * @var integer
     */
    public $pull_time;

    /**
     *
     * @var integer
     */
    public $last_locate_time;

    /**
     *
     * @var integer
     */
    public $mileage_time;

    /**
     *
     * @var integer
     */
    public $turnon;

    /**
     *
     * @var integer
     */
    public $gsm;

    /**
     *
     * @var integer
     */
    public $gps_count;

    /**
     *
     * @var integer
     */
    public $use_attribute;

    /**
     *
     * @var integer
     */
    public $push_target;

    /**
     *
     * @var integer
     */
    public $lock_queue;

    /**
     *
     * @var integer
     */
    public $except_lock_time;

    /**
     *
     * @var integer
     */
    public $last_lock_time;

    /**
     *
     * @var string
     */
    public $vin_img_url;

    /**
     *
     * @var string
     */
    public $plate_num_img_url;

    /**
     *
     * @var integer
     */
    public $device_model_id;

    /**
     *
     * @var integer
     */
    public $data_source;

    /**
     *
     * @var integer
     */
    public $use_status;

    /**
     *
     * @var integer
     */
    public $vehicle_model_id;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_vehicle");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_vehicle';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Vehicle[]|Vehicle|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Vehicle|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * @param \Phalcon\ValidationInterface $validator
     * @return bool|void
     * 验证
     */
    public function validation()
    {
        $validator = new Validation();
        /**
         * 车架号只能出现字母和数字
         */
        $validator->add('vin',new Validation\Validator\Regex([
            "pattern" => "/^[A-Za-z\d-#.\/]+$/",
            "message" => "车架号只能为数字或字母或-#./"]));
        /**
         * 得威二维码 只能为数字
         */
        $validator->add('bianhao',new Validation\Validator\Numericality((["message" => "得威编号只能为数字",])));
        $validator->add('bianhao',new Validation\Validator\StringLength([
              "max"            => 16,
              "min"            => 12,
              "messageMaximum" => "长度必须小于等于15位",
              "messageMinimum" => "长度必须大于等于12位",
          ]));

        return $this->validate($validator);
    }


    public function beforeCreate()
    {
        $this->create_time = time();
        $this->update_time = time();
        $this->driver_id = 0;
    }
    public function beforeUpdate()
    {
        $this->update_time = time();
    }


    public  function batch_insert(array $data)
    {
        if (count($data) == 0) {
            return false;
        }
        $keys = array_keys(reset($data));
        $keys = array_map(function ($key) {
            return "`{$key}`";
        }, $keys);
        $keys = implode(',', $keys);
        $sql = "INSERT INTO " . $this->getSource() . " ({$keys}) VALUES ";
        foreach ($data as $v) {
            $v = array_map(function ($value) {
                return "'{$value}'";
            }, $v);
            $values = implode(',', array_values($v));
            $sql .= " ({$values}), ";
        }
        $sql = rtrim(trim($sql), ',');
        return $sql;
    }
}

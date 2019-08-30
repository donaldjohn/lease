<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models\order;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

class ServiceContract extends BaseModel
{
    // 未生效
    const STATUS_UNPAID = 1;
    // 待生效
    const STATUS_PAID_UNBIND = 2;
    // 生效中
    const STATUS_USING = 3;
    // 退款中
    const STATUS_REFUNDING = 4;
    // 已结束
    const STATUS_FINISHED = 5;
    // 已失效
    const STATUS_INVALID = 6;

    /**
     * 骑手服务契约表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_service_contract");
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

    public static function getServiceOrderById($id)
    {
        return self::findFirst([
            'conditions' => 'id = ?1',
            'bind'       => [
                1 => $id,
            ]
        ]);
    }

}
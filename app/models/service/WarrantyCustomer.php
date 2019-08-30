<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/7/5
 * Time: 16:26
 */
namespace app\models\service;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

class WarrantyCustomer extends BaseModel
{
    /**
     * 联保客户表
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_warranty_customer");
    }

    public function beforeCreate()
    {
        $this->create_at = time();
        $this->update_at = 0;
        $this->is_delete = 0;
        $validator = new Validation();
        $validator->add(
            'user_real_name',
            new Uniqueness(['message' => '客户全称必须唯一',])
        );
        $validator->add(
            'user_simple_name',
            new Uniqueness(['message' => '客户简称必须唯一',])
        );
        $validator->add(
            'user_code',
            new Uniqueness(['message' => '客户代码必须唯一',])
        );

        return $this->validate($validator);
    }

    public function beforeUpdate()
    {
        $this->update_at = time();
    }

    /**
     * 生成32位随机秘钥
     */
    public static function secret()
    {
        $char = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = time();
        for ($i = 0; $i < 6; $i++) {
            $key .= $char[ mt_rand(0, strlen($char) - 1) ];
        }
        return md5($key);
    }
}
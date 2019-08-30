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

class VehicleSource extends BaseModel
{


    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_vehicle_source");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_vehicle_source';
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

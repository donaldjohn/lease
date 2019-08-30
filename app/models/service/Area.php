<?php

namespace app\models\service;

class Area extends BaseModel
{

    /**
     *
     * @var integer
     */
    public $area_id;

    /**
     *
     * @var string
     */
    public $area_name;

    /**
     *
     * @var integer
     */
    public $area_parent_id;

    /**
     *
     * @var integer
     */
    public $area_deep;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("dw_area");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'dw_area';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwArea[]|DwArea|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return DwArea|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * 通过区域ID获取三级区域ID（省市区）
     * @param $region_id
     * @return mixed
     */
    public static function getThreeRegion($region_id)
    {
        $data['area_id'] = $region_id;
        $city = self::findFirst($data['area_id']);
        if ($city) {
            $data['city_id'] = $city->area_parent_id;
            $province = self::findFirst($data['city_id']);
            if ($province) {
                $data['province_id'] = $province->area_parent_id;
            }
        }
        return $data;
    }
}

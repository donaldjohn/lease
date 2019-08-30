<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26 0026
 * Time: 17:56
 */
namespace app\models\phpems;
class X2Examsessionpractice extends BaseModel
{
    /**
     * 模拟考试的缓存
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setSource("x2_examsession_practice");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'x2_examsession_practice';
    }
}
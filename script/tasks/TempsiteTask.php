<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/12
 * Time: 10:56
 */
use Phalcon\Cli\Task;
use app\models\service\Area;

class TempsiteTask extends Task
{
    //迁移站点公司数据
    /**
     *
     */
    public function SyncAction()
    {
        $result = $this->getDi()->getShared('dw_users')->query("
            SELECT s.*,b.store_name as company_name from temp_store as s
            LEFT JOIN temp_store as b on s.service_id = b.account_id
            WHERE s.service_id > 0 AND s.`status` = 0 ;"
        );
        $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
        while ($robot = $result->fetch()) {
            $time = time();
            $ins_id = $this->getInsId($robot->company_name);
            $province_id = 0;
            $city_id = 0;
            if ($robot->region_id) {
                $area = new Area();
                $res = $area->getThreeRegion($robot->region_id);
                if ($res) {
                    $province_id = $res['province_id'];
                    $city_id = $res['city_id'];
                }
            }
            $resu = $this->getDi()->getShared('dw_dispatch')->query("INSERT INTO `dewin_dispatch`.`dw_region` 
            (`ins_id`,`region_level`,`region_remark`,`create_at`,`region_type`,`provice_id`,`city_id`,`area_id`,`region_name`,`region_code`,`address`)
            VALUES($ins_id, 1, '老系统导入',$time,'2', '{$province_id}',$city_id,$robot->region_id,'{$robot->store_name}','{$robot->login_name}','{$robot->address}');");
            if ($resu) {
                $this->getDi()->getShared('dw_users')->query("update temp_store set status = 1 WHERE  account_id = {$robot->account_id}");
            }
        }
    }

    /**
     * 获取InsId
     * @param $company_name
     * @return int
     */
    private function getInsId($company_name)
    {
        $ins_id = 0;
        $sql = " SELECT i.id from dw_company as c
            LEFT JOIN dw_institution as i on c.ins_id = i.parent_id
            where c.company_name = '{$company_name}' and i.type_id = 7  limit 1";
        $result = $this->getDi()->getShared('dw_users')->query(
           $sql
        );
        $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
        while ($robot = $result->fetch()) {
            $ins_id =  $robot->id;
        }
        return $ins_id;
    }


}
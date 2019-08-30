<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/12
 * Time: 10:56
 */
use Phalcon\Cli\Task;
use app\modules\auth\ExpressController;
use app\services\data\UserData;
use app\models\service\Area;
use app\models\users\Association;
use app\models\users\UserInstitution;
use app\models\users\Group;

class TempcompanyTask extends Task
{
    //迁移快递公司数据
    /**
     *
     */
    public function SyncAction()
    {
        $sql = "SELECT * from temp_store where service_id = 0 and status =0; ";
        $result = $this->getDi()->getShared('dw_users')->query($sql);
        $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
        $company = Association::findFirst(['association_name = :association_name:','bind' => [
                'association_name' =>"苏州市快递协会",
        ]]);
        if (!$company) {
            echo "未找到苏州市快递协会";exit;
        }
        $user = UserInstitution::findFirst(['ins_id = :ins_id:','bind' => [
            'ins_id' => $company->ins_id,
        ]]);
        if (!$user) {
            echo "未找到苏州市快递协会的用户";exit;
        }
//        $auth = (object)['insId' => 106, "userid" => 429];
        $auth = (object)['insId' => $company->ins_id, "userid" => $user->user_id];
        while ($robot = $result->fetch()) {
            $json = $this->getJsonData($robot);
            $json['isAdministrator'] = 2;
            $json['insId'] = $auth->insId;
            $json['parentId'] = $auth->userId;
            $json['groupId'] = 81;
            $a = new UserData();
            $company =$a->createCompany($auth,$json,\app\common\library\SystemType::EXPRESS);
            if ($company) {
                $res = $this->getDi()->getShared('dw_users')->query("update temp_store set status = 1 WHERE  account_id = {$robot->account_id}");
            } else {
                echo  $robot->account_id; exit;
            }
        };
    }

    /**
     * 获取发送java的json数据
     * @param $val
     */
    private function getJsonData($val)
    {
        $data = [];
       if ($val->region_id) {
           $area = new Area();
           $res = $area->getThreeRegion($val->region_id);
           if ($res) {
               $data['provinceId'] = $res['province_id'];
               $data['cityId'] = $res['city_id'];
               $data['areaId'] = $res['area_id'];
               $data['areaArr'] = array_values($res);
           }
       }
       $data['userName'] = $val->login_name;
       $data['userStatus'] = 1;
       $data['companyName'] = $val->store_name;
       $data['phone'] = $val->phone;
       $data['linkMan'] = $val->contact_name ? $val->contact_name : '管理员';
       $data['address'] = $val->address;
       return $data;
    }


}
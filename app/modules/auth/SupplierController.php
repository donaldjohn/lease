<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: SupplierController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\common\errors\DataException;
use app\common\library\SystemType;
use app\models\users\Association;
use app\models\users\Postoffice;
use app\modules\BaseController;

//供应商管理
class SupplierController extends BaseController
{
    public function ListAction()
    {
        $company = $this->userData->getCompanyPage($this->authed,SystemType::SUPPLIER);
        return $this->toSuccess($company['data'],$company['meta']);
    }

    public function OneAction($id)
    {
        $user = $this->userData->getUserById($id);
        if ($user['insId'] == -1)
            return $this->toError(500,'用户暂无供应商信息');
        //根据user insId 获取邮管局信息
        $company = $this->userData->getCompanyByInsId($user['insId']);
        $company['userName'] = $user['userName'];
        $company['phone'] = $user['phone'];
        $company['userStatus'] = $user['userStatus'];
        $company['groupId'] = $user['groupId'];
        $company['userId'] = $user['id'];
        $company['createAt'] = !empty($company['createAt']) ? date('Y-m-d H:i:s', $company['createAt']) : '-';
        $company['updateAt'] = !empty($company['updateAt']) ? date('Y-m-d H:i:s', $company['updateAt']) : '-';

        if ($company['provinceId'] != 0) {
            $p = $this->userData->getRegionName($company['provinceId']);
            $company['provinceName'] = $p['areaName'];
            unset($p);
        }
        if ($company['cityId'] != 0) {
            $c = $this->userData->getRegionName($company['cityId']);
            $company['cityName'] = $c['areaName'];
            unset($c);
        }
        if ($company['areaId']) {
            $a = $this->userData->getRegionName($company['areaId']);
            $company['areaName'] = $a['areaName'];
            unset($a);
        }
        if ($company['groupId']) {
            $group = $this->userGroupData->getUserGroupById($company['groupId']);
            $company['groupName'] = isset($group['groupName']) ? $group['groupName'] : '无';
        }

        return $this->toSuccess($company);
    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['isAdministrator'] = 2;
        $json['insId'] = $this->authed->insId;
        $company = $this->userData->createCompany($this->authed,$json,SystemType::SUPPLIER);
        return $this->toSuccess($company);
    }


    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $user= $this->userData->getUserById($id);

        if (!$user) return $this->toError(500,'用户不存在');

        // 参数提取
        $fields = [
            'phone' => 0,
            'groupId' => 0,
            'userStatus' => 0,
        ];
        $users = $this->getArrPars($fields, $json);
        if (false === $user){
            return;
        }
        $users['id'] = $id;
        $users['updateAt'] = time();
        //更新user表
        $this->userData->updateUser($users);
        //更新邮管局
        if (!isset($json['id'])) return $this->toError(500,'公司Id必传');
        $fields = [
            'insId' => 0,
            'companyName' => 0,
            'companyType' => 0,
            'legalPerson' => 0,
            'scale' => 0,
            'regMark' => 0,
            'bankName' => 0,
            'bankOwner' => 0,
            'bankCard' => 0,
            'scope' => ["name" => "企业经营范围","maxl" => 55],
            'linkMan' => 0,
            'linkCard' => 0,
            'linkPhone' => 0,
            'linkTel' => 0,
            'linkMail' => 0,
            'provinceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'remark' => 0,
            'postId' => 0,
            'parentId' => 0,
            'address' => 0,
            'orgCode' => 0,
        ];
        $parameter = $this->getArrPars($fields, $json);
        if (!$parameter){
            return;
        }
        $parameter['id'] = $json['id'];
        $parameter['updateAt'] = time();
        $company = $this->userData->updateCompany($parameter);

        return $this->toSuccess($company);

    }


    public function DeleteAction($id)
    {
        //获取用户数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            "code" => "10004",
            "parameter" => [
                "id" => $id
            ]],"post");
        //结果处理返回
        if ($result['statusCode'] != '200' || count($result['content']['users']) != 1) {
            return $this->toError(500, "未获取到有效用户数据");
        }



        $user['id'] = $id;
        $user['updateAt'] = time();
        $user['userStatus'] = 2;
        $user['isDelete'] = 1;
        //修改用户数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10003',
            'parameter' => $user
        ],"post");
        //错误返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'更新用户信息失败'.$result['msg']);
        }

        return $this->toSuccess('禁用成功' , '',200 );
    }


    public function List2Action(){

        $pageSize = $this->request->getQuery("pageSize",'string',20,true);
        $pageNum = $this->request->getQuery("pageNum",'string',1,true);
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
            "userType" => (int)SystemType::SUPPLIER,
        ];
        $userName = $this->request->getQuery("userName","string",null);
        $companyName = $this->request->getQuery("companyName","string",null);
        $userStatus = $this->request->getQuery("userStatus","int",null);
        $companyType = $this->request->getQuery("companyType","int",null);
        if(!empty($userName))
            $parameter['userName'] = $userName;
        if(!empty($companyName))
            $parameter['companyName'] =$companyName;
        if(!empty($userStatus))
            $parameter['userStatus'] =$userStatus;
        if(!empty($companyType))
            $parameter['companyType'] =$companyType;

        if ($this->authed->userType == 2) {
            /**
             * 邮管局
             */
            $p = Postoffice::findFirst(['ins_id = :ins_id:','bind' => ['ins_id' => $this->authed->insId]]);
            if ($p != false) {
                if ($p->getCityId() > 0) {
                    /**
                     * 市邮管局
                     * 查询市级快递协会 insId
                     */
                    $cityId = (int)$p->getCityId();
                    $a = Association::findFirst(['level = :level: and city_id = :city_id:','bind' =>['level' => 3,'city_id' => $cityId]]);
                    if ($a == false ) {
                        return $this->toSuccess(null,['pageSize' =>$pageSize,'pageNum' => $pageNum,'total'=> 0]);
                    }
                    $parameter['insId'] = $a->getInsId();
                } else if ($p->getProvinceId() > 0) {
                    /**
                     * 省邮管局
                     * 查询省级快递协会 insId
                     */
                    $provinceId = (int)$p->getProvinceId();
                    $a = Association::find(['level = :level: and province_id = :province_id:','bind' =>['level' => 3,'province_id' => $provinceId]])->toArray();
                    if ($a == false ) {
                        return $this->toSuccess(null, ['pageSize' => $pageSize, 'pageNum' => $pageNum, 'total' => 0]);
                    }
                    foreach ($a as $item) {
                        $parameter['insIds'][] = $item['ins_id'];
                    }
                }

            }
        } elseif ($this->authed->userType == 3) {
            /**
             * 快递协会
             */
            $a = Association::findFirst(['ins_id = :ins_id:','bind' =>['ins_id' => $this->authed->insId]]);
            if ($a != false) {
                if ($a->getCityId() > 0) {
                    /**
                     * 查询市级快递协会生成的快递公司
                     */
                    $parameter['insId'] = $this->authed->insId;
                } elseif ($a->getProvinceId() > 0) {
                    /**
                     * 查询当前省下的市级快递协会
                     */
                    $parameter['insIds'][] = $this->authed->insId;
                    $provinceId = (int)$a->getProvinceId();
                    $a = Association::find(['level = :level: and province_id = :province_id:','bind' =>['level' => 3,'province_id' => $provinceId]])->toArray();
                    if ($a == false ) {
                        return $this->toSuccess(null, ['pageSize' => $pageSize, 'pageNum' => $pageNum, 'total' => 0]);
                    }
                    foreach ($a as $item) {
                        $parameter['insIds'][] = $item['ins_id'];
                    }

                }

            }
        }

        //调用微服务接口获取数据
        $params = ["code" => "10069", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (!isset($result['statusCode']) && $result['statusCode'] != '200')
            throw new DataException();
        $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : 0;
        $meta['pageNum'] = $pageNum;
        $meta['pageSize'] = $pageSize;
        $result = $result['content']['companyDOS'];
        foreach ($result as $key => $item) {
            $result[$key]['createAt'] = date('Y-m-d H:i:s',$item['createAt']);
            $result[$key]['updateAt'] = date('Y-m-d H:i:s',$item['updateAt']);
        }
        //return ['data' => $result,'meta' => $meta];


        return  $this->toSuccess($result,$meta);

    }
}
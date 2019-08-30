<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: DispathingController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\common\library\SystemType;
use app\modules\BaseController;

//配送企业管理
class DispathingController extends BaseController
{
    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     */
    public function ListAction()
    {
        $company = $this->userData->getCompanyPage($this->authed,SystemType::DISPATHING);
        return $this->toSuccess($company['data'],$company['meta']);
    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     */
    public function OneAction($id)
    {
        $user = $this->userData->getUserById($id);
        if ($user['insId'] == -1)
            return $this->toError(500,'用户暂无邮管局信息');
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

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     */
    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['isAdministrator'] = 2;
        $json['insId'] = $this->authed->insId;
        $company = $this->userData->createCompany($this->authed,$json,SystemType::DISPATHING);
        return $this->toSuccess($company);
    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface|void
     * @throws \app\common\errors\DataException
     */
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
        $user = $this->getArrPars($fields, $json);
        if (false === $user){
            return;
        }
        $user['id'] = $id;
        $user['updateAt'] = time();
        //更新user表
        $this->userData->updateUser($user);
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
            'address' => 0,
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


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
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
}
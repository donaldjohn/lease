<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: StoreController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\common\library\SystemType;
use app\modules\BaseController;

//门店管理
class StoreController extends BaseController
{
    public function ListAction()
    {
        $store = $this->userData->getStoreUserPage($this->authed,SystemType::STORE);
        return $this->toSuccess($store['data'],$store['meta']);
    }

    public function OneAction($id)
    {
        $user = $this->userData->getUserById($id);
        if ($user['insId'] == -1)
            return $this->toError(500,'用户暂无门店信息');
        //根据user insId 获取邮管局信息
        $store = $this->userData->getStoreByInsId($user['insId']);
        $store['userName'] = $user['userName'];
        $store['phone'] = $user['phone'];
        $store['userStatus'] = $user['userStatus'];
        $store['groupId'] = $user['groupId'];
        $store['userId'] = $user['id'];
        $store['createAt'] = !empty($store['createAt']) ? date('Y-m-d H:i:s', $store['createAt']) : '-';
        $store['updateAt'] = !empty($store['updateAt']) ? date('Y-m-d H:i:s', $store['updateAt']) : '-';
        if ($store['groupId']) {
            $group = $this->userGroupData->getUserGroupById($store['groupId']);
            $store['groupName'] = isset($group['groupName']) ? $group['groupName'] : '无' ;
            unset($group);
        }

        if ($store['provinceId'] != 0) {
            $p = $this->userData->getRegionName($store['provinceId']);
            $store['provinceName'] = $p['areaName'];
            unset($p);
        }
        if ($store['cityId'] != 0) {
            $c = $this->userData->getRegionName($store['cityId']);
            $store['cityName'] = $c['areaName'];
            unset($c);
        }

        if ($store['areaId'] != 0) {
            $a = $this->userData->getRegionName($store['areaId']);
            $store['areaName'] = $a['areaName'];
            unset($a);
        }

        return $this->toSuccess($store);
    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['isAdministrator'] = 2;
        $json['parentId'] = $this->authed->userId;
        $json['scope'] = '门店';
        $store = $this->userData->createStore($this->authed,$json,SystemType::STORE);
        return $this->toSuccess($store);
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
        $user_info = $this->getArrPars($fields, $json);
        if (false === $user){
            return;
        }
        $user_info['id'] = $id;
        $user_info['updateAt'] = time();
        //更新user表
        $this->userData->updateUser($user_info);
        //更新邮管局
        if (!isset($json['id'])) return $this->toError(500,'公司Id必传');
        $fields = [
            'insId' => 0,
            'storeName' => 0,
            'legalPerson' => 0,
            'lat' => 0,
            'lng' => 0,
            'scope' => ["name" => "企业经营范围","maxl" => 255],
            'regMark' => 0,
            'orgCode' => 0,
            'bankName' => 0,
            'bankOwner' => 0,
            'bankCard' => 0,
            'linkMan' => 0,
            'linkCard' => 0,
            'linkPhone' => 0,
            'linkTel' => 0,
            'linkMail' => 0,
            'provinceId' => 0,
            'cityId' => 0,
            'areaId' => 0,
            'address' => 0,
            'imgUrl' => 0,
            'startAt' => 0,
            'endAt' => 0,
        ];
        $parameter = $this->getArrPars($fields, $json);
        if (!$parameter){
            return;
        }
        $parameter['id'] = $json['id'];
        $parameter['updateAt'] = time();
        // TODO: storeType
        if (isset($json['storeType']))
            $parameter['storeType'] = $json['storeType'];
        $company = $this->userData->updateStore($parameter);
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
}
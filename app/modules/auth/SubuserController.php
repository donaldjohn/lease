<?php
namespace app\modules\auth;

use app\common\library\SystemType;
use app\modules\BaseController;


/**
 * Class UserController
 * @package app\modules\auth
 * 用户 增删改查
 */
class SubuserController extends BaseController
{
    // 用户列表 搜索
    public function ListAction()
    {
        $this->logger->info("用户查询");

        $isAdministrator = 1;
        $parentId = $this->authed->userId;
        $type = $this->authed->userType;
        $company = $this->userData->getCompanyByInsId($this->authed->insId);
        $users = $this->userData->getInsideUserPage($type,$isAdministrator,$parentId,$company['companyName']);

        return $this->toSuccess($users['data'],$users['meta']);
    }

    // 获取单条用户信息
    public function oneAction($id)
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

        $user = $result['content']['users'][0];

        if ($this->system != 0 && $user['groupId'] != $this->authed->groupId) {
            return $this->toError(500, "非法请求!");
        }

        //$res = ["id" => $user['roleId']];
        $res = ['code' => 10007,'parameter' => ["id" => $user['roleId']]];
        $result = $this->curl->httpRequest($this->Zuul->user,$res,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if (count($result['content']['roles']) != 1) {
                $user['roleName'] = "主系统";
            } else {
                $result = $result['content']['roles'][0];
                $user['roleName'] = $result['roleName'];
            }
        }
        $userGroup = $this->userGroupData->getUserGroupById($user['groupId']);
        $user['groupName'] = isset($userGroup['groupName']) ? $userGroup['groupName'] : '无';



//        echo json_encode($regionres);return;
        $backlist = [
            'id' => '',
            'userName' => '',
            'realName' => '',
            'phone' => '',
            'groupId' => '',
            'roleId' => '',
            'parentId' => 0,
            'isAdministrator' => '',
            'userStatus' => '',
            'roleName' => '',
            'regionId' =>  '',
            'isLeader' =>  '',
            'regionLevel' =>  '',
            'groupName' => '',
            'regionName' =>  '',
            'idCard' => '',
            'sex' => '',
            'email' => '',
            'userRemark' => '',
            'createAt' => [
                'fun' => 'time',
            ],
            'updateAt' => [
                'fun' => 'time',
            ]
        ];
        $list = $this->backData($backlist, $user);
        return $this->toSuccess($list);

    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\MicroException
     *
     * 创建用户组用户
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if ($this->system == 0 && $this->authed->userType == 1) {

            if(isset($request['roleId']) && $request['roleId'] != 0) {
                $request['isAdministrator'] = 1;
            } else {
                $request['isAdministrator'] = 2;
            }
            $request['parentId'] = 0;
            $request['systemId'] = 0;
        } else {
            $request['isAdministrator'] = 1;
            $request['parentId'] = $this->authed->userId;
            $request['groupId'] = $this->authed->groupId;
            $request['systemId'] = $this->system;
            $request['insId'] = $this->authed->insId;
        }
        $user = $this->userData->createInsideUser($request,$this->authed->userType);
        if (isset($user['code']) && $user['code'] == false)
            return $this->toError(500,$user['msg']);

        return $this->toSuccess($user);
    }




    //修改
    public function UpdateAction($id)
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

        $user = $result['content']['users'][0];

        if ($this->system != 0 && $user['groupId'] != $this->authed->groupId) {
            return $this->toError(500, "非法请求!");
        }

        $request = $this->request->getJsonRawBody(true);

//        if ($this->system != 0 ) {
//            $request['groupId'] = $this->authed->groupId;
//        }
        // 重置密码
        if (isset($request['resetPWD']) && $request['resetPWD']==1){
            $user['password'] = $this->security->hash(123456);
        }else{
            // 参数提取
            $fields = [
                'userName' => 0,
                'roleId' => 0,
                'userStatus' => 0,
                'realName' => 0,
                'phone' => 0,
//                'regionLevel' => 0,
                'oldRegionId' => 0,
                'regionId' => 0,
                'parentId' => 0,
                'groupId' => 0,
                'isAdministrator' => 0,
                'idCard' => 0,
                'sex' => 0,
                'email' => 0,
                'userRemark' => 0,
            ];
            $user = $this->getArrPars($fields, $request,true);
            if (false === $user){
                return;
            }
        }
        $user['id'] = $id;
        $user['updateAt'] = time();
        //修改用户数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10003',
            'parameter' => $user
        ],"post");
        //错误返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'更新用户信息失败'.$result['msg']);
        }


        return $this->toSuccess('更新成功' , '',200 );
    }

    //删除
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

        if (!isset($result['content']['users'][0]['parentId']) || $result['content']['users'][0]['parentId'] != $this->authed->userId) {
            return $this->toError(500,'非法操作!');
        }

        $user['id'] = $id;

        //删除用户数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10002',
            'parameter' => $user
        ],"post");
        //错误返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'删除用户信息失败'.$result['msg']);
        }

        return $this->toSuccess('删除成功' , '',200 );

    }



    public function RestpwdAction($id)
    {
        $user = $this->userData->getUserById($id);
        if (empty($user['id']))
            return $this->toError(500,'用户不存在');
        $parameter['id'] = $id;
        $parameter['password'] = $this->security->hash(123456);
        $user = $this->userData->updateUser($parameter);
        if($user)
            return $this->toSuccess(true);
        return $this->toError(500,'重置密码失败!');

    }


    public function ChangepwdAction()
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['newPassword']))
            return $this->toError(500,'密码必填!');
        if (!isset($json['oldPassword']))
            return $this->toError(500,'密码必填!');
        if ($this->authed->userId < 0) {
            return $this->toError(500,'非法操作!');
        }

        /**
         * rsa解密
         */
        $json['oldPassword'] = $this->RSADec($json['oldPassword']);
        $json['newPassword'] = $this->RSADec($json['newPassword']);

        $user = $this->userData->getUserById($this->authed->userId);
        if (!$this->security->checkHash($json['oldPassword'], $user['password'])) {
            return $this->toError(500,'旧密码不正确!');
        }
        if (empty($user['id']))
            return $this->toError(500,'用户不存在');
        $parameter['id'] = $user['id'];
        $parameter['password'] = $this->security->hash($json['newPassword']);
        $user = $this->userData->updateUser($parameter);
        if($user)
            return $this->toSuccess(true);
        return $this->toError(500,'更新密码失败!');

    }






}

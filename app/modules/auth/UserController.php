<?php
namespace app\modules\auth;

use app\common\library\SystemType;
use app\models\dispatch\Region;
use app\models\dispatch\RegionUser;
use app\modules\BaseController;
use app\services\data\RegionData;


/**
 * Class UserController
 * @package app\modules\auth
 * 用户 增删改查
 */
class UserController extends BaseController
{
    // 用户列表 搜索
    public function ListAction()
    {
        $this->logger->info("用户查询");
        if ($this->system == 0 && $this->authed->userType == 1) {
            $isAdministrator = 1;
            $parentId = 0;
            $users = $this->userData->getInsideUserPage(SystemType::DEWIN_IN,$isAdministrator,$parentId);
        } else {
            $isAdministrator = 1;
            $parentId = $this->authed->userId;
            $type = $this->authed->userType;
            $users = $this->userData->getInsideUserPage($type,$isAdministrator,$parentId);
        }
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

        if ($user['userType'] != 1) {
            $region =  $this->modelsManager->createBuilder()
                ->addfrom('app\models\dispatch\Region','r')
                // 查询关联用户
                ->join('app\models\dispatch\RegionUser', 'ru.region_id = r.id','ru')
                ->where('ru.user_id = :user_id:', ['user_id'=>$user['id']])
                ->columns('r.id, r.region_level, r.parent_id, r.region_code, r.region_name, r.region_type, ru.is_leader')
                ->getQuery()
                ->getSingleResult();
            // 如果是站点
            if ($region && 2==$region->region_type){
                $user['siteId'] = $region->id;
                $user['siteName'] = $region->region_name;
                $user['isLeader'] = $region->is_leader;
                // 查询区域信息
                if ($region->parent_id > 0){
                    $parentRegion = Region::findFirst([
                        'id = :id:',
                        'bind' => [
                            'id' => $region->parent_id,
                        ],
                    ]);
                }
                if (isset($parentRegion) && $parentRegion){
                    $user['regionId'] = $parentRegion->id;
                    $user['regionLevel'] = $parentRegion->region_level;
                    $user['regionName'] = $parentRegion->region_name;
                }
            }else if($region){
                // 如果是区域
                $user['regionId'] = $region->id;
                $user['regionLevel'] = $region->region_level;
                $user['regionName'] = $region->region_name;
            }
        }
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
            'siteId' =>  '',
            'siteName' =>  '',
            'idCard' => '',
            'sex' => '',
            'userRemark' => '',
            'createAt' => [
                'fun' => 'time',
            ],
            'updateAt' => [
                'fun' => 'time',
            ],
            'email' => '',
        ];
        $list = $this->backData($backlist, $user);
        return $this->toSuccess($list);

    }


    /**
     * 创建用户组用户
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if ($this->authed->userType != 1) {
            $request['groupId'] = $this->authed->groupId;
            $request['parentId'] = $this->authed->userId;
        } else {
            $request['parentId'] = 0;
        }
        $request['systemId'] = 0;
        $request['insId'] = $this->authed->insId;
        $request['isAdministrator'] = 1;
        $request['insId'] = $this->authed->insId;
        $user = $this->userData->createInsideUser($request,$this->authed->userType);
        if (isset($user['code']) && $user['code'] == false)
            return $this->toError(500,$user['msg']);
        // 绑定区域/站点
        if (isset($request['siteId']) && !empty($request['siteId'])){
            $regionId = $request['siteId'];
        }elseif (isset($request['regionId']) && !empty($request['regionId'])){
            $regionId = $request['regionId'];
        }else{
            $regionId = false;
        }
        if($regionId) {
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60007',
                'parameter' => [
                    'regionId' => $regionId,
                    'isLeader' => 1,
                    'userId' => $user['id'],
                ]
            ],"post");
            //结果处理返回
            if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
                return $this->toError(500,'用户关系维护失败');
            }
        }

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
        // 参数提取
        $fields = [
            'userName' => 0,
            'roleId' => 0,
            'userStatus' => 0,
            'realName' => 0,
            'phone' => 0,
            'parentId' => 0,
            'groupId' => 0,
            'isAdministrator' => 0,
            'idCard' => 0,
            'sex' => 0,
            'userRemark' => 0,
            'email' => 0,
        ];
        $user = $this->getArrPars($fields, $request);
        if (false === $user){
            return;
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
        // 绑定区域/站点
        if (isset($request['siteId']) && !empty($request['siteId'])){
            $regionId = $request['siteId'];
        }elseif (isset($request['regionId']) && !empty($request['regionId'])){
            $regionId = $request['regionId'];
        }else{
            $regionId = false;
        }
        // 有区域设置且无变化，直接成功返回
        if ($regionId) {
            // 查询已有关系
            $oldRU = RegionUser::arrFindFirst([
                'region_id' => $regionId,
                'user_id' => $id
            ]);
            if ($oldRU){
                return $this->toSuccess();
            }
        }
        // 涉及区域编辑
        if (isset($request['regionLevel']) && ''!==$request['regionLevel']){
            // 删除区域用户关系
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60008',
                'parameter' => [
                    'userId' => $id,
                ]
            ],"post");
            //失败返回
            if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
                return $this->toError(500,'用户关系维护失败');
            }
        }
        if ($regionId) {
            // 重建区域用户关系
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => '60007',
                'parameter' => [
                    'regionId' => $regionId,
                    'isLeader' => 1,
                    'userId' => $id,
                ]
            ],"post");
            // 失败返回
            if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
                return $this->toError(500,'用户关系维护失败');
            }
        }
        return $this->toSuccess();
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
        // 是快递公司业务员
        $RegionId = (new RegionData())->getRegionIdByUserId($id);
        if (null !== $RegionId){
            return $this->toError(500,'用户为快递公司业务员，不可删除');
        }
//        $user = $result['content']['users'][0];
//
//        if ($this->system != 0 && $user['groupId'] != $this->authed->groupId) {
//            return $this->toError(500, "非法请求!");
//        }
//
        $result = $this->userData->deleteUserById($id);
        if ($result) {
            return $this->toSuccess('删除成功' );
        } else {
            return $this->toError(500,'删除用户失败!');
        }

//        $user['id'] = $id;
//        $user['updateAt'] = time();
//        $user['userStatus'] = 2;
//        //修改用户数据
//        $result = $this->curl->httpRequest($this->Zuul->user,[
//            'code' => '10003',
//            'parameter' => $user
//        ],"post");
//        //错误返回
//        if ($result['statusCode'] != '200') {
//            return $this->toError(500,'更新用户信息失败'.$result['msg']);
//        }
//
//        return $this->toSuccess('禁用成功' , '',200 );

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

    // 获取登录用户行政区域
    public function AreaInfoAction()
    {
        $parameter['insId'] = $this->authed->insId;
        $parameter['userId'] = $this->authed->userId;
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => 10063,
            'parameter' => $parameter
        ],'post');
        if (200 != $result['statusCode']){
            return $this->toError($result['statusCode'], $result['msg']);
        }
        $data = $result['content']['data'][0] ?? null;
        return $this->toSuccess($data);
    }
}

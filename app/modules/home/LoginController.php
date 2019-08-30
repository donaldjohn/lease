<?php
namespace app\modules\home;

use app\common\errors\AppException;
use app\common\errors\MicroException;

use app\models\dispatch\Region;
use app\models\dispatch\RegionUser;
use app\models\users\Institution;
use app\models\users\Trafficpolice;
use app\models\users\User;
use app\modules\BaseController;
use app\services\auth\Authentication;
use app\services\auth\AuthResult;
use app\services\data\CommonData;
use Phalcon\Exception;
use Phalcon\Logger;
use app\services\data\RedisData;

class LoginController extends BaseController
{
    /**
     * 根据用户输入用户名和密码生成jwt token
     * 1.用户名 密码(rsa解密) 登入
     * system =  0,1,2   type = web ios android
     * 登入类型
     *          1.web-主系统
     *          2. web-子系统
     *          3. 手机app
     *              3.1站点登入
     *          4.小程序
     *              4.1门店账号登入
     */
    public function CreateAction()
    {
        $request = $this->request;
        /**
         * 获取用户上传用户名和密码
         */
        if ($request->hasPost("username")) {
            $username = $request->getPost("username");
            $password = $request->getPost("password");
            /**
             * RSA解密
             */
            // $password = $this->RSADec($password);
        } else {
            $json = $request->getJsonRawBody(true);
            if (empty($json["username"]))
                return $this->toError(500,"请输入用户名");
            $username = $json["username"];
            if (empty($json["password"]))
                return $this->toError(500,"请输入密码");
            $password = $json["password"];
            /**
             * RSA解密
             */
            $password = $this->RSADec($password);
            /**
             * 验证当前用户是否在登入黑名单里
             * 由于小程序还有门店的在使用。暂时需要判断type
             */
            if ($this->RedisData->get('USERNAMEBLACKLIST'.strtolower($username)) && $this->type == "web") {
                /**
                 * 在黑名单里
                 */
                if (!isset($json['verification'])) {
                    return $this->toError(500,"请输入验证码");
                }
                $verification = strtolower($json["verification"]);
                if ($this->RedisData->get('vcode_'.$verification) != 1) {
                    return $this->toError(500,"验证码不正确!");
                } else {
                    $this->RedisData->del('vcode_'.$verification);
                    $this->RedisData->del('USERNAMEBLACKLIST'.strtolower($username));
                }
            } else {
                /**
                 * 校验验证码
                 *
                 */
                if (false == $this->config->app->debug) {
                    if ('web' == $this->type
                        && isset($json['isActivepic'])
                        && true==$json['isActivepic']) {
                        if (empty($json["verification"]))
                            return $this->toError(500,"请输入验证码");
                        $verification = strtolower($json["verification"]);
                        // 从Redis查询验证码是否存在
                        $redis = new RedisData();
                        if ($redis->get('vcode_'.$verification) != 1) {
                            return $this->toError(500,"验证码不正确!");
                        } else {
                            $redis->del('vcode_'.$verification);
                        }
                    }
                }
            }
        }


        /**
         * 1.根据用户名查询数据
         *   1.1无数据
         *   1.2有数据
         *       1.2.1(hack验证用户名大小写)
         *       1.2.2验证密码是否正常
         *       1.2.3验证用户状态是否正常
         * 2.匹配用户和系统关系(主系统和子系统需要匹配)
         *   2.1 子系统需要单独匹配区域
         * 3.生成用户信息
         * 4.生成JWT信息
         * 5.返回jwt和不敏感信息
         */
        $userParams = ['userName' => $username];
        $result = $this->userData->getUserByJson($userParams);
        //结果处理返回
        if ($result['statusCode'] == '200') {
            if (count($result['content']['users']) < 1) {
                return $this->toError(500, "用户名或密码错误");
            }
            //判断用户名密码是否正确
            if (!isset($result['content']['users'][0]))
                return $this->toError(500, "用户名或密码错误!");
            $user_result = $result['content']['users'][0];


            //hack 不推荐使用
            if ($user_result['userName'] !== $username) {
                return $this->toError(500, "用户名或密码错误!");
            }

            /**
             * 根据返回的数组进行匹配.理论上存在多个数组,绑定当前系统所对应的机构ID
             * 主系统和子系统用户都能对应机构ID
             * 但是如果出现app和小程序的话默认对应主系统机构
             */
            if (count($result['content']['userInstitutions']) > 0) {
                //用户对应机构
                foreach ($result['content']['userInstitutions'] as $item) {
                    if ($this->type == 'web') {
                        //主系统和子系统绑定
                        if (isset($item['systemId']) && $this->system == $item['systemId']) {
                            $user_result['insId'] = $item['insId'];
                            //is_sub 1不是 2是
                        }
                    } else {
                        //非web绑定主系统机构
                        // if ($item['isAdmin'] == 1) {
                        $user_result['insId'] = $item['insId'];
                        // }
                    }
                }
            }
//            } else {
//                return $this->toError(500,'用户暂无机构ID,请联系管理员!');
//            }

            $header = $this->type;
            if ($header == 'web') {
                if ($user_result['isAdministrator'] == 1 && $this->system != 0) {
                    //查询用户是否是子系统用户
                    // $usercheck = $this->userData->getUserSystem($user_result['id'],$this->system);
                    // if ($usercheck == false) {
                    //     return $this->toError(500, "系统暂无此用户!");
                    // }
                } elseif ($user_result['isAdministrator'] == 2 && $this->system != 0) {
                    //管理员用户根据用户组查看是否关联子系统
                    $result_system = $this->userGroupData->checkUserSystems($user_result['groupId'], $this->system);
                    if ($result_system == false) {
                        return $this->toError(500, "该用户不属于当前系统");
                    }
                }
            }

            /**
             * 判断机构当前状态
             */
            if ($user_result['userType'] == 10) {
                //查询机构状态
                $ins = Trafficpolice::findFirst(['ins_id = :ins_id:', 'bind' => ['ins_id' => $user_result['insId']]]);
                if ($ins == false || $ins->status != 1) {
                    return $this->toError(500, '当前机构不可用！');
                }

            } else if ($user_result['userType'] != 1) {
                if (!isset($user_result['insId'])) {
                    return $this->toError(500, '当前机构不可用！');
                }
                // 判断机构是否启用
                // 需要对应的机构admin 的状态是否启用
                if ($user_result['isAdministrator'] == 1) {
                    //查询主账号状态
                    $ins = $this->modelsManager->createBuilder()
                        ->columns('ui.user_id,ui.ins_id,ui.is_admin,u.user_status,u.is_administrator,u.id')
                        ->addFrom('app\models\users\UserInstitution', 'ui')
                        ->leftJoin('app\models\users\User', 'u.id = ui.user_id', 'u')
                        ->andWhere('ui.ins_id = :ins_id: and u.is_administrator = 2', ['ins_id' => $user_result['insId']])
                        ->getQuery()
                        ->getSingleResult();
                    if ($ins == false || $ins->user_status == 2) {
                        return $this->toError(500, '当前机构不可用！');
                    }
                }
            }

            if (!$this->checkPassword($password, $user_result['password'])) {
                $this->RedisData->set('USERNAMEBLACKLIST' . strtolower($username), 1, 300);
                return $this->toError(500, "密码错误!");
            }

            if ($user_result['userStatus'] != 1) {
                $this->RedisData->set('USERNAMEBLACKLIST' . strtolower($username), 1, 300);
                return $this->toError(500, "用户已禁用,请联系管理员!");
            }


            /**
             * 如果当前系统为子系统 需要增加区域字段
             */
            $userRegionId = -1;
            if ($this->system != 0) {
                /*
                //判断是不是管理员.
                $result_system = $this->userGroupData->checkUserSystems($user_result['groupId'],$this->system);
                if ($result_system == true) {
                    $userRegionId = 0;
                }
                if ($userRegionId == -1) {
                    $result_system = $this->roleData->checkRoleSystems($user_result['roleId'],$this->system);
                    if ($result_system == true) {
                        $userRegionId = 0;
                    }
                }
                if ($userRegionId == -1) {
                    $regionResult = $this->userData->getRegionByUserId($user_result['id']);
                    if (count($regionResult) == 1) {
                        $userRegionId = $regionResult['regionId'];
                    }
                }
                */
                // 查询用户绑定的区域id
                $RU = RegionUser::findFirst([
                    'user_id = :user_id:',
                    'bind' => [
                        'user_id' => $user_result['id']
                    ]
                ]);
                $userRegionId = $RU ? $RU->region_id : 0;
            }

            /**
             * 子系统需要增加regionId
             * 系统管理员regionId = 0
             * 其他用户 查询
             */


            $user = new Authentication();
            $user->userId = $user_result['id'];
            $user->userName = $user_result['realName'];
            $user->roleId = $user_result['roleId'];
            $user->groupId = $user_result['groupId'];
            $user->isAdministrator = $user_result['isAdministrator'];
            $user->regionId = $userRegionId;
            if ($user_result['userType'] == 1) {
                $user->insId = 0;
            } else {
                $user->insId = isset($user_result['insId']) ? $user_result['insId'] : -1;
            }

            if ($user->insId == -1) {
                throw new AppException([500,"当前用户机构异常,请通知管理员!"]);
            }
            $user->system = $this->system;
            $user->userType = $user_result['userType'];
            $result = $this->auth->authenticate_by_user($user);
            // 获取Header中的Type 并进行终端判断
            switch ($header) {
                // 小程序
                case "microprograms":
                    $result = $this->microProgramsLogin($user->groupId, $result);
                    break;
                // IOS
                case "ios":
                // 安卓
                case "android":
                    $result = $this->appLogin($user->userId, $result);
                    $user->regionId = $result->site['id'];
                    $result->access_token = $this->auth->authenticate_by_user($user)->access_token;
                    break;
                // 默认Web
                default:
                    if ($user->isAdministrator == 2) {
                        //usergroup为角色名称
                        $role = $this->userGroupData->getUserGroupById($user_result['groupId']);
                        if ($role == false) {
                            $roleName = '无';
                        } else {
                            $roleName = $role['groupName'];
                        }
                    } else {
                        $role = $this->roleData->getRoleById($user_result['roleId']);
                        if ($role == false) {
                            $roleName = '无';
                        } else {
                            $roleName = $role['roleName'];
                        }
                    }

                    //根据usertype获取名称
                    $typeName = $this->userData->getTypeName($user_result['userType']);

                    $result = ['access_token' => $result->access_token, 'roleName' => $roleName, 'typeName' => $typeName];
            }
            return $this->toSuccess($result);
        } else {
            return $this->toError($result['statusCode'],"登入失败");
        }
    }



    public function blackUserNameAction(){
        $userName = $this->request->getQuery("userName","string",null,false);
        if($this->RedisData->get('USERNAMEBLACKLIST'.strtolower($userName))) {
            return $this->toSuccess(true);
        } else {
            return $this->toSuccess(false);
        }
    }

    /**
     * /小程序登陆判断
     * @param $groupId 用户所属机构ID
     * @param $data 用户登陆信息
     * @return 成功返回登陆信息，失败返回错误信息
     */
    private function microProgramsLogin($groupId, $data) {
        // 拼装接口所需要的参数
        $params = [
            "code" => "10014",
            "parameter" => [
                'id' => $groupId
            ]
        ];
        // 请求API接口
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        // 判断登陆小程序的用户是否属于工厂组织
        if ($result['content']['groupDOS'][0]['groupType'] != 5) {
            // 如果为非工厂用户，登陆失败
            return $this->toError(400,"登入失败");
        }
        // 返回结果
        return $data;

    }

    // 站点登录发送验证码
    public function SiteLoginSendSMSCodeAction()
    {
        $phone = $_GET['phone'] ?? '';
        // 对传参进行判断，手机号码、验证码不能为空
        if (empty($phone) || 0==preg_match('/^1\d{10}$/u', $phone)) {
            return $this->toError(500, "手机号有误");
        }
        // 查询站点用户是否存在
        $ru =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\users\User','u')
            ->addfrom('app\models\dispatch\RegionUser', 'ru')
            ->where('u.phone = :phone: AND u.is_delete = 0 AND ru.user_id = u.id',
                ['phone'=>$phone])
            ->columns('u.*')
            ->getQuery()
            ->getSingleResult();
        if (!$ru){
            return $this->toError(500, '非网点用户，请检查手机号');
        }
        $bol = (new CommonData())->SendPhoneSMSCode($phone, CommonData::APP_POSTAL_RIDER);
        if (false === $bol){
            return $this->toError(500 , '短信发送失败，请重试');
        }
        return $this->toSuccess();
    }

    // 站点登录通过验证码
    public function SiteLoginBySMSCodeAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        $phone  = $request['phone'] ?? '';
        $code   = $request['code'] ?? '';
        // 对传参进行判断，手机号码、验证码不能为空
        if (empty($phone) || 0==preg_match('/^1\d{10}$/u', $phone)) {
            return $this->toError(500, "手机号码格式错误");
        }
        $bol = (new CommonData())->CheckPhoneSMSCode($phone, $code, CommonData::APP_POSTAL_RIDER, true);
        if (false === $bol){
            return $this->toError(500, '验证码有误,请重试');
        }
        $user = User::arrFindFirst([
            'phone' => $phone,
            'is_delete' => 0,
        ]);
        if (false === $user){
            return $this->toError(500,"用户名或密码错误");
        }
        $user = $user->toArray();
        // 是否启用 1启用 2禁用
        if (2==$user['user_status']){
            return $this->toError(500, "当前用户已被禁用！");
        }
        $result = $this->SiteLoginInfoByUser($user);
        return $this->toSuccess($result);
    }

    /**
     * 站点APP登录
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function SiteloginAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $username = $request['username'] ?? false;
        $password = $request['password'] ?? false;
        if (!$username || !$password){
            return $this->toError(500,"请输入用户名和密码");
        }
        // 解密密码
        $password = $this->RSADec($password);
        // 查询用户信息
        $user = User::findFirst([
            'user_name = :username: and is_delete = 0',
            'bind' => [
                'username' => $username,
            ]
        ]);
        if (false === $user){
            return $this->toError(500,"用户名或密码错误");
        }
        $user = $user->toArray();
        if (!$this->checkPassword($password, $user['password'])){
            return $this->toError(500, "密码错误!");
        }
        // 是否启用 1启用 2禁用
        if (2==$user['user_status']){
            return $this->toError(500, "当前用户已被禁用！");
        }
        $result = $this->SiteLoginInfoByUser($user);
        return $this->toSuccess($result);
    }
    // 根据user数组查询处理站点登陆
    private function SiteLoginInfoByUser(array $user)
    {
        // 查询站点信息
        $site =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\RegionUser', 'ru')
            ->where('ru.user_id = :user_id:', ['user_id'=>$user['id']])
            // 查询关联用户
            ->join('app\models\dispatch\Region','ru.region_id = r.id','r')
            ->columns('r.id, r.region_code AS siteCode, r.region_name AS siteName,r.provice_id AS proviceId, r.city_id AS cityId, r.address, ru.user_id, r.ins_id, ru.is_leader, r.region_status AS regionStatus')
            ->getQuery()
            ->getSingleResult();
        if (false===$site){
            throw new AppException([500, '用户没有绑定区域或站点']);
        }
        $site = $site->toArray();
        if (2==$site['regionStatus']){
            throw new AppException([500, '当前网点已被禁用']);
        }
        $site['linkman'] = $user['real_name'];
        $site['phone'] = $user['phone'];
        // 获取行政区域地址
        if ($site['proviceId'] > 0 && $site['cityId'] > 0) {
            $site['proviceName'] = $this->userData->getRegionName($site['proviceId'])['areaName'] ?? '';
            $site['cityName'] = $this->userData->getRegionName($site['cityId'])['areaName'] ?? '';
            $site['address'] = $site['proviceName'] . $site['cityName'] . $site['address'];
        }

        /**
         * auth 10002
         */
        //调用微服务接口获取数据
//        if ($this->type =="ios") {
//            $type = 2;
//        } else if ($this->type =="android") {
//            $type = 1;
//        } else {
//            return $this->toError(500,'请通过客户端登入');
//        }
//        $json = [
//            'code' => 10002,
//            'parameter' => [
//                "userId" => $user['id'],
//                "deviceToken" =>  $this->request->getHeader("deviceToken"),
//                "deviceUuid" => $this->request->getHeader("deviceUUID"),
//                "packageName" => $this->request->getHeader("packageName"),
//                "deviceType" => $type,
//
//            ]
//        ];
//        $result2 = $this->userData->postCommon2($json,$this->config->ZuulBaseUrl2.'/auth/apiservice');
//        if (isset($result2['msg']) && $result2['msg'] != '操作成功') {
//            throw new AppException([500,'登入失败，获取用户信息失败！']);
//        }

        $userJWT = new Authentication();
        $userJWT->userId = $user['id'];
        $userJWT->userName = $user['real_name'];
        $userJWT->roleId = $user['role_id'];
        $userJWT->groupId = $user['group_id'];
        $userJWT->isAdministrator = $user['is_administrator'];
        $userJWT->regionId = $site['id'];
        $userJWT->insId = $site['ins_id'];
        $result = $this->auth->authenticate_by_user($userJWT);

        // 返回
        $fields = [
            'id' => 0,
            'siteCode' => '',
            'siteName' => '',
            'linkman' => '',
            'phone' => '',
            'address' => '',
        ];
        $result->site = $this->backData($fields,$site);
        return $result;
    }

    /**
     * 安卓 IOS登陆验证
     * @param $userId 用户ID
     * @param $data 用户登陆信息
     * @return mixed 返回用户信息及站点信息
     */
    private function appLogin($userId, $data)
    {

        // 获取登录用户的站点ID
        $regionId = $this->RegionData->getRegionIdByUserId($userId);
        $site =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Region','r')
            ->where('r.id = :regionId:', ['regionId'=>$regionId])
            // 查询关联用户
            ->leftJoin('app\models\dispatch\RegionUser', 'ru.region_id = r.id','ru')
            // ->andWhere('ru.is_leader = 2')
            ->columns('r.id, r.region_code AS siteCode, r.region_name AS siteName,r.provice_id AS proviceId, r.city_id AS cityId, r.address, ru.user_id')
            ->getQuery()
            ->getSingleResult();
        if (false===$site){
            throw new AppException([500, '用户没有绑定区域或站点']);
        }
        $site = $site->toArray();
        if ($site['user_id'] > 0){
            // 获取联系人信息
            $linkMan = $this->userData->getUserById($site['user_id']);
            $site['linkman'] = $linkMan['realName'];
            $site['phone'] = $linkMan['phone'];
        }
        // 获取行政区域地址
        if ($site['proviceId'] > 0 && $site['cityId'] > 0) {
            $site['proviceName'] = $this->userData->getRegionName($site['proviceId'])['areaName'] ?? '';
            $site['cityName'] = $this->userData->getRegionName($site['cityId'])['areaName'] ?? '';
            $site['address'] = $site['proviceName'] . $site['cityName'] . $site['address'];
        }
        // 返回
        $fields = [
            'id' => 0,
            'siteCode' => '',
            'siteName' => '',
            'linkman' => '',
            'phone' => '',
            'address' => '',
        ];
        $site = $this->backData($fields,$site);

        $data->site = $site;
        // 获取站点对应机构及区域信息
//        $insId = $result['content']['siteDOS'][0]['insId'];
//        $regionId = $result['content']['siteDOS'][0]['regionId'];
//
//        // 获取区域信息
//        $param = [
//            'code' => '60004',
//            'parameter' => [
//                'id' => $regionId
//            ]
//        ];
//        $result = $this->curl->httpRequest($this->Zuul->dispatch,$param,"post");
//        $data->region = isset($result['content']['regionDOS'][0]) ? $result['content']['regionDOS'][0] : [];
//
//        // 获取机构信息
//        $param = [
//            'code' => '10049',
//            'parameter' => [
//                'id' => $insId
//            ]
//        ];
//        $result = $this->curl->httpRequest($this->Zuul->user,$param,"post");
//        $data->ins = isset($result['content']['institutions'][0]) ? $result['content']['institutions'][0] : [];
//
//        // 返回站点信息、区域信息、机构信息
        return $data;
    }


    private function checkPassword($password, $hashPassword)
    {
        return $this->security->checkHash($password, $hashPassword);
    }

}

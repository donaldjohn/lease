<?php
namespace app\services\auth;


use app\common\errors\AuthenticationException;
use app\common\errors\EmployException;
use app\common\library\HttpService;
use app\models\dispatch\RegionUser;
use app\models\users\System;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Phalcon\Di\Injectable;

class AuthService extends Injectable
{
    const JWT_HEADER_KEY    = 'Authorization';
    const JWT_HEADER_PREFIX = 'Bearer';

    const JWT_ALLOWED_ALGS = [ 'HS256', 'HS512', 'HS384', 'RS256', 'RS384', 'RS512' ];

    private $__authentication = null;



    /**
     * 判断当前身份是否有权限访问 $uri
     * @return bool
     */
    public function allow($uri = null)
    {
        $url = $this->getBaseRequestUri();
        if ($this->isPublic($url)) return true;

        $user = $this->getAuthentication();



        //根据判断该用户是否有权限 code
        // TODO: 权限验证还没修改

        //$url = $this->getBaseRequestUri();
        //判断当前用户是否有权限。
        //如果用户登入根据用户角色调用api判断是否有权限
        return true;
    }

    /**
     * @param $uri
     * @return bool
     * 不进入权限
     */
    public function isPublic($uri)
    {
        $dispatcher = $this->dispatcher;
        $module     = strtoupper($dispatcher->getModuleName());
        $controller = strtoupper($dispatcher->getControllerName());
        $action     = strtoupper($dispatcher->getActionName());

        //HOME 模块
        if ($module == "HOME" ) return true;
        if ($module == "WARRANTY" && $controller == 'OUTSIDE') return true;
        //骑手登入
        if ($module == 'DRIVERSAPP' && $controller == 'INDEX' && $action == 'LOGIN' ) return true;

        // TODO:预废弃门店地图接口
        if ($module == 'CABINET' && $controller == 'DRIVERS' && $action == 'MAP' ) return true;
        // 得威出行APP首页门店地图
        if ($module == 'DRIVERSAPP' && $controller == 'INDEX' && $action == 'STOREMAP' ) return true;

        // 骑手APP获取开通城市列表
        if ($module == 'CABINET' && $controller == 'DRIVERS' && $action == 'CITY' ) return true;

        if ($module == 'QROCDE' && $controller == 'INDEX' && $action == 'IMAGE' ) return true;

        if ($module == 'CABINET' && $controller == 'DATA' && $action == 'BOARD' ) return true;

        if ($module == 'CABINET' && $controller == 'DATA' && $action == 'ROOM' ) return true;

        if ($module == 'QRCODE' && $controller == 'INDEX' && $action == 'IMAGE' ) return true;

        // 邮管局骑手APP登录
        if ($module == 'POSTOFFICEAPP' && $controller == 'DRIVER' && $action == 'LOGIN' ) return true;
        // 邮管局骑手APP获取短信验证码
        if ($module == 'POSTOFFICEAPP' && $controller == 'DRIVER' && $action == 'GETSMSCODECODE' ) return true;
        // 邮管局骑手APP重置密码
        if ($module == 'POSTOFFICEAPP' && $controller == 'DRIVER' && $action == 'RESETPASSWORD' ) return true;
        // 邮管局骑手APP获取附近门店
        if ($module == 'POSTOFFICEAPP' && $controller == 'STORE' && $action == 'NEARBYSTORE' ) return true;
        // 是否需要是实人认证
        if ($module == 'POSTOFFICEAPP' && $controller == 'STORE' && $action == 'NEEDAUTH' ) return true;
        // 获取油管的城市信息
        if ($module == 'POSTOFFICEAPP' && $controller == 'STORE' && $action == 'CITY' ) return true;

        // 开放给联保的维修单更新接口
        if ($module == 'RENT' && $controller == 'REPAIRORDER' && $action == 'UPDATE' )
            return true;

        if ($module == 'PAY') return true;

        return false;
    }



    /**
     * 获取当前身份，如果尚未认证则尝试从 header 中取 JWT 进行认证，如认证失败则创建 guest 身份
     * @return Authentication
     */
    public function getAuthentication()
    {
        if ($this->__authentication == null) {
            $this->__authentication = $this->authenticate_by_jwt();
        }
        if ($this->__authentication == null) {
            $this->__authentication = Authentication::newGuest();
        }
        return $this->__authentication;
    }


    public function authenticate_by_user($user)
    {
        $jwt = $this->config->auth->jwt;

        // 创建认证对象
        $authentication = $this->createAuthentication($user);
        // 设置认证对象
        $this->__authentication = $authentication;
//        // 更新认证记录
//        $this->onAuthenticated($user);
        // 生成 JWT
        $token = JWT::encode($authentication, $jwt->key);
        $this->sendAuthHeader($token);
        /**
         * token 存入redis
         */
        $type = $this->request->getHeader("type");
        if (empty($type)) {
            $type = "web";
        }
        $json = ['token' => $token,'userId' => $user->userId,'type' => $type];
        $result2 = $this->userData->postCommon2($json,$this->config->ZuulBaseUrl2.'/auth/saveToken');

        // 返回认证结果
        $result = new AuthResult($token);
        $result->expires_in = $jwt->exp;
        return $result;
    }

    public function authenticate_by_jwt()
    {
        $request = $this->request;
        $type = $this->request->getHeader("type");
        if (empty($type)) {
            $type = "web";
        }
        //if ($request->getBasicAuth() != null) return null;

        $raw_token = $request->getHeader(self::JWT_HEADER_KEY);

        if (! $raw_token) return null;

        $jwt_token = trim(str_ireplace(self::JWT_HEADER_PREFIX, '', $raw_token));

        $jwt = $this->config->auth->jwt;
        try {
            $jwt_payload = JWT::decode($jwt_token, $jwt->key, array_keys(JWT::$supported_algs));
        } catch (SignatureInvalidException $e) {
            throw new AuthenticationException(HttpService::AuthTokenInvalid, $e);
        } catch (ExpiredException $e) {
            throw new AuthenticationException(HttpService::AuthTokenExpired, $e);
        }

        /**
         * 查找token是否存在redis里
         *
         * lq = 8 白名单
         */

//        if ($type != "web") {
//            $json = ['token' => $raw_token,'userId' => $jwt_payload->userId,'type' => $type];
//            $result3 = $this->userData->postCommon2($json,$this->config->ZuulBaseUrl2.'/auth/findToken');
//        }

        $json = ['token' => $jwt_token,'userId' => $jwt_payload->userId,'type' => $type];
        $result3 = $this->userData->postCommon2($json,$this->config->ZuulBaseUrl2.'/auth/findToken');
        if(isset($result3['data']['data'])) {
            if ($type != "web" &&  $result3['data']['data'] != $jwt_token) {
                throw new EmployException();
            }
        }

        /**
         * 判断token是否快过期,更新token
         */
        if ($jwt_payload->exp < (time()+120)) {
            $authentication = $this->createAuthentication($jwt_payload);
            ////        // 每次请求都刷新 token
            $token = JWT::encode($authentication, $jwt->key);
            $this->sendAuthHeader($token);
            /**
             * token 存入redis
             */
            $json = ['token' => $token,'userId' => $authentication->userId,'type' => $type];
            $result2 = $this->userData->postCommon2($json,$this->config->ZuulBaseUrl2.'/auth/saveToken');

            return $authentication;
        } else {
            $this->sendAuthHeader($jwt_token);
            $authentication = $this->createAuthentication($jwt_payload);
            return $authentication;
        }

        /**
         * 主系统和子系统切换
         * isAdminator = 2 才能切换 token => 更新insId regionId
         */
//        if (isset($jwt_payload['isAdministrator']) && $jwt_payload['isAdministrator'] == 2) {
//            /**
//             * 获取系统ID
//             */
//            $systemId = 0;
//            $system_code = $this->request->getHeader("system");
//            if (empty($system_code)) {
//                $this->system = 0;
//                unset($system_code);
//            } else {
//                $systemDetail = System::find(['conditions' => ['system_code = :system_code:'],'bind' =>['system_code' => $system_code]]);
//                if ($systemDetail == false) {
//                    throw new DataException([500,'子系统不存在!请通知管理员']);
//                } else {
//                    if($systemDetail->getSystemStatus() != 1) {
//                        throw new DataException([500,'子系统已禁用!请通知管理员']);
//                    }
//                    $systemId = $systemDetail->getId();
//                }
//            }
//            /**
//             * 系统ID不一样说明需要进行token 刷新
//             */
//            if ($jwt_payload['systemId'] != $systemId) {
//                $jwt_payload["system"] = $systemId;
//                if ($systemId > 0) {
//                    // 查询用户绑定的区域id
//                    $RU = RegionUser::findFirst([
//                        'user_id = :user_id:',
//                        'bind' => [
//                            'user_id' => $jwt_payload['userid']
//                        ]
//                    ]);
//                    $userRegionId = $RU ? $RU->region_id : 0;
//                    $jwt_payload['regionId'] = $userRegionId;
//                    //更新用户机构ID
//                    $user = $this->modelsManager->createBuilder()
//                        ->columns('ui.user_id,ui.ins_id,ui.is_admin,i.is_sub,i.system_id')
//
//                } else {
//                    $jwt_payload['regionId'] = -1;
//                    //更新用户机构ID
//                }
//            }
//
//        }

//        $authentication = $this->createAuthentication($jwt_payload);
////
////        // 每次请求都刷新 token
//        $token = JWT::encode($authentication, $jwt->key);
//        $this->sendAuthHeader($token);
//  return $authentication;

    }


    // 创建并返回认证对象
    private function createAuthentication($user)
    {
        $nowTime = time();
        $jwt = $this->config->auth->jwt;

        $authentication = new Authentication();
        if (isset($user->userId)) {
            $authentication->userId = $user->userId;
        } else {
            $authentication->userId = $user->userid;
        }
        if (isset($user->userName)) {
            $authentication->userName = $user->userName;
        } else {
            $authentication->userName = $user->username;
        }

        $authentication->groupId = $user->groupId;
        $authentication->roleId = $user->roleId;
        $authentication->isAdministrator = $user->isAdministrator;
        $authentication->system = $user->system;
        $authentication->insId = $user->insId;
        $authentication->userType = $user->userType;
        $authentication->regionId = $user->regionId;
        if (isset($user->deviceUUID) && !empty($user->deviceUUID)){
            $authentication->deviceUUID = $user->deviceUUID;
        }
        //TODO: 其他不敏感数据
        $authentication->iss = $jwt->iss;
        $authentication->aud = $jwt->aud;
        $authentication->iat = $nowTime;
        $nbfTime = $nowTime-60;
        $authentication->nbf = $nbfTime;
        $type = $this->request->getHeader('type');

        if ($type == 'web' || empty($type)) {
            $authentication->exp = $nowTime + $jwt->exp;
        } else {
            $authentication->exp = $nowTime + 86400*90;
        }
        return $authentication;
    }

    //将jwt token返回在头部
    public function sendAuthHeader($token)
    {
        $this->response->setHeader(self::JWT_HEADER_KEY, self::JWT_HEADER_PREFIX . ' ' . $token);
    }

    // 返回去掉 querystring 之后的 request_uri
    protected function getBaseRequestUri($uri = null)
    {
        if ($uri == null) $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '?') === false) return $uri;
        return strstr($uri, '?', true);
    }

    public function checkPassword($password, $hashPassword)
    {
        return $this->security->checkHash($password, $hashPassword);
    }

    public function hashPassword($password)
    {
        return $this->security->hash($password);
    }

    /**
     * @return mixed
     * 获取当前IP
     */
    public function getRemoteAddr() {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ipAddress = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        }
        return $ipAddress;
    }
}
<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: MicroController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\home;


use app\modules\BaseController;
use app\common\errors\AppException;
use app\services\auth\Authentication;
use app\services\data\RedisData;
class SecondhandmicroController extends BaseController
{

    /**
     * 小程序工厂端登入
     * 必须是供应商的子账号才能登入
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
            $password = $this->RSADec($password);
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

            if (!$this->checkPassword($password,$user_result['password']))
                return $this->toError(500, "密码错误!");

            if ($user_result['userStatus'] != 1)
                return $this->toError(500, "用户已禁用,请联系管理员!");


            /**
             *
             *   查看当前用户是否属于得威运营
             *     userType = 1 isAdministrator =1 parent_id != 0
             */
            if ($user_result['userType'] != 1 ) {
                return $this->toError(500, "用户不属于该系统!无法登入");
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
            $user->regionId = -1;
            $user->insId = 0;
            $user->system = 0;
            $user->userType = $user_result['userType'];
            $result = $this->auth->authenticate_by_user($user);
            // 获取Header中的Type 并进行终端判断

            return $this->toSuccess($result);
        } else {
            return $this->toError($result['statusCode'],"登入失败");
        }




    }


    private function checkPassword($password, $hashPassword)
    {
        return $this->security->checkHash($password, $hashPassword);
    }

}
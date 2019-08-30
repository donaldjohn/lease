<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: PostofficeController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\common\library\SystemType;
use app\modules\BaseController;

//邮管局管理
class PostofficeController extends BaseController
{

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 列表
     */
    public function ListAction()
    {
        $this->logger->info("邮管局查询");
        $company = $this->userData->getPostOfficePage($this->authed,SystemType::POSTOFFICE);
        return $this->toSuccess($company['data'],$company['meta']);
    }

    public function OneAction($id)
    {
        $user = $this->userData->getUserById($id);
        if ($user['insId'] == -1)
            return $this->toError(500,'用户暂无邮管局信息');
        //根据user insId 获取邮管局信息
        $company = $this->userData->getPostOfficeByInsId($user['insId']);
        $company['userName'] = $user['userName'];
        $company['phone'] = $user['phone'];
        $company['userStatus'] = $user['userStatus'];
        $company['groupId'] = $user['groupId'];
        $company['userId'] = $user['id'];
        $company['createAt'] = !empty($company['createAt']) ? date('Y-m-d H:i:s', $company['createAt']) : '-';
        $company['updateAt'] = !empty($company['updateAt']) ? date('Y-m-d H:i:s', $company['updateAt']) : '-';

        if ($company['provinceId'] != 0){
            $p = $this->userData->getRegionName($company['provinceId']);
            $company['provinceName'] = $p['areaName'];
            unset($p);
        }
        if ($company['cityId'] != 0){
            $c = $this->userData->getRegionName($company['cityId']);
            $company['cityName'] = $c['areaName'];
            unset($c);
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
        $postoffice = $this->userData->createPostOffice($this->authed,$json,SystemType::POSTOFFICE);
        return $this->toSuccess($postoffice);
    }


    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $fields = [
            'phone' => 0,
            'groupId' => 0,
            'userStatus' => 0,
        ];
        $parameter = $this->getArrPars($fields, $json);
        if (!$parameter){
            return;
        }
        //更新user表
        $parameter['id'] = $id;
        $parameter['updateAt'] = time();
        $user = $this->userData->updateUser($parameter);

        $fields = [
            'insId' => 0,
            'id' => 'ID不能为空',
            'level' => 'level不能为空',
            'provinceId' => 0,
            'cityId' => 0,
            'linkMan' => 0,
            "linkPhone" => 0,
            'remark' => 0,
        ];
        $parameter = $this->getArrPars($fields, $json);
        if (!$parameter){
            return;
        }
        //TODO: hack
        if (isset($json['remark']))
            $parameter['remark'] = $json['remark'];

        if($parameter['level'] == 2) {
            //判断是否已经存在邮管局
            $params = ['provinceId' => $parameter['provinceId'], "level" => 2,'cityId' => 0];
            $result = $this->curl->httpRequest($this->Zuul->user,[
                'code' => '10074',
                'parameter' => $params,
            ],"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                return $this->toError($result['statusCode'], $result['msg']);
            }
            if (isset($result['content']['postoffices'][0]) && $parameter['insId'] != $result['content']['postoffices'][0]['insId']) {
                return $this->toError(500,'已经存在该邮管局');
            }
            unset($result);
            unset($params);
            //参数绑定
            $parameter['parentId'] = 0;
            $parameter['cityId'] = 0;
            //获取新的邮管局名称
            $params = ["code" => "10022","parameter" => ['areaId' => $parameter['provinceId']]];
            $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                return $this->toError($result['statusCode'], $result['msg']);
            }
            if (!isset($result['content']['data'][0]))
                return $this->toError(500, "数据不存在");
            $parameter['postName'] = $result['content']['data'][0]['areaName'].'邮管局';
        } else if ($parameter['level'] == 3) {
            $params = ["code" => "10022","parameter" => ['areaId' => $parameter['cityId']]];
            $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
               return $this->toError($result['statusCode'], $result['msg']);
            }
            if (!isset($result['content']['data'][0]))
                return $this->toError(500, "数据不存在");
            $parameter['postName'] = $result['content']['data'][0]['areaName'].'邮管局';
            $params = ['provinceId' => $json['provinceId'], "level" => 2,'cityId' => 0];
            $post = $this->userData->getPostOffice($params);
            $parameter['parentId'] = $post['id'];
        } else {
            return $this->toError(500,"level参数错误");
        }
        $company = $this->userData->updatePostOffice($parameter);
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
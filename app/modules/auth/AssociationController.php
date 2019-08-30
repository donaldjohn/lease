<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: ExpassController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\common\errors\DataException;
use app\common\library\SystemType;
use app\modules\BaseController;

//快递协会管理
class AssociationController extends BaseController
{
    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws DataException
     */

    public function ListAction()
    {
        $this->logger->info("快递协会查询");
        $company = $this->userData->getAssociationPage($this->authed,SystemType::ASSOCIATION);
        return $this->toSuccess($company['data'],$company['meta']);
    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws DataException
     */
    public function OneAction($id)
    {
        $user = $this->userData->getUserById($id);
        if ($user['insId'] == '-1')
            return $this->toError(500,'用户暂无快递协会信息');
        //根据user insId 获取邮管局信息
        $company = $this->userData->getAssociationByInsId($user['insId']);
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
        if ($company['groupId']) {
            $group = $this->userGroupData->getUserGroupById($company['groupId']);
            $company['groupName'] = isset($group['groupName']) ?  $group['groupName'] : '无';
        }


        return $this->toSuccess($company);

    }

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws DataException
     */
    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['isAdministrator'] = 2;
        $json['insId'] = $this->authed->insId;
        $postoffice = $this->userData->createAssociation($this->authed,$json,SystemType::ASSOCIATION);
        return $this->toSuccess($postoffice);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface|void
     * @throws DataException
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     */
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
        unset($parameter);
        //根据 provinceId cityId  生成 associationName
        //先找对应 邮管局ID
        if (isset($json['cityId']) && $json['cityId'] != 0) {
            //10074
            $region= $this->userData->getRegionName($json['cityId']);
            $params = ["code" => "10074", "parameter" => ['cityId' => $json['cityId']]];
            $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
            if (isset($result['statusCode']) && $result['statusCode'] != '200')
                throw new DataException();
            if (!isset($result['content']['postoffices'][0]['id']))
                throw new  DataException([500,'暂无'.$region['areaName'].'邮管局']);
            $postId = $result['content']['postoffices'][0]['id'];
            //$region= $this->userData->getRegionName($json['cityId']);
            $json['associationName'] = $region['areaName'].'快递协会';
            $params = ['provinceId' => $json['provinceId'], "level" => 2,'cityId' => 0];
            $post = $this->userData->getAssociation($params);
            $json['parentId'] = $post['id'];

        }else if (isset($json['provinceId'])) {
            $region= $this->userData->getRegionName($json['provinceId']);
            $params = ["code" => "10074", "parameter" => ['provinceId' => $json['provinceId']]];
            $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
            if (isset($result['statusCode']) && $result['statusCode'] != '200')
                throw new DataException();
            if (!isset($result['content']['postoffices'][0]['id']))
                throw new  DataException([500,'暂无'.$region['areaName'].'邮管局']);
            $postId = $result['content']['postoffices'][0]['id'];
            //$region= $this->userData->getRegionName($json['provinceId']);
            $json['associationName'] = $region['areaName'].'快递协会';
            $json['parentId'] = 0;
        }


        //更新快递协会
        if (!isset($json['id'])) return $this->toError(500,'快递协会Id必传');
        $fields = [
            'insId' => 0,
            'associationName' => 0,
            'linkMan' => 0,
            'linkPhone' => 0,
            'provinceId' => 0,
            'cityId' => 0,
            'remark' => 0,
            'postId' => 0,
            'parentId' => 0,
        ];
        $parameter = $this->getArrPars($fields, $json);
        if (!$parameter){
            return;
        }
        $parameter['id'] = $json['id'];
        $parameter['updateAt'] = time();
        $parameter['postId'] = $postId;
        if (isset($json['storeType']))
            $parameter['storeType'] = $json['storeType'];
        $company = $this->userData->updateAssociation($parameter);
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
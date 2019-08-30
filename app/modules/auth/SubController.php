<?php
namespace app\modules\auth;


use app\modules\BaseController;

/**
 * Class SubapipiController
 * @package app\modules\auth
 *  子系统
 */
class SubController extends BaseController
{

    public function ListAction()
    {
        return $this->toSuccess(true);
        /**
         * 如果传过来的参数中有type则执行不分页查找
         */
        $type = $this->request->getQuery("type","string","page");
        $systemType = $this->request->getQuery("systemType","string",null);
        if ($type != "page") {
            return $this->getSubList(1,$systemType);
        }
        /**
         * 一般情况下进行分页查询
         */
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = ["pageNum" => (int)$pageNum,"pageSize" => (int)$pageSize];

        /**
         * 参数绑定
         */
        if (!empty($this->request->getQuery('systemName', "string",null))){
            $parameter['systemName'] = $this->request->getQuery('systemName', "string");
        }
        if (!empty($this->request->getQuery('systemType', "int",null))){
            $parameter['systemType'] = (int)$this->request->getQuery('systemType', "int");
        }
        if (!empty($this->request->getQuery('systemStatus', "int",null))){
            $parameter['systemStatus'] = (int)$this->request->getQuery('systemStatus', "int");
        }

        //调用微服务接口获取数据
        $params = ["code" => "10043","parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            $meta['total'] = isset($result['content']['pageInfo']['total']) ?  $result['content']['pageInfo']['total'] : 0;
            $meta['pageNum'] = $pageNum;
            $meta['pageSize'] = $pageSize;
            return $this->toSuccess($result['content']['systems'],$meta);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }


    }


    public function OneAction($id)
    {
        return $this->toSuccess(true);
        //根据id查询数据
        $res = ["id" => $id];
        $json = ['code'=> 10043,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if(count($result['content']['systems']) == 1) {
                $res = $result['content']['systems'][0];
                isset($res['updateAt']) ? $res['updateAt'] = date('Y-m-d H:i:s',$res['updateAt']) : '';
                isset($res['createAt']) ? $res['createAt'] = date('Y-m-d H:i:s',$res['createAt']) : '';

                return $this->toSuccess($res);

            }
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    public function CreateAction()
    {
        return $this->toSuccess(true);
        $res = [];
        $request = $this->request->getJsonRawBody();
        if (!isset($request->systemName)) return $this->toError(500,"系统名称必填");
        $res['systemName'] = $request->systemName;
        if (!isset($request->systemType)) return $this->toError(500,"子系统类别必填");
        $res['systemType'] = $request->systemType;
        if (!isset($request->systemCode)) return $this->toError(500,"子系统代码必填");
        $res['systemCode'] = $request->systemCode;
        if (!isset($request->systemStatus)) return $this->toError(500,"子系统状态必填");
        $res['systemStatus'] = $request->systemStatus;
        $res['createAt'] = time();
        $params = ["code" => "10041","parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }


    public function UpdateAction($id)
    {
        return $this->toSuccess(true);
        $res = ["id" => $id];
        $json = ['code' => 10043,"parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if(count($result['content']['systems']) != 1)
                return $this->toError($result['statusCode'],$result['msg']);
            $res = $this->request->getJsonRawBody(true);
            //java 判断逻辑有问题
                $params = $result['content']['systems'][0];
                $result = [];
                $result["id"] = $id;
                if (isset($res['systemName']) &&  $params["systemName"] != $res['systemName'])
                    $result["systemName"]  = $res['systemName'];

                if (isset($res['systemType']) && $params["systemType"] != (int)$res['systemType'])
                    $result["systemType"] = (int)$res['systemType'];

                if (isset($res['systemCode'])  && $params["systemCode"] != $res['systemCode'])
                    $result["systemCode"] = $res['systemCode'];
                if (isset($res['systemStatus'])  && $params["systemStatus"] != $res['systemStatus'])
                    $result["systemStatus"] = $res['systemStatus'];
            $result['updateAt'] = time();
            $put_params = ["code" => "10044","parameter" => $result];
            $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
            if ($result['statusCode'] == '200') {
                return $this->toSuccess($result);
            } else {
                return $this->toError($result['statusCode'],$result['msg']);
            }
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 删除
     */
    public function DeleteAction($id)
    {
        return $this->toSuccess(true);
        $res = ["id" => $id];
        $json = ["code" => 10042,"parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user, $json, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'], $result['msg']);
        }
    }

    /**
     * 绑定用户组系统
     */
    public function UsergroupAction($groupId)
    {
        $systems = [];
        $res = ["groupId" => $groupId];
        $params = [
            'code' => 10060,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            $result = $result['content']['groupSystemDOS'];
            foreach($result as $key => $item) {
                $systems[] = $item['systemId'];
            }
            return $this->toSuccess($systems);

        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }


    //TODO:: 剔除和groupType不一样的类型
    public function UpdateusergroupAction($groupId)
    {
        $systems = $this->request->getJsonRawBody(true);
        $systems = $systems['systems'];
        /**
         * 查询已有的systems
         */
        $res = ["groupId" => $groupId];
        $params = [
            'code' => 10060,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if (isset($result['content']['groupSystemDOS'])) {
                $result = $result['content']['groupSystemDOS'];
                foreach($result as $key => $item) {
                    //不在新的系统里 删除
                    if (!in_array($item['systemId'],$systems)) {
                        $this->subData->DeleteGroupSystemById($item['id']);
                    } else {
                        $key1 = array_search($item['systemId'],$systems);
                        unset($systems[$key1]);
                    }
                }
                foreach ($systems as $item) {
                    //新增
                    $this->subData->AddGroupSystem($groupId,$item);
                }
                return $this->toSuccess(true);
            } else {
                foreach ($systems as $item) {
                    //新增
                    $this->subData->AddGroupSystem($groupId,$item);
                }
                return $this->toSuccess(true);
            }

        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }

    /**
     * 绑定角色子系统
     */
    public function RoleAction($roleId)
    {

        $systems = [];
        $res = ["roleId" => $roleId];
        $params = [
            'code' => 10065,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            $result = $result['content']['roleSystemDOs'];
            foreach($result as $key => $item) {
                $systems[] = $item['systemId'];
            }
            return $this->toSuccess($systems);

        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }

    public function UpdateroleAction($roleId)
    {

        $systems = $this->request->getJsonRawBody(true);
        $systems = $systems['systems'];
        /**
         * 查询已有的systems
         */
        $res = ["roleId" => $roleId];
        $params = [
            'code' => 10065,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if (isset($result['content']['roleSystemDOs'])) {
                $result = $result['content']['roleSystemDOs'];
                foreach($result as $key => $item) {
                    //不在新的系统里 删除
                    if (!in_array($item['systemId'],$systems)) {
                        $this->subData->DeleteRoleSystemById($item['id']);
                    } else {
                        $key1 = array_search($item['systemId'],$systems);
                        unset($systems[$key1]);
                    }
                }
                foreach ($systems as $item) {
                    //新增
                    $this->subData->AddRoleSystem($roleId,$item);
                }
                return $this->toSuccess(true);
            } else {
                foreach ($systems as $item) {
                    //新增
                    $this->subData->AddRoleSystem($roleId,$item);
                }
                return $this->toSuccess(true);
            }


        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }


    private function getSubList($systemStatus = 1,$systemType = null)
    {
        $parameter = [];
        if (isset($systemStatus)){
            $parameter['systemStatus'] = $systemStatus;
        }
        if (isset($systemType)){
            $parameter['systemType'] = $systemType;
        }
        //调用微服务接口获取数据
        $params = ["code" => "10043","parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['content']['systems']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }

}
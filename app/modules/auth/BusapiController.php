<?php
/**
 * Created by PhpStorm.
 * User: zhengchao
 */
namespace app\modules\auth;
use app\modules\BaseController;


/**
 * Class BusApiController
 * @package app\modules\auth
 * 业务层api增删改查
 */
class BusapiController extends BaseController
{

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 查新列表和分页
     */
    public function ListAction()
    {

        /**
         * 如果传过来的参数中有type则执行不分页查找
         */
        $this->logger->info("API列表");
        $type = $this->request->getQuery("type", "string", "page");
        $apiStatus = $this->request->getQuery("apiStatus", "int", 1);
        $moduleId = $this->request->getQuery("moduleId", "int", null);
        if ($type != "page") {
            return $this->getBusList($apiStatus,$moduleId);
        }
        /**
         * 一般情况下进行分页查询
         */
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
        ];
        if (!empty($this->request->getQuery('apiName', "string", null))) {
            $parameter['apiName'] = $this->request->getQuery('apiName', "string");
        }
        if (!empty($this->request->getQuery('apiCode', "string", null))) {
            $parameter['apiCode'] = $this->request->getQuery('apiCode', "string");
        }
        if (!empty($this->request->getQuery('apiStatus', "int", null))) {
            $parameter['apiStatus'] = (int)$this->request->getQuery('apiStatus', "int");
        }
        //调用微服务接口获取数据
        $params = ["code" => "10011", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        //结果处理返回
        if ($result['statusCode'] != 200) {
            return $this->toError($result['statusCode'], $result['msg']);
            $this->logger->error("获取数据=>CODE:10011数据失败.".$result['statusCode'].':'.$result['msg']);
        }
        if (!isset($result['content']['roles'])) {
            return $this->toError(500, "数据返回出错!");
            $this->logger->error("获取数据=>CODE:10011数据格式返回错误");
        }
        if (!isset($result['content']['pageInfo']['total']) || !isset($result['content']['roles'])) {
            return $this->toError(500, "数据返回出错!");
            $this->logger->error("获取数据=>CODE:10011数据格式返回错误");
        }
        $meta['total'] = $result['content']['pageInfo']['total'];
        $meta['pageNum'] = $pageNum;
        $meta['pageSize'] = $pageSize;
        $result = $result['content']['roles'];
        foreach ($result as $key => $item) {
            $result[$key]['createAt'] = date("Y-m-d H:i:s", $item['createAt']);
            $result[$key]['updateAt'] = date("Y-m-d H:i:s", $item['updateAt']);
        }
        return $this->toSuccess($result, $meta);

    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 查询单个
     */
    public function OneAction($id)
    {
        //根据id查询数据
        $res = ["id" => $id];
        $json = ['code' => 10011,
            'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user, $json, "post");
        if ($result['statusCode'] == '200') {
            if (count($result['content']['roles']) == 1) {
                $result = $result['content']['roles'][0];
                //数据转换
                $result['apiStatus'] == 1 ? $result['apiStatus'] = true : $result['apiStatus'] = false;
                $result['isCommon'] == 1 ? $result['isCommon'] = true : $result['isCommon'] = false;
                $result['needLogin'] == 1 ? $result['needLogin'] = true : $result['needLogin'] = false;
                $result['needPermission'] == 1 ? $result['needPermission'] = true : $result['needPermission'] = false;

                return $this->toSuccess($result);
            }
            return $this->toError(500, "数据不存在！");
        } else {
            $this->logger->error("获取数据=>CODE:10011数据失败.".$result['statusCode'].':'.$result['msg']);
            return $this->toError($result['statusCode'], $result['msg']);
        }
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 创建数据
     */
    public function CreateAction()
    {
        $res = [];
        $req = $this->request->getJsonRawBody(true);
        if (!isset($req['apiName'])) return $this->toError(500, "API名称必填");
        $res['apiName'] = $req['apiName'];
        if (!isset($req['apiCode'])) return $this->toError(500, "API代码必填");
        $res['apiCode'] = $req['apiCode'];
        if (!isset($req['apiAddr'])) return $this->toError(500, "API地址必填");
        $res['apiAddr'] = $req['apiAddr'];
        if (!isset($req['apiStatus'])) return $this->toError(500, "API状态必填");
        $req['apiStatus'] == 1 ? $res['apiStatus'] = 1 : $res['apiStatus'] = 2;
        if (isset($req['isCommon']) && $req['isCommon'] == 1) {
            $res['isCommon'] = 1;
        } else {
            $res['isCommon'] = 2;
        }
        if (isset($req['needLogin']) && $req['needLogin'] == 1){
            $res['needLogin'] = 1;
        } else {
            $res['needLogin'] = 2;
        }
        if (isset($req['needPermission']) && $req['needPermission'] == 1) {
            $res['needPermission'] = 1;
        } else {
            $res['needPermission'] = 2;
        }
        if (!isset($req['moduleId'])) return $this->toError(500, "模块ID必填");
        $res['moduleId'] = (int)$req['moduleId'];
        if (!isset($req['showPriority'])) return $this->toError(500, "显示优先级（0-100)必填");
        $res['showPriority'] = (int)$req['showPriority'];
        if (!isset($req['apiFunction'])) return $this->toError(500, "API功能（1插入 2修改 3删除 4查询)必填");
        $res['apiFunction'] = (int)$req['apiFunction'];
        $params = ["code" => "10009", "parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'], $result['msg']);
        }
    }


    //修改
    public function UpdateAction($id)
    {
        $params = [];
        //根据id查询数据
        $search_params = ["id" => $id];
        $json = [
            'code' => 10011,
            'parameter' => $search_params
        ];
        $result = $this->curl->httpRequest($this->Zuul->user, $json, "post");
        unset($search_params);
        //判断数据返回正确性
        if (isset($result['statusCode']) && $result['statusCode'] != '200')
            return $this->toError($result['statusCode'], $result['msg']);
        //判断所需呀的数据是否存在
        if (!isset($result['content']['roles'][0]))
            return $this->toError($result['statusCode'], $result['msg']);
        $params['id'] = $id;
        unset($result);
        $res = $this->request->getJsonRawBody();
        if (isset($res->apiName)) {
            $params['apiName'] = $res->apiName;
        }
        if (isset($res->apiCode)) {
            $params['apiCode'] = $res->apiCode;
        }
        if (isset($res->apiAddr))
            $params['apiAddr'] = $res->apiAddr;
        if (isset($res->apiStatus))
            $res->apiStatus == 1 ? $params['apiStatus'] = 1 : $params['apiStatus'] = 2;
        if (isset($this->content->isCommon))
            $res->isCommon == 1 ? $params['isCommon'] = 1 : $params['isCommon'] = 2;
        if (isset($res->needLogin))
            $res->needLogin == 1 ?  $params['needLogin'] = 1 :  $params['needLogin'] = 2;
        if (isset($res->needPermission))
            $res->needPermission == 1 ? $params['needPermission'] = 1 : $params['needPermission'] = 2;
        if (isset($res->showPriority))
            $params['showPriority'] = (int)$res->showPriority;
        if (isset($res->apiFunction))
            $params['apiFunction'] = (int)$res->apiFunction;
        if (isset($res->moduleId))
            $params['moduleId'] = (int)$res->moduleId;
        $params['updateAt'] = time();
        $put_params = ["code" => "10010", "parameter" => $params];
        $result = $this->curl->httpRequest($this->Zuul->user, $put_params, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($params);
        } else {
            return $this->toError($result['statusCode'], $result['msg']);
        }

    }


    //删除
    public function DeleteAction($id)
    {
        $res = ["id" => $id];
        $json = [
            'code' => 10022,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user, $json, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'], $result['msg']);
        }
    }

    /**
     *
     * @param int $apiStatus
     * @param int $moduledId
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 获取api列表
     *
     * TODO： $moduleId 是什么
     */
    private function getBusList($apiStatus, $moduleId)
    {
        $busList = [];
        $parameter = [];
        if (!empty($apiStatus))
            $parameter['apiStatus'] = $apiStatus;
        if (!empty($moduleId))
            $parameter['moduleId'] = $moduleId;
        $params = ["code" => "10011", "parameter" =>$parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == '200') {
            $result = $result['content'];
            if (is_array($result) && count($result) > 1) {
                foreach ($result as $item) {
                    $api = [];
                    $api["id"] = $item["id"];
                    $api["apiCode"] = $item["apiCode"];
                    $api["apiName"] = $item["apiName"];
                    $busList[] = $api;
                }
            } else {
                $busList = (array)$result;
            }
            return $this->toSuccess($busList);
        }
        return $this->toError($result['statusCode'], $result['msg']);
    }


    //返回未使用的Order
    public function OrderAction()
    {
        $order = [];
        $big = 100;
        $params = ["code" => "10011","parameter" => (Object)$order];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            $result = $result['content']['roles'];
            if (count($result) > 1) {
                foreach ($result as $item) {
                    if($big < $item['showPriority'])
                        $big = $item['showPriority'];
                    $list[] = $item['showPriority'];
                }


                $i = 1;
                do {
                    if (!in_array($i, $list))
                        $order[] = $i;
                    $i++;
                } while ($i <= 100);
            } else {
                $i = 1;
                do {
                    $order[] = $i;
                    $i++;
                } while ($i <= 100);
            }
            return $this->toSuccess($order);

        }
        return $this->toError(500,"获取数据失败");
    }
}

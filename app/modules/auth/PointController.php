<?php
namespace app\modules\auth;


use app\common\errors\AppException;
use app\common\errors\DataException;

use app\modules\BaseController;

/**
 * Class PointController
 * @package app\modules\auth
 * 功能点api接口
 */
class PointController extends BaseController
{

    public function listAction($funcId)
    {

        $parameter['menuId'] = (int)$funcId;
        //$res = ["id" => $parameter['menuId']];
        $res = ['code' => 10021,'parameter' =>["id" => $parameter['menuId']]];
        $result = $this->curl->httpRequest($this->Zuul->user,$res,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if(isset($result['content']['menus'][0]["menuLevel"]) && $result['content']['menus'][0]["menuLevel"] != 3)
                return $this->toError(500,"menu为空或者menulevel不为3");
        }

        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = ["pageNum" => (int)$pageNum,"pageSize" => (int)$pageSize];
        $parameter['menuId'] = (int)$funcId;
        /**
         * 参数绑定
         */
        $funcCode = $this->request->getQuery('funcCode');
        $funcName = $this->request->getQuery('funcName');
        if(!empty($funcCode))
            $parameter['funcCode'] = $funcCode;
        if(!empty($funcName))
            $parameter['funcName'] = $funcName;

        //调用微服务接口获取数据
        $params = ["code" => "10032","parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            $meta['total'] = $result['content']['pageInfo']['total'];
            $meta['pageNum'] = $pageNum;
            $meta['pageSize'] = $pageSize;
            return $this->toSuccess($result['content']['menuFunctions'],$meta);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    public function OneAction($funcId,$id)
    {
        //根据id查询数据
        $res = ["id" => (int)$id,"menuId" => (int)$funcId];
        $json = ['code' => 10032,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if ($result['statusCode'] == '200') {
            if(count($result['content']['roles']) == 1)
                return $this->toSuccess($result['content']['menuFunctions'][0]);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 新增功能点
     */
    public function CreateAction($funcId)
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'menuId' => '菜单ID必填',
            'funcCode' => '功能点CODE必填',
            'funcName' => '功能点名称必填',
            'apiId' => 'apiId必填',
            'status' => 0,
            'create_at' => 0,
        ];
        $res = $this->getArrPars($fields, $request);
        if (!$res){
            return $this->toError(500,"参数传递出错");
        }
        $res['status'] = 1;
        $res["createAt"] = time();
        $params = ["code" => "10029","parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    public function UpdateAction($funcId,$id)
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数提取
        $fields = [
            'menuId' => 0,
            'funcCode' => 0,
            'funcName' => 0,
            'apiId' => 0,
            'status' => 0,
            'create_at' => 0,
            'update_at' => 0
        ];
        $point = $this->getArrPars($fields, $request);
        if (!$point){
            return $this->toError(500,"参数传递出错");
        }
        $point['updateAt'] = time();
        $point['id'] = $id;
        $params = [
            'code' => '10031',
            'parameter' => $point
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($point );
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 删除功能点
     */
    public function DeleteAction($funcId,$id)
    {
        $res = ["id" => $id];
        $json = ['code'=> 10030,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }
}
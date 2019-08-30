<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: log.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\log;

use app\modules\BaseController;

class LogController extends BaseController
{

    private $BusinessCode = 10011;
    private $PlatformCode = 10012;
    private $SystemCode = 10013;

    //
    public function BusinessAction()
    {
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $level = $this->request->getQuery('level', "string", null);  //等级
        $bizModuleCode = $this->request->getQuery('bizModuleCode', "string", null);  //业务模块编号
        $timestampStart = $this->request->getQuery('timestampStart', "int", null);  //时间戳起始
        $timestampEnd = $this->request->getQuery('timestampEnd', "int", null);  //时间戳结束
        $objectType = $this->request->getQuery('objectType', "string", null);  //操作对象类型
        $operObject = $this->request->getQuery('operObject', "string", null);  //操作对象
        $operater = $this->request->getQuery('operater', "string", null);  //操作人
        $requestId = $this->request->getQuery('requestId', "string", null);  //请求id


        $parameter = ["pageNum" => (int)$pageNum,"pageSize" => (int)$pageSize];
        if (!empty($level)) {
            $parameter['level'] = $level;
        }
        if (!empty($bizModuleCode)) {
            $parameter['bizModuleCode'] = $bizModuleCode;
        }
        if (!empty($timestampStart)) {
            $parameter['timestampStart'] = $timestampStart;
        }
        if (!empty($timestampEnd)) {
            $parameter['timestampEnd'] = $timestampEnd;
        }
        if (!empty($objectType)) {
            $parameter['objectType'] = $objectType;
        }
        if (!empty($operObject)) {
            $parameter['operObject'] = $operObject;
        }
        if (!empty($operater)) {
            $parameter['operater'] = $operater;
        }
        if (!empty($requestId)) {
            $parameter['requestId'] = $requestId;
        }


        $result = $this->userData->common($parameter,$this->Zuul->log,$this->BusinessCode);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($pageInfo == '') {
            $pageInfo = ['total' => 0,'pageNum' =>(int)$pageNum,'pageSize' =>(int)$pageSize];
        }
        if ($result == null)
            return $this->toSuccess([],$pageInfo);
        foreach ($result as $key =>  $item) {
            $result[$key]['timestamp'] = !empty($result[$key]['timestamp']) ? date('Y-m-d H:i:s',$result[$key]['timestamp']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);

    }


    public function PlatformAction()
    {
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $level = $this->request->getQuery('level', "string", null);  //等级
        $bizModuleCode = $this->request->getQuery('bizModuleCode', "string", null);  //业务模块编号
        $timestampStart = $this->request->getQuery('timestampStart', "int", null);  //时间戳起始
        $timestampEnd = $this->request->getQuery('timestampEnd', "int", null);  //时间戳结束

        $parameter = ["pageNum" => (int)$pageNum,"pageSize" => (int)$pageSize];
        if (!empty($level)) {
            $parameter['level'] = $level;
        }
        if (!empty($bizModuleCode)) {
            $parameter['bizModuleCode'] = $bizModuleCode;
        }
        if (!empty($timestampStart)) {
            $parameter['timestampStart'] = $timestampStart;
        }
        if (!empty($timestampEnd)) {
            $parameter['timestampEnd'] = $timestampEnd;
        }



        $result = $this->userData->common($parameter,$this->Zuul->log,$this->PlatformCode);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($pageInfo == '') {
            $pageInfo = ['total' => 0,'pageNum' =>(int)$pageNum,'pageSize' =>(int)$pageSize];
        }
        if ($result == null)
            return $this->toSuccess([],$pageInfo);
        foreach ($result as $key =>  $item) {
            $result[$key]['timestamp'] = !empty($result[$key]['timestamp']) ? date('Y-m-d H:i:s',$result[$key]['timestamp']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);

    }


    public function SystemAction()
    {
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $level = $this->request->getQuery('level', "string", null);  //等级
        $timestampStart = $this->request->getQuery('timestampStart', "int", null);  //时间戳起始
        $timestampEnd = $this->request->getQuery('timestampEnd', "int", null);  //时间戳结束

        $parameter = ["pageNum" => (int)$pageNum,"pageSize" => (int)$pageSize];
        if (!empty($level)) {
            $parameter['level'] = $level;
        }
        if (!empty($timestampStart)) {
            $parameter['timestampStart'] = $timestampStart;
        }
        if (!empty($timestampEnd)) {
            $parameter['timestampEnd'] = $timestampEnd;
        }


        $result = $this->userData->common($parameter,$this->Zuul->log,$this->SystemCode);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($pageInfo == '') {
            $pageInfo = ['total' => 0,'pageNum' =>(int)$pageNum,'pageSize' => (int)$pageSize];
        }
        if ($result == null)
            return $this->toSuccess([],$pageInfo);
        foreach ($result as $key =>  $item) {
            $result[$key]['timestamp'] = !empty($result[$key]['timestamp']) ? date('Y-m-d H:i:s',$result[$key]['timestamp']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);

    }
}
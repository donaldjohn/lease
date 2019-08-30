<?php
namespace app\modules\rent;


use app\modules\BaseController;

//服务项目模块
class ServiceitemController extends BaseController
{
    /**
     * 新增服务项目
     */
    public function CreateAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'serviceItemSn' => '服务项目编号不能为空',
            'serviceItemName' => '服务项目名称不能为空',
            'serviceItemType' => '服务项目类型不能为空',
            'lifecycle' => [
                'def' => 999,
            ],
            'status' => [
                'def' => 1,
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        if (false === $parameter){
            return;
        }
        // 调用微服务接口新增服务项目
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10000",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    /**
     * 服务项目列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            'serviceItemSn' => 0,
            'serviceItemName' => 0,
            'status' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
            'operatorInsId' => [
                'def' => null,
            ]
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        switch ($this->authed->userType){
            case 9:
                $parameter['operatorInsId'] = $this->authed->insId;
                break;
        }

        // 调用微服务接口新增服务项目
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10004",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['serviceItemList'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 处理时间戳
        foreach ($list as $k => $v){
            $list[$k]['createTime'] = date('Y-m-d H:i:s', $v['createTime']);
            $list[$k]['updateTime'] = date('Y-m-d H:i:s', $v['updateTime']);
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }


    /**
     * 服务项目详情
     */
    public function OneAction($id)
    {
        // 调用微服务接口新增服务项目
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10001,
            'parameter' => [
                'serviceItemId' => $id,
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $info = $result['content']['serviceItem'];
        // 处理时间
        $info['createTime'] = date('Y-m-d H:i:s', $info['createTime']);
        $info['updateTime'] = date('Y-m-d H:i:s', $info['updateTime']);
        // 成功返回
        return $this->toSuccess($info);
    }


    /**
     * 编辑服务项目
     */
    public function UpdateAction($id)
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        // 调用微服务接口删除服务项目
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10002,
            'parameter' => [
                'serviceItemId' => $id,
                'serviceItemName' => $request['serviceItemName'],
            ],
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    /**
     * 批量更新服务项目状态
     */
    public function UpstatusAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        // 定义接收字段
        $fields = [
            'serviceItemIds' => '请选择服务项目',
            'status' => '请选择更新状态',
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        if (false === $parameter){
            return;
        }
        // 处理serviceItemIds格式
        $parameter['serviceItemIds'] = implode(',', $parameter['serviceItemIds']);
        // 调用微服务接口删除服务项目
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10003,
            'parameter' => $parameter,
        ],"post");
        switch ($result['statusCode']){
            case '200':
                return $this->toSuccess();
                break;
            case '10001':
                return $this->toError(500, '服务项目有套餐在用，不可禁用');
                break;
            default:
                return $this->toError($result['statusCode'],$result['msg']);
        }
    }

    /**
     * 删除服务项目
     */
    public function DelAction($id)
    {
        // 调用微服务接口删除服务项目
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10005,
            'parameter' => [
                'serviceItemId' => $id,
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }
}

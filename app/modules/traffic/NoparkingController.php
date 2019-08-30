<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/4
 * Time: 15:34
 * 禁停区
 */
namespace app\modules\traffic;

use app\modules\BaseController;

class NoparkingController extends BaseController
{
    /**
     * 列表
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function ListAction()
    {
        $searchItem  = $this->request->getQuery('searchItem','string',null);
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $pageSize = $this->request->getQuery('pageSize','int',20);
        $params = [
            "pageSize" => (int)$pageSize,
            "pageNum" => (int)$pageNum,
            "searchItem" => $searchItem,
            "userId" => $this->authed->userId
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '10082',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['list'], $result['content']['pageInfo']);
    }

    /**
     * 新增禁停区
     */
    public function CreateAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'provinceId', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'cityId', 'type' => 'string', 'parameter' => ['default' => true]],
            ['key' => 'areaId', 'type' => 'string', 'parameter' => ['default' => true,]],
            ['key' => 'name', 'type' => 'string', 'parameter' => ['default' => true,]],
            ['key' => 'reason', 'type' => 'string', 'parameter' => ['default' => false,]],
            ['key' => 'latlng', 'type' => 'string', 'parameter' => ['default' => true,]],
        ];

        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $params['latlng'] = json_encode($params['latlng']);
        $data = [
            'parameter' => $params,
            'code' => '10080',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess($result['content']);
    }

    /**
     * 编辑
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function UpdateAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true, ]],
            ['key' => 'name', 'type' => 'string', 'parameter' => ['default' => false,]],
            ['key' => 'reason', 'type' => 'string', 'parameter' => ['default' => false,]],
            ['key' => 'latlng', 'type' => 'string', 'parameter' => ['default' => false,]],
        ];

        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        if (isset($params['latlng'])) {
            $params['latlng'] = json_encode($params['latlng']);
        }
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '10081',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess();
    }

    /**
     * 删除数据
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function DelAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true, ]],
        ];

        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data = [
            'parameter' => $params,
            'code' => '10084',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }

    /**
     * 启用禁用
     */
    public function StatusAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'status', 'type' => 'number', 'parameter' => ['default' => true, 'in' => [1, 2]]],
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data = [
            'parameter' => $params,
            'code' => '10083',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }
    /**
     *查询区域下面的禁停区
     */
    public function AreaAction()
    {
        $params['areaId'] = $this->request->get("areaId");
        $params['id'] = $this->request->get("id");
        $fields = [
            ['key' => 'areaId', 'type' => 'number', 'parameter' => ['default' => true,]],
//            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '10085',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['otherNoParkings']);
    }

    /**
     * 设置停车区
     */
    public function ParkingCreateAction()
    {
        $params = $this->request->getJsonRawBody(true);
        if (!isset($params['noparkingId']) || $params['noparkingId'] < 0) {
            return $this->toError(500, '未选择禁停区');
        }
        if (!isset($params['parkingAreaList'])) {
            return $this->toError(500, '未设置');
        }
        // 20180925 赵银娣不在，代为修改
        if (isset($params['parkingAreaList'])) {
            foreach ($params['parkingAreaList'] as $k => $parkingArea) {
                if (isset($parkingArea['latlng'])){
                    $params['parkingAreaList'][$k]['latlng'] = json_encode($parkingArea['latlng']);
                }
            }
        }
        $data = [
            'parameter' => $params,
            'code' => '10086',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result);
    }

    /**
     *禁停区列表
     */
    public function ParkingListAction()
    {
        $id  = $this->request->getQuery('id','string',null);

        if (!$id) {
            return $this->toError(500, '未填写ID');
        }
        $data = [
            'parameter' => ["id" => $id],
            'code' => '10087',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/4
 * Time: 15:35
 * 禁行区
 */
namespace app\modules\traffic;

use app\modules\BaseController;

class NoentryController extends BaseController
{
    public function listAction()
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
            'code' => '10093',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        foreach ($result['content']['list'] as $key => &$val) {
            if ($val['noEntryWay'] == 0) {
                $val['time'] = date('Y-m-d H:i:s',$val['noEntryStartTime']) .'-'.date('Y-m-d H:i:s',$val['noEntryEndTime']);
            } else {
                $start = (number_format($val['noEntryStartTime']/3600)) . '时'.($val['noEntryStartTime']%60) . '分';
                $end = (number_format($val['noEntryEndTime']/3600)) . '时'.($val['noEntryEndTime']%60) . '分';
                $val['time'] = $start .'-'. $end;
            }
        }
        return $this->toSuccess($result['content']['list'], $result['content']['pageInfo']);
    }
    /**
     * 新增
     */
    public function createAction()
    {
        $pram = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'provinceId', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'cityId', 'type' => 'string', 'parameter' => ['default' => true]],
            ['key' => 'areaId', 'type' => 'string', 'parameter' => ['default' => true,]],
            ['key' => 'noEntryWay', 'type' => 'string', 'parameter' => ['default' => true, 'in' => [0, 1,2,3]]],
//            ['key' => 'days', 'type' => 'array', 'parameter' => ['default' => false,]],
            ['key' => 'noEntryStartTime', 'type' => 'number', 'parameter' => ['default' => true,]],
            ['key' => 'noEntryEndTime', 'type' => 'number', 'parameter' => ['default' => true,]],
            ['key' => 'reason', 'type' => 'string', 'parameter' => ['default' => true,]],
        ];

        $validate = $this->validate;
        $result = $validate->myValidation($fields,$pram);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        if (isset($pram['latlng'])) {
            $pram['latlng'] = json_encode($pram['latlng']);
        } else {
            return $this->toError(500, '未选择区域');
        }
        $data = [
            'parameter' => $pram,
            'code' => '10088',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess($result['content']);
    }

    /**
     * 编辑
     */
    public function updateAction()
    {
        $pram = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true, ]],
            ['key' => 'noEntryWay', 'type' => 'number', 'parameter' => ['default' => false, 'in' => [0,1,2,3]]],
            ['key' => 'noEntryStartTime', 'type' => 'number', 'parameter' => ['default' => false,]],
            ['key' => 'noEntryEndTime', 'type' => 'number', 'parameter' => ['default' => false,]],
            ['key' => 'reason', 'type' => 'string', 'parameter' => ['default' => false,]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$pram);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        if (isset($pram['latlng'])) {
            $pram['latlng'] = json_encode($pram['latlng']);
        }
        $data = [
            'parameter' => $pram,
            'code' => '10089',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess();
    }

    /**
     * 删除
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function delAction()
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
            'code' => '10092',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess();
    }

    /**
     * 详细信息
     */
    public function DetailAction()
    {
        $id  = $this->request->getQuery('id','string',null);

        if (!$id) {
            return $this->toError(500, '未填写ID');
        }
        $data = [
            'parameter' => ["id" => $id],
            'code' => '10091',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']);
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
            'code' => '10090',
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
        $params['noEntryStartTime'] = $this->request->get("noEntryStartTime");
        $params['noEntryEndTime'] = $this->request->get("noEntryEndTime");
        $params['noEntryWay'] = $this->request->get("noEntryWay");
        $params['days'] = $this->request->get("days");
        $fields = [
            ['key' => 'areaId', 'type' => 'number', 'parameter' => ['default' => true,]],
            ['key' => 'noEntryWay', 'type' => 'number', 'parameter' => ['default' => false, 'in' => [0,1,2,3]]],
            ['key' => 'noEntryStartTime', 'type' => 'number', 'parameter' => ['default' => true,]],
            ['key' => 'noEntryEndTime', 'type' => 'number', 'parameter' => ['default' => true,]],
            ['key' => 'days', 'type' => 'string', 'parameter' => ['default' => false,]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data = [
            'parameter' => $params,
            'code' => '10094',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']);
    }
    /**
     *查询当前用户下面的区域
     */
    public function UserAreaAction()
    {
        $data = [
            'parameter' => ['userId' => $this->authed->userId],
            'code' => '10095',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']);
    }

}
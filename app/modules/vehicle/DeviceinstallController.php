<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27 0027
 * Time: 16:13
 */
namespace app\modules\vehicle;

use app\modules\BaseController;
use phpDocumentor\Reflection\Types\Object_;
use app\services\auth\AuthService;

class DeviceinstallController extends BaseController {

    /**
     * 获取列表信息
     */
    public function IndexAction()
    {

        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $json['taskCode'] = $this->request->getQuery('taskCode','string','');
        $json['cityId'] = $this->request->getQuery('cityId','int',0);
        $json['insId'] = $this->request->getQuery('insId','int',0);

        $json = array_filter($json);
        //TODO
        $data = [
            'parameter' => $json,
            'code' => '11054',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : [] ,
            isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : []);
    }

    /**
     * 详细信息
     */
    public function DetailAction()
    {
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $json['taskId'] = (int)$this->request->getQuery('taskId','int',0);
        $json['multiCode'] = $this->request->getQuery('multiCode','string','');
        if (!isset($json['taskId']) || $json['taskId'] == 0) {
            return $this->toError(500, '请选择查看的批次');
        }
        $json = array_filter($json);
        $data = [
            'parameter' => $json,
            'code' => 11056,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : [] ,
            isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : []);
    }
    /**
     * 新增后装预约
     */
    public function CreateAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'cityId', 'type' => 'number', 'parameter' => ['default' => true, ]],
            ['key' => 'provinceId', 'type' => 'number', 'parameter' => ['default' => true, ]],
            ['key' => 'insId', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'taskType', 'type' => 'number', 'parameter' => ['default' => true,'in' => [1, 2]]],
            ['key' => 'orderCount', 'type' => 'number', 'parameter' => ['default' => true, 'min' => 0,'max' => 1000000]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $params['taskSource'] = 1;
        $params['createUserId'] = $this->authed->userId;

        $data = [
            'parameter' => $params,
            'code' => '11052',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }
    /**
     * 编辑后装预约
     */
    public function editAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'cityId', 'type' => 'number', 'parameter' => ['default' => false, ]],
            ['key' => 'provinceId', 'type' => 'number', 'parameter' => ['default' => false, ]],
            ['key' => 'insId', 'type' => 'number', 'parameter' => ['default' => false]],
            ['key' => 'taskType', 'type' => 'number', 'parameter' => ['default' => false,'in' => [1, 2]]],
            ['key' => 'orderCount', 'type' => 'number', 'parameter' => ['default' => false, 'min' => 0,'max' => 1000000]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }

        $data = [
            'parameter' => $params,
            'code' => '11052',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }

    /**
     * 更新后装预约（完成、撤销、删除、完成）
     */
    public function UpdateAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'operation', 'type' => 'number', 'parameter' => ['default' => true, 'in' => [1,2,3,4]]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500,  $message[0]);
        }
        //TODO
        $data = [
            'parameter' => $params,
            'code' => '11053',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }
    /**
     * 删除导入的车辆数据
     */
    public function DelAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500,  $message[0]);
        }
        //TODO
        $data = [
            'parameter' => $params,
            'code' => '11064',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }

    /**
     * 获取开通邮管局的城市
     */
    public function CityAction()
    {
        $data = [
            'parameter' => new Object_(),
            'code' => '11050',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }
    /**
     * 获取开通邮管局城市的快递公司
     */
    public function CompanyAction()
    {
        $cityId = $this->request->getQuery('cityId','int',0);
        $data = [
            'parameter' => ["cityId"=> $cityId],
            'code' => '11051',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }

    /**
     * 导入车辆数据
     */
    public function ImportAction()
    {
        $params = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'fileName', 'type' => 'string', 'parameter' => ['default' => true]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields, $params);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data = ['parameter' => $params, 'code' => '11055'];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : []);
    }

}
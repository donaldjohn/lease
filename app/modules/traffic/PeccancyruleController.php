<?php
namespace app\modules\traffic;

use app\models\service\PeccancyRecord;
use app\models\service\PeccancyType;
use app\models\users\Association;
use app\models\users\Institution;
use app\models\users\Postoffice;
use app\modules\BaseController;
use app\services\data\RegionData;
use app\services\data\UserData;

// 违章处理规则
class PeccancyruleController extends BaseController
{
    // 违章处理规则列表
    public function ListAction()
    {
        $area = (new UserData())->getAreaByInsId($this->authed->insId);
        // 查询车辆违规列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15015',
            'parameter' => [
                'cityId' => $area['cityId'] ?? 0,
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:' . $result['msg']);
        }
        $data = $result['content']['data'];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 新增违章处理规则
    public function AddAction()
    {
        $area = (new UserData())->getAreaByInsId($this->authed->insId);
        $request = $this->request->getJsonRawBody(true);
        $request['insId'] = $this->authed->insId;
        $request['cityId'] = $area['cityId'] ?? 0;
        // 新增违章处理规则
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15016',
            'parameter' => $request
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:' . ($result['content']['data']['cause'] ?? $result['msg']));
        }
        $data = $result['content']['data'] ?? [];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 编辑违章处理规则
    public function EditAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        $request['id'] = $id;
        // 更新违章处理规则
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15016',
            'parameter' => $request
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:' . ($result['content']['data']['cause'] ?? $result['msg']));
        }
        $data = $result['content']['data'] ?? [];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 查询违章类型
    public function SelectPeccancyTypeAction()
    {
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15017',
            'parameter' => $_GET
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 获取违章锁车和短信发送按钮状态
    public function PeccancyParamStatusAction()
    {
        $area = (new UserData())->getAreaByInsId($this->authed->insId);
        $_GET['cityId'] = $area['cityId'] ?? 0;
        // 获取违章锁车和短信发送按钮状态
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15018',
            'parameter' => $_GET
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:' . $result['msg']);
        }
        $data = $result['content']['data'] ?? [];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 新增或更新违章按钮参数
    public function EditPeccancyParamAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $area = (new UserData())->getAreaByInsId($this->authed->insId);
        $request['cityId'] = $area['cityId'] ?? 0;
        $request['insId'] = $this->authed->insId;
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15019',
            'parameter' => $request
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:' . $result['msg']);
        }
        $data = $result['content']['data'] ?? [];
        // 成功返回
        return $this->toSuccess($data);

    }
}

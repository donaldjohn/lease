<?php
namespace app\modules\intelligent;

use app\modules\BaseController;

class PostofficevehicleController extends BaseController
{
    // 邮管车辆数据列表
    public function ListAction()
    {
        $tips = [
            '1031' => '快递公司不存在',
        ];
        return $this->PenetrateTransfer(10024, $_GET, $tips);
    }

    // 编辑邮管车辆数据
    public function EditAction($id)
    {
        $parameter = $this->request->getJsonRawBody(true);
        $parameter['vehicleId'] = $id;
        $tips = [
            '1033' => '车辆状态是"已报废"，无法编辑',
            '1034' => '车辆智能设备号和设备型号重复验证失败',
            '1031' => '快递公司不存在',
            '1035' => '车牌号验证失败',
            '1036' => '当前二维码不存在',
            '1037' => '当前二维码已绑定车辆',
            '1038' => '输入的智能设备号与设备型号不匹配',
            '1040' => '车架号和车型重复',
        ];
        return $this->PenetrateTransfer(10026, $parameter, $tips);
    }

    // 删除邮管车辆数据
    public function DelAction($id)
    {
        $parameter['vehicleId'] = $id;
        $tips = [
            '1032' => '车辆状态不是未使用，删除失败',
        ];
        return $this->PenetrateTransfer(10025, $parameter, $tips);
    }

    /**
     * 接口透传
     * @param $code 接口代码
     * @param null $parameter 接口参数【默认取JsonBody】
     * @param null|string|array $serviceName 服务名称 | tips
     * @param bool $DoNotHandleResult 是否直接处理结果
     * @param array $tips
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    private function PenetrateTransfer($code, $parameter=null, $serviceName=null, $DoNotHandleResult=false, $tips=[])
    {
        if (is_array($serviceName)){
            $tips = $serviceName;
            $serviceName = null;
        }
        $serviceName = $serviceName ?? 'vehicle';
        $parameter = $parameter ?? $this->request->getJsonRawBody(true);
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->$serviceName,[
            'code' => $code,
            'parameter' => $parameter
        ],"post");
        if ($DoNotHandleResult){
            return $result;
        }
        //结果处理返回
        if ($result['statusCode'] != '200') {
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        $data = $result['content']['data'] ?? $result['content']['list'] ?? [];
        $meta = $result['content']['pageInfo'] ?? [];
        return $this->toSuccess($data, $meta);
    }
}
<?php
namespace app\modules\cabinet;

use app\modules\BaseController;
use Phalcon\Logger;

/**
 * Class AdminController
 * 换电柜管理后台API类
 * @Author Lishiqin
 * @package app\modules\microprograms
 */
class DataController extends BaseController {

    /**
     * 接收换电柜上电状态
     * @param string token         主板编号（必填）
     * @param string boardversion  主板版本
     * @return mixed
     */
    public function BoardAction()
    {
        $request = $this->request->getRawBody();

        // 请求数据验证
        $request = json_decode($request, true);
        $token   = isset($request['boardID']) ? $request['boardID'] : '';

        $this->logger->error("主板编号：".(string)$token);

        if (empty($token)) {
            return $this->toError(500, '主板编号无效');
        }

        $params = [
            "code" => 30000,
            "parameter" => [
                "cabinetId"    => $token,
            ]
        ];

        // 请求微服务接口提交换电柜上电信息
        $result = $this->curl->httpRequest($this->Zuul->charging ,$params, "post", false);

        // 判断结果返回
        if ($result['statusCode'] == 200) {
            return $this->toSuccess();
        } else {
            return $this->toError(500, '数据提交失败');
        }
    }

    /**
     * 接收换电柜柜组心跳数据包信息
     * @param string token         主板编号（必填）
     * @param array  roomstatus    柜组状态信息6个（必填）
     * @return mixed
     */
    public function RoomAction()
    {
        $request = $this->request->getRawBody();

        // 请求数据验证
        $request = json_decode($request, true);


        // 请求数据验证
        $token   = isset($request['token']) ? $request['token'] : '';
        $roomstatus  = isset($request['parameter']) ? $request['parameter']['roomstatus'] : [];

        try {

            if (empty($token)) {
                return $this->toError(500, '心跳包主板编号不能为空');
            }

            if (count($roomstatus) <= 0) {
                return $this->toError(500, '柜组状态不能为空');
            }

            // 硬件请求日志
            $data = []; // 定义返回参数

            // 遍历心跳包数据，组装返回结果
            foreach ($roomstatus as $key => $value) {
                $data[$key]['roomNum']         = $value['roomnum'];
                $data[$key]['doorStatus']      = $value['doorstatus'];
                $data[$key]['batteryStatus']   = $value['betterystatus'];
                $data[$key]['batteryId']       = $value['betteryid'];
                $data[$key]['batteryEnergy']   = $value['betteryenergy'];
            }

            $params = [
                "code" => 20003,
                "parameter" => [
                    "cabinetId" => $token,
                    "data"      => $data,
                    "type"      => 1
                ]
            ];

            // 请求微服务接口提交换电柜组状态
            $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post", false);

            // 判断结果返回
            if ($result['statusCode'] == 200) {
                return $this->toSuccess();
            } else {
                return $this->toError(500, '数据提交失败');
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}
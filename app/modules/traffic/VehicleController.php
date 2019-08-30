<?php
namespace app\modules\traffic;

use app\models\service\Road;
use app\models\service\RoadSection;
use app\modules\BaseController;
use app\services\data\UserData;

// 交管车辆定位系统
class VehicleController extends BaseController
{
    // 查询违章-车辆列表
    public function MapVehicleListAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 违章开始时间
            'peccancyTime' => '未接收到违章开始时间',
            // 省id
            'provinceId' => '请选择省',
            // 市id
            'cityId' => 0,
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 请求服务
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 15013,
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常:'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 处理时间戳
        $this->handleBackTimestamp($list, ['peccancyTime']);
        // 成功返回
        return $this->toSuccess($list);
    }

    // 根据子系统机构id查询快递公司信息
    public function SubInsIdFindCompanyAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 子系统insId
            'subInsId' => '未收到子系统insId',
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 请求服务
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => 20001,
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常:'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 处理时间戳
        $this->handleBackTimestamp($data);
        // 成功返回
        return $this->toSuccess($data);
    }

    // 获取GPS点列表
    public function GPSListAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // udid
            'udid' => '未收到udid',
            // 开始时间(时间戳)
            'begin' => '未收到开始时间',
            // 结束时间(时间戳)
            'end' => '未收到结束时间',
            // 开始日期(yyyyMMdd)
            'dateStart' => '未收到开始日期',
            // 结束日期(yyyyMMdd)
            'dateEnd' => '未收到结束日期',
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 请求服务
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 60303,
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常:'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 处理时间戳
        $this->handleBackTimestamp($data);
        // 成功返回
        return $this->toSuccess($data);
    }



    // 获取GPS点列表 去重复点
    public function GPSListDelRepPointAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // udid
            'udid' => '未收到udid',
            // 开始时间(时间戳)
            'begin' => '未收到开始时间',
            // 结束时间(时间戳)
            'end' => '未收到结束时间'
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 请求服务
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 60304,
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常:'.$result['msg']);
        }
        if (!isset($result['content']['data']) || $result['content']['data'] == null) {
            return $this->toSuccess([]);
        }
        $data = $result['content']['data'];
        // 处理时间戳
        $this->handleBackTimestamp($data);
        // 成功返回
        return $this->toSuccess($data);
    }

}

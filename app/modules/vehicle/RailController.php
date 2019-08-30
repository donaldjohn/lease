<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/28
 * Time: 16:13
 */
namespace app\modules\vehicle;


use app\models\VehicleRail;
use app\modules\BaseController;

class RailController extends BaseController
{
    /**
     * 获取电子围栏列表
     */
    public function IndexAction()
    {
        $pageSize = intval($this->request->get('pageSize'));
        $pageNum = intval($this->request->get('pageNum'));
        $status = $this->request->get('status');
        $railNum = $this->request->get('railNum');

        $params = [
            "pageSize" => (int)$pageSize,
            "pageNum" => (int)$pageNum,
            "insId" => isset($this->authed->insId) ? $this->authed->insId : '8888',
            "status" => $status,
            "railNum" => $railNum,
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '10104',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        $item = [];
        foreach ($result['content']['data'] as $key => $val) {
            $val['createAt'] = $val['createAt'] ? date('Y-m-d H:i:s', $val['createAt']) : '';
            $val['updateAt'] = $val['updateAt'] ? date('Y-m-d H:i:s', $val['updateAt']) : '';
            $val['latlng'] = unserialize($val['latlng']);
            $item[] = $val;
            unset($result['content']['data'][$key]);//释放数据
        }
        $pageInfo = isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : [
            "total" => 0, "pageSize" => $pageSize, "pageNum" => $pageNum,];
        return $this->toSuccess($item, $pageInfo);
    }

    public function CreateAction()
    {
        $fields = [
            'railNum' => '围栏编号必填',
            'type' => '围栏方式必填',
            'provinceCode' => '省代码必填',
            'provinceName' => '省名称必填',
            'cityCode' => '市代码必填',
            'cityName' => '市名称必填',
            'areaCode' => '地区代码必填',
            'areaName' => '地区名称必填',
            'startTime' => '开始时间',
            'endTime' => '结束时间',
            'latlng' => '围栏区域必填'
        ];
        $parameter = $this->getArrPars($fields, $this->request->getJsonRawBody(true));
        if (!$parameter){
            return;
        }
        if (!in_array($parameter['type'], [1,2])) {
            return $this->toError('500', '围栏方式填写错误');
        }
        $parameter['latlng'] = serialize($parameter['latlng']);

        $parameter['remark'] = isset($this->content->remark) ? $this->content->remark : '';
        $parameter['insId'] = isset($this->authed->insId) ? $this->authed->insId : 1;
//        $parameter['startTime'] = time();
//        $parameter['endTime'] = strtotime('2030-12-30');
        $parameter['status'] = 1;
        $data = [
            'parameter' => $parameter,
            'code' => '10101',
        ];
        //print_r(json_encode($data));exit;
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess();
    }

    /**
     * 编辑电子围栏
     */
    public function EditAction()
    {
        $fields = [
            'id' => 'ID必填',
        ];
        $parameter = $this->request->getJsonRawBody(true);
        $res = $this->getArrPars($fields, $parameter);
        if (!$res){
            return;
        }
        if (isset($parameter['type']) && !in_array($parameter['type'], [1,2])) {
            return $this->toError('500', '围栏方式填写错误');
        }
        //序列化地图参数
        if (isset($parameter['latlng'])) {
            $parameter['latlng'] = serialize($parameter['latlng']);
        }
        $data = [
            'parameter' => $parameter,
            'code' => '10103',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess();
    }

    /**
     * 超出电子围栏数据接口
     */
    public function ListAction()
    {
        $pageSize = intval($this->request->get('pageSize'));
        $pageNum = intval($this->request->get('pageNum'));
        $inTimeStart = $this->request->get('inTime');
        $inTimeEnd = intval($this->request->get('outTime'));
//        $railId = $this->request->get('railId');
//        $vehicleId = $this->request->get('vehicleId');

        $params = [
            "pageSize" => $pageSize ? (int)$pageSize : 10,
            "pageNum" => $pageNum ? (int)$pageNum : 1,
            "inTimeStart" => $inTimeStart,
            "inTimeEnd" => $inTimeEnd,
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '10114',
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        $item = [];
        foreach ($result['content']['data'] as $key => $val) {
            $val['inTime'] = $val['inTime'] ? date('Y-m-d H:i:s', $val['inTime']) : '';
            $val['outTime'] = $val['outTime'] ? date('Y-m-d H:i:s', $val['outTime']) : '';
            $val['updateTime'] = $val['updateTime'] ? date('Y-m-d H:i:s', $val['updateTime']) : '';
            // 演示用假数据
            $val['company'] = '';
            $val['bianhao'] = '';
            $val['railNum'] = '';
            if ($val['vehicleId']) {
                $pram = ["vehicleId" => $val['vehicleId']];
                $data = [
                    'parameter' => $pram,
                    'code' => '60005',
                ];
                $result_vehicle = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
                if ($result_vehicle['statusCode'] <> 200) {
                    return $this->toError($result_vehicle['statusCode'],$result_vehicle['msg']);
                }
                $val['bianhao'] = $result_vehicle['content']['VehicleDO']['bianhao'];
            }
            if ($val['railId']) {
                $pram = ["id" => $val['railId']];
                $data = [
                    'parameter' => $pram,
                    'code' => '10104',
                ];
                $result_rail = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
                if ($result['statusCode'] <> 200) {
                    return $this->toError($result_rail['statusCode'],$result['msg']);
                }
                $val['railNum'] = isset($result_rail['content']['data'][0]) ? $result_rail['content']['data'][0]['railNum'] : '';
            }
            $item[] = $val;
            unset($result['content']['data'][$key]);//释放数据
        }
        $pageInfo = isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : [
            "total" => 0, "pageSize" => $pageSize, "pageNum" => $pageNum,];
        return $this->toSuccess($item, $pageInfo);
    }

    /**
     * 增加删除接口
     */
    public function DeleteAction()
    {
        $fields = [
            'id' => 'ID必填',
        ];
        $parameter = $this->getArrPars($fields, $this->request->getJsonRawBody(true));
        if (!$parameter){
            return;
        }
        $data = [
            'parameter' => $parameter,
            'code' => '10102',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        };
        return $this->toSuccess();
    }

    // 查询行政区域下的启用的电子围栏信息
    public function AreaRailingAction()
    {
        $fields = [
            'provinceId' => '请选择省份',
            'cityId' => 0
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 15014,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'];
        return $this->toSuccess($data);
    }
}
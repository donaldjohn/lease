<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\library\ReturnCodeService;
use app\modules\BaseController;

/**
 * Class IndexController
 * @package app\modules\warranty
 * 基础数据查询
 */
class IndexController extends BaseController
{


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 获取车辆类型
     */
    public function VehicleTypesAction()
    {
        $types = [];
        //获取url int参数status,不传status默认为1
        $typeStatus = $this->request->getQuery('status', 'int', 1);
        //$json = $this->request->getJsonRawBody(true);
        $json['typeStatus'] = $typeStatus;
        $this->logger->info('查询车辆类型:' . json_encode($json));
        $result = $this->userData->common($json, $this->Zuul->biz, ReturnCodeService::WARRANTY_READ_VEHICLE_TYPE);
        $result = $result['data'];
        foreach ($result as $item) {
            $list = [];
            $list['id'] = $item['id'];
            $list['typeName'] = $item['typeName'];
            //$list['typeStatus'] = $item['typeStatus'];
            array_push($types, $list);
        }
        return $this->toSuccess($types);
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 获取车辆区域
     */
    public function VehicleAreasAction()
    {
        $areas = [];
        //获取url int参数status,不传status默认为1
        $areaStatus = $this->request->getQuery('status', 'int', 1);
        $json['areaStatus'] = $areaStatus;
        //绑定类型ID
        $vehicleTypeId = $this->request->getQuery('vehicleTypeId', 'int', null);
        if (!is_null($vehicleTypeId)) {
            $json['vehicleTypeId'] = $vehicleTypeId;
        }
        $this->logger->info('查询车辆区域:' . json_encode($json));
        $result = $this->userData->common($json, $this->Zuul->biz, ReturnCodeService::WARRANTY_READ_VEHICLE_AREA);
        $result = $result['data'];
        foreach ($result as $item) {
            $list = [];
            $list['id'] = $item['id'];
            $list['areaName'] = $item['areaName'];
            $list['vehicleTypeId'] = $item['vehicleTypeId'];
            // $list['vehicleTypeName'] = isset($item['vehicleTypeName']) ? $item['vehicleTypeName'] : '无';
            $list['areaStatus'] = $item['areaStatus'];
            $list['areaOrder'] = $item['areaOrder'];
            array_push($areas, $list);
        }
        return $this->toSuccess($areas);

    }


    public function RegionAction()
    {
        $params = ["code" => "10022", "parameter" => (object)[]];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        $result = $result['content']['data'];
        $result = $this->warrantyData->res_tree($result, 0);
        return $this->toSuccess($result);
    }



}
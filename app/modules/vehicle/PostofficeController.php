<?php
namespace app\modules\vehicle;

use app\modules\BaseController;

// 邮管车辆
class PostofficeController extends BaseController
{
    // 已打印过行驶证备案卡的车辆
    public function LicenseIssuedVehicleAction()
    {
        $fields = [
            // 快递公司insId
            'insId' => 0,
            // 通用搜索
            'searchText' => 0,
            // 车架号
            'vin' => 0,
            // 智能设备号
            'udid' => 0,
            // 车牌号
            'plateNum' => 0,
            // 车辆编号
            'bianhao' => 0,
            // 骑手姓名
            'realName' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        // TODO:邮管/快递协会/快递公司
        // 查询当前机构能关联的子系统快递公司
        $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false!==$expressIdList){
            if (empty($expressIdList)){
                return $this->toEmptyList();
            }
            $parameter['insIdList'] = $expressIdList;
        }
        // 车辆列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11014,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 子系统快递公司机构ids
        $insIds = [];
        foreach ($list as $item){
            if ($item['insId']>0){
                $insIds[] = $item['insId'];
            }
        }
        // 获取快递公司名称
        $expressNames = $this->userData->getCompanyNamesByInsIds($insIds);
        foreach ($list as $k => $v){
            // 保险状态
            $v['secureStatus'] = time()<$v['secureEndTime'] ? true : false;
            // 快递公司
            $v['expressName'] = $expressNames[$v['insId']] ?? '';
            $list[$k] = $v;
        }
        // 处理时间戳
        $this->handleBackTimestamp($list, ['printTime', 'secureEndTime']);
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 车辆使用统计
    public function VehicleUsageStatisticsAction()
    {
        // 车辆使用统计
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11015,
            'parameter' => [
                'insId' => $this->authed->insId,
                'userId' => $this->authed->userId
            ]
        ],"post");
        if (200 != $result['statusCode']){
            if (1018 == $result['statusCode']){
                return $this->toError(500,'当前用户无权访问');
            }
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['vehicleUsageCensusInfo'];
        $data['companyList'] = $this->SortTwoDimensionalArray($data['companyList'], 'vehiclePeccancyNum', true);
        return $this->toSuccess($data);
    }


    // 车辆使用统计月使用趋势
    public function MonthVehicleUsageAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11019,
            'parameter' => [
                'insIdList' => $request['insIdList'],
            ]
        ],"post");
        if (200 != $result['statusCode']){
            if (1018 == $result['statusCode']){
                return $this->toError(500,'当前用户无权访问');
            }
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content'];
        return $this->toSuccess($data);
    }

    // 车辆使用统计年使用趋势
    public function YearVehicleUsageAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11020,
            'parameter' => [
                'insIdList' => $request['insIdList'],
            ]
        ],"post");
        if (200 != $result['statusCode']){
            if (1018 == $result['statusCode']){
                return $this->toError(500,'当前用户无权访问');
            }
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content'];
        return $this->toSuccess($data);
    }

    /**
     * 二维数组排序
     * @param $arrays 原始二维数组
     * @param $field 排序字段
     * @param bool $desc 是否降序，默认升序
     * @return array 排序后的数组
     */
    public function SortTwoDimensionalArray($arrays, $field, $desc=false)
    {
        $tmp = [];
        foreach ($arrays as $arr){
            $val = $arr[$field] ?? 0;
            $tmp[$val][] = $arr;
        }
        //ksort() - 根据键，以升序对关联数组进行排序
        //krsort() - 根据键，以降序对关联数组进行排序
        if ($desc){
            krsort($tmp);
        }else{
            ksort($tmp);
        }
        $arrays = [];
        foreach ($tmp as $item){
            $arrays = array_merge($arrays, $item);
        }
        return $arrays;
    }
}
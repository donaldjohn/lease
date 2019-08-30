<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/20
 * Time: 20:32
 */
namespace app\modules\vehicle;

use app\modules\BaseController;
use Phalcon\Application\Exception;

class GpsController extends BaseController
{
    public function IndexAction()
    {
        $udid = $this->request->get("udid");
        if (!$udid) {
           $vehicleId = $this->request->get("vehicleId");
        	if (!$vehicleId) {
            	return $this->toError('500', '未选择车辆');
        	}
     
        	//查询车辆的信息
        	$pram = ["vehicleId" => $vehicleId];
        	$data = [
            	'parameter' => $pram,
            	'code' => '60005',
        	];
        	$result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        	if ($result['statusCode'] <> 200) {
            	return $this->toError($result['statusCode'], $result['msg']);
        	};
        	if (!isset($result['content']['VehicleDO']['udid'])) {
            	return $this->toError('500','未找到该设备');
        	}
          	$udid = $result['content']['VehicleDO']['udid'];
            
        }
        $start_time = $this->request->get("startTime");
        $end_time = $this->request->get("endTime");

        $start_time = $start_time ? strtotime($start_time) : strtotime(date('Y-m-d'));
        $end_time = $end_time ? strtotime($end_time) : strtotime(date('Y-m-d H:i:s'));
        if (date("Y-m-d",$start_time) != date("Y-m-d",$end_time)) {
            return $this->toError('500', '起止时间不在同一天');
        }
        try {
            $pram = ["udid" => $udid, "begin" => $start_time, "end" => $end_time,
                "dateStart" => date("Ymd",$start_time), "dateEnd" => date("Ymd",$start_time)];
            $data = [
                'parameter' => $pram,
                'code' => '60301',
            ];
            $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
            if ($result['statusCode'] <> 200) {
                return $this->toError($result['statusCode'], $result['msg']);
            };
            $item = [];
            if (isset($result['content']['data']) && $result['content']['data']) {
                foreach ($result['content']['data'] as $key => $val) {
                    $item[] = [
                        "create_time" => $val['createTime'] ? date('Y-m-d H:i:s', $val['createTime']) : '--',
                        "position" => [$val['lng'], $val['lat']],
                    ];
                }
            }
            return $this->toSuccess($item);
        } catch (Exception $e) {
            return $this->toError('500', $e->getMessage());
        }

    }
}
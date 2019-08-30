<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/20
 * Time: 16:45
 */
namespace app\modules\vehicle;

use app\common\library\PhpExcel;

use app\models\VehicleDaily;
use app\modules\BaseController;
use Phalcon\Application\Exception;

class MileageController extends BaseController
{

    /**
     * 车辆详细信息的chart图表接口
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function ChartAction()
    {
        try {
            $this->logger->info("test chart");
            $vehicle_id = intval($this->request->get("vehicleId"));
            if (!$vehicle_id) {
                return $this->toError('500', '车辆vehicleId不能为空');
            }
            $models = VehicleDaily::query()
                ->where('vehicle_id = :vehicle_id:', array('vehicle_id' => 5110))
                ->orderBy('date desc')
                ->execute()
                ->toArray();
            foreach ($models as $key => &$val) {
                $val['create_time'] = date('Y-m-d H:i:s');
            }
            return $this->toSuccess($models);
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());
            return $this->toError('500', $e->getMessage());
        }
    }

    /**
     * Todo :
     * 里程报表
     */
    public function ListAction()
    {
        $pageSize = intval($this->request->get('pageSize'));
        $pageNum = intval($this->request->get('pageNum'));
        $recordTimeStart = $this->request->get("recordTimeStart");
        $recordTimeEnd = $this->request->get("recordTimeEnd");
        $vehicleId = $this->request->get("vehicleId");
        $bianhao = $this->request->get("bianhao");
        $driverId = $this->request->get("driverId");
//
        $recordTimeStart = $recordTimeStart ? strtotime($recordTimeStart) :0;
        $recordTimeEnd = $recordTimeEnd ? strtotime($recordTimeEnd) : 0;
        $params = [
            "pageSize" => (int)$pageSize,
            "pageNum" => (int)$pageNum,
            "vehicleId" => $vehicleId,
            "bianhao" => $bianhao,
            "driverId" => $driverId,
            "recordTimeStart" => $recordTimeStart,
            "recordTimeEnd" => $recordTimeEnd,
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '60009',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $content = [];
        foreach ($result['content']['vehicleMileageVos'] as $key => $val) {
            $val['duration'] = number_format($val["duration"]/3600, 2);
            $content[] = $val;
            unset($result['content']['vehicleMileageVos'][$key]);
        }
        return $this->toSuccess($content, $result['content']['pageInfo']);

    }

    /**
     * 车辆里程详情
     */
    public function IndexAction()
    {
        $vehicle_id = intval($this->request->get("vehicleId"));
        if (!$vehicle_id) {
            return $this->toError('500', '车辆vehicleId不能为空');
        }
        $pageSize = intval($this->request->get('pageSize'));
        $pageNum = intval($this->request->get('pageNum'));
        $recordTimeStart = $this->request->get("recordTimeStart");
        $recordTimeEnd = $this->request->get("recordTimeEnd");

        $recordTimeStart = $recordTimeStart ? strtotime($recordTimeStart) : '';
        $recordTimeEnd = $recordTimeEnd ? strtotime($recordTimeEnd) : '';
        $params = [
            "pageSize" => (int)$pageSize,
            "pageNum" => (int)$pageNum,
            "vehicleId" => $vehicle_id,
            "recordTimeStart" => $recordTimeStart,
            "recordTimeEnd" => $recordTimeEnd,
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '60011',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $content = [];
        foreach ($result['content']['vehicleMileageDOS'] as $key => $val) {
            $val['recordTime'] = date('Y-m-d H:i:s', $val['beginTime']);
            $content[] = [
                "mileage" => $val["distance"],
                "recordTime" => $val["recordTime"],
                "duration" =>  number_format($val["duration"]/3600, 2),
            ];
            unset($result['content']['vehicleMileageDOS'][$key]);
        }
        return $this->toSuccess($content, $result['content']['pageInfo']);
    }

    /**
     * 导出数据
     */
    public function ExportAction()
    {
        $recordTimeStart = $this->request->get("recordTimeStart");
        $recordTimeEnd = $this->request->get("recordTimeEnd");
        $vehicleId = $this->request->get("vehicleId");
        $bianhao = $this->request->get("bianhao");
        $driverId = $this->request->get("driverId");

        $recordTimeStart = $recordTimeStart ? strtotime($recordTimeStart) : strtotime(date('Y-m-d'));
        $recordTimeEnd = $recordTimeEnd ? strtotime($recordTimeEnd) : strtotime(date('Y-m-d H:i:s'));
        $params = [
            "vehicleId" => $vehicleId,
            "bianhao" => $bianhao,
            "driverId" => $driverId,
            "recordTimeStart" => $recordTimeStart,
            "recordTimeEnd" => $recordTimeEnd,
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '60009',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $sheetRow = ['车辆编号',  '车架号', '行驶证备案号', '骑手', '站点', '里程', '行驶时间'];//表头
        $label = ['bianhao', 'vin', 'recordNum', 'driverName', 'siteName', 'mileage', 'duration'];
        $data = [];
        $i = 0;
        foreach ($result['content']['vehicleMileageVos'] as $key => $val) {
            foreach ($label as $k => $v) {
                if ($v == 'duration') {
                    $data[$i][] = number_format($val[$v]/3600, 2);
                } else {
                    $data[$i][] = isset($val[$v]) ? $val[$v] : '';
                }
            }
            $i++;
            unset($result['content']['vehicleMileageVos'][$key]);
        }
        PhpExcel::downloadExcel('车辆里程报表', $sheetRow, $data);
    }



    public function ReportAction()
    {
       $startTime = $this->request->getQuery('startTime','int',null,true);
       $endTime = $this->request->getQuery('endTime','int',null,true);
       $bianhao = $this->request->getQuery('bianhao','string',null,true);
       $jobNo = $this->request->getQuery('jobNo','string',null,true);
       $regionId = $this->request->getQuery('regionId','string',null,true);
       $pageSize = $this->request->getQuery('pageSize','int',20,true);
       $pageNum = $this->request->getQuery('pageNum','int',1,true);

        $json = [];

        if ($startTime != null) {
            $json['startTime'] = $startTime;
        }
        if ($endTime != null) {
            $json['endTime'] = $endTime;
        }
        if ($bianhao != null) {
            $json['bianhao'] = $bianhao;
        }
        if ($jobNo != null) {
            $json['jobNo'] = $jobNo;
        }
        if ($regionId != null) {
            $json['regionId'] = $regionId;
        }
        $json['pageSize'] = $pageSize;
        $json['pageNum'] = $pageNum;
        $json['currentRegionId'] = $this->authed->regionId;
        $json['insId'] = $this->authed->insId;


        $result = $this->userData->common($json,$this->Zuul->vehicle,60109);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($result == null)
            return $this->toSuccess([],$pageInfo);
        return $this->toSuccess($result,$pageInfo);





    }
}
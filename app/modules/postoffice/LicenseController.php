<?php
namespace app\modules\postoffice;

use app\common\library\ZuulApiService;
use app\modules\BaseController;
use app\services\data\ProductData;
use app\services\data\QRCodeData;

//行驶证模块
class LicenseController extends BaseController
{
    /**
     * 行驶证列表
     * code：10003
     */
    public function listAction()
    {
        $fields = [
            // 行驶证编号
            'id' => 0,
            // 保险公司ID
            'secureId' => 0,
            // 快递公司id
            'expressId' => 0,
            // 车辆id
            'vehicleId' => 0,
            // 打印状态  1:未打印 2:已打印
            'licenseStatus' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (!$parameter){
            return;
        }
        // 如有车架号条件，先查车辆
        if(isset($_GET['vin'])&&''!=$_GET['vin']){
            $vehicleres = $this->curl->httpRequest($this->Zuul->vehicle,[
                'code' => 60010,
                'parameter' => [
                    'vin' => $_GET['vin'],
                ],
            ],"post");
            if (200!=$vehicleres['statusCode'] || !isset($vehicleres['content']['vehicleDOS'][0])){
                return $this->toSuccess([]);
            }
            $parameter['vehicleId'] = $vehicleres['content']['vehicleDOS'][0]['id'];
        }
        // 获取关联快递公司id
        $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false===$expressIdList || empty($expressIdList)){
            return $this->toEmptyList();
        }
        $parameter['expressIdList'] = $expressIdList;
        //调用微服务接口获取行驶证数据
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '10003',
            'parameter' => $parameter
        ],"post");
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $licenselist = $result['content']['licenseDOS'];
        // 获取车辆id列表去查询车辆信息
        $ids = [];
        $secure_ids = [];
        foreach ($licenselist as $val){
            $ids[] = $val['vehicleId'];
            $secure_ids[] = $val['secureId'];
        }
        $vehiclelist = [];
        $vehicleModelIds = [];
        if (count($ids) > 0){
            //调用微服务接口获取车辆信息
            $params = ["code" => "60008","parameter" => ["idList" => $ids]];
            $vehicle_res = $this->curl->httpRequest($this->Zuul->vehicle,$params,"post");
            $reslist = $vehicle_res['content']['vehicleDOS'];
            foreach ($reslist as $vehicle){
                $vehiclelist[$vehicle['id']] = $vehicle;
                if (0!=$vehicle['vehicleModelId']){
                    $vehicleModelIds[] = $vehicle['vehicleModelId'];
                }
            }
        }
        $secureNumlist = [];
        if (count($ids) > 0){
            //调用微服务接口获取保单信息
            $secureres = $this->curl->httpRequest($this->Zuul->biz, [
                "code" => "10020",
                "parameter" => [
                    "ids" => $secure_ids
                ]
            ],"post");
            $secureres = $secureres['content']['secureDOS'];
            foreach ($secureres as $secure){
                $secureNumlist[$secure['vehicleId']] = $secure['secureNum'];
            }
        }
        // 获取车辆商品信息
        $vehicleModels = (new ProductData())->getVehicleProductsByVehicleModelIds($vehicleModelIds);
        // 返回数据处理
        //处理返回数据
        $fields = [
            'id' => '',
            'secureId' => '',
            'vehicleId' => '',
            'secureNum' => '',//保单号
            'vin' => '',//车架号
            'plateNum' => '',//车牌号
            'expressName' => '',//快递公司
            'recordNum' => '',//备案号
            'owner' => '',//所有人
            'vehicleName' => '',//车辆名称
            'brandName' => '',//品牌名
            'model' => '',//型号
            'ratedVoltage' => '',//电压
            'motorPower' => '',//功率
            'registerDate' => '',//登记日期
            'issueDate' => '',//发放日期
            'licenseStatus' => [
                'fun' => [
                    '1' => '未打印',
                    '2' => '已打印',
                ]
            ],
            'createAt' => '',
            'updateAt' => '',
            'printTime' => '',
        ];

        $expressIds = [];
        foreach ($licenselist as $license) {
            $expressIds[] = $license['expressId'];
        }
        // 获取快递公司名称
        $expressNames = $this->userData->getCompanyNamesByInsIds($expressIds);
        foreach ($licenselist as $k => $license){
            //车辆信息
            if (isset($vehiclelist[$license['vehicleId']])){
                $license['vin'] = $vehiclelist[$license['vehicleId']]['vin'];
                $license['plateNum'] = $vehiclelist[$license['vehicleId']]['plateNum'];
                $license['bianhao'] = $vehiclelist[$license['vehicleId']]['bianhao'];
                //备案号
                $license['recordNum'] =  $vehiclelist[$license['vehicleId']]['recordNum'] ;
                $license['vehicleModelId'] =  $vehiclelist[$license['vehicleId']]['vehicleModelId'] ;
            }
            //保单信息
            if (isset($secureNumlist[$license['vehicleId']])){
                $license['secureNum'] = $secureNumlist[$license['vehicleId']];
            }
            //品牌型号信息
            if (isset($license['vehicleModelId'])){
                $license['ratedVoltage'] =  $vehicleModels[$license['vehicleModelId']]['ratedVoltage'] ?? '';
                $license['vehicleName'] = $vehicleModels[$license['vehicleModelId']]['vehicleName'] ?? '';
                $license['brandName'] = $vehicleModels[$license['vehicleModelId']]['brandName'] ?? '';
                $license['model'] = $vehicleModels[$license['vehicleModelId']]['model'] ?? '';
                $license['motorPower'] = $vehicleModels[$license['vehicleModelId']]['motorPower'] ?? '';
            }
            $license['createAt'] = date('Y-m-d H:i:s', $license['createAt']);
            $license['printTime'] = date('Y-m-d H:i:s', $license['printTime']);
            $license['issueDate'] = date('Y-m-d',time());
            $license['registerDate'] = $license['createAt'];
            // 快递公司
            $license['expressName'] = $expressNames[$license['expressId']] ?? '----';
            $license['owner'] = $license['expressName'];
            $license = $this->backData($fields, $license);
            $licenselist[$k] = $license;
        }
        $meta['total'] = $result['content']['pageInfo']['total'];
        $meta['pageNum'] = $result['content']['pageInfo']['pageNum'];
        $meta['pageSize'] = $result['content']['pageInfo']['pageSize'];
        return $this->toSuccess($licenselist,$meta);
    }

    /**
     * 查询行驶证
     * code：
     */
    public function oneAction($id)
    {
        //调用微服务接口获取数据
        $params = [
            "code" => "10003",
            "parameter" => [
                'id' => $id,
                // 目前不传page参数后端无法返回数据
                'pageNum' => 1,
                'pageSize' => 1,
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
        if ($result['statusCode']!='200' || count($result['content']['licenseDOS'])!=1) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $license = $result['content']['licenseDOS'];
        if (count($license)!=1){
            return $this->toError(500,'未获取到行驶证信息');
        }
        //调用微服务接口获取车辆信息
        $params = ["code" => "60005","parameter" => ["vehicleId" => $license[0]['vehicleId']]];
        $vres = $this->curl->httpRequest($this->Zuul->vehicle,$params,"post");
        if ($vres['statusCode']!='200' || null==$vres['content']['VehicleDO']) {
            return $this->toError(500,'未获取到车辆信息');
        }
        $vehicle = $vres['content']['VehicleDO'];
        // 获取商品信息
        $vehicleProductInfo  =(new ProductData())->getVehicleProductInfoByVehicleModelId($vehicle['vehicleModelId']);
        $license = [
            'recordNumber' => $vehicle['plateNum'],
            'carNumber' => $vehicle['vin'],
            'qrCode' => (new QRCodeData())->getQRCodeContent($vehicle['bianhao']),
            'pressure' => $vehicleProductInfo['ratedVoltage'], // 电压
            'power' => $vehicleProductInfo['motorPower'],//功率
            // 所有者
            'possessor' => $this->userData->getExpressNamesByInsId($license[0]['expressId']) ?? '---',
            'vin' => $vehicle['vin'],
            'brand' => $vehicleProductInfo['brandName'].' '.$vehicleProductInfo['model'],
            'registerDate' => date('Y-m-d',$license[0]['createAt']),
            'grantDate' => date('Y-m-d',time()),
        ];
        return $this->toSuccess($license);
    }


    /**
     * 新增行驶证
     * code：
     */
    public function CreateAction()
    {
    }


    /**
     * 修改行驶证
     * code：10004
     */
    public function UpdateAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数提取
        $fields = [
            //当前只支持行驶证未打印变更为已打印
            'licenseStatus' => [
                'min' => '2',
                'max' => '2',
            ],
        ];
        $license = $this->getArrPars($fields, $request);
        if (!$license){
            if ([] === $license){
                return $this->toError(500, '未提交变更');
            }
            return;
        }
        $license['id'] = $id;
        $license['updateAt'] = time();
        //调用微服务接口获取数据
        $params = ["code" => "10004","parameter" => $license];
        $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
        if ($result['statusCode'] != '200'){
            return $this->toError(500,'更新失败');
        }
        return $this->toSuccess(200,'更新成功');
    }

    /**
     * 删除行驶证
     * code：
     */
    public function DeleteAction($id)
    {
    }

}

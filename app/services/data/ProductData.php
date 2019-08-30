<?php
namespace app\services\data;


class ProductData extends BaseData
{
    // 获取新商品详情
    public function getVehicleProductInfoByVehicleModelId($vehicleModelId)
    {
        if (!($vehicleModelId>0)){
            return null;
        }
        $result = $this->curl->httpRequest($this->Zuul->product,[
            'code' => 10069,
            'parameter' => [
                'id' => $vehicleModelId
            ]
        ], "post");
        return $result['content']['vehicleModelDetail'] ?? null;
    }

    // 获取单个新车辆商品信息
    public function getVehicleProductByVehicleModelId($vehicleModelId){
        return $this->getVehicleProductsByVehicleModelIds([$vehicleModelId], false)[0] ?? null;
    }

    // 获取单个新车辆商品信息
    public function getBatteryProductByBatteryModelId($batteryModelId){
        return $this->getBatteryProductsByBatteryModelIds([$batteryModelId], false)[0] ?? null;
    }

    // 批量获取新车辆商品信息
    public function getVehicleProductsByVehicleModelIds(array $vehicleModelIds, $convert=true)
    {
        $vehicleModelIds = array_values(array_unique(array_diff($vehicleModelIds,[0])));
        if (empty($vehicleModelIds)){
            return [];
        }
        $result = $this->curl->httpRequest($this->Zuul->product,[
            'code' => 10077,
            'parameter' => [
                'idList' => $vehicleModelIds
            ]
        ], "post");
        $vehicleModelDetails = $result['content']['list'] ?? [];
        if ($convert){
            $tmpList = [];
            foreach ($vehicleModelDetails as $vehicleModelDetail){
                $tmpList[(string) $vehicleModelDetail['id']] = $vehicleModelDetail;
            }
            $vehicleModelDetails = $tmpList;
        }
        return $vehicleModelDetails;
    }

    // 批量获取新电池商品信息
    public function getBatteryProductsByBatteryModelIds(array $batteryModelIds, $convert=true)
    {
        $batteryModelIds = array_values(array_unique(array_diff($batteryModelIds,[0])));
        if (empty($batteryModelIds)){
            return [];
        }
        $result = $this->curl->httpRequest($this->Zuul->product,[
            'code' => 10088,
            'parameter' => [
                'idList' => $batteryModelIds
            ]
        ], "post");
        $batteryModelDetails = $result['content']['list'] ?? [];
        if ($convert){
            $tmpList = [];
            foreach ($batteryModelDetails as $batteryModelDetail){
                $tmpList[(string) $batteryModelDetail['id']] = $batteryModelDetail;
            }
            $batteryModelDetails = $tmpList;
        }
        return $batteryModelDetails;
    }
}
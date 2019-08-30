<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\order\VehicleWarrantyOrder;



class RentWarrantyData extends BaseData
{
    public function CreateWarrantyOrder($vehicleId, $driverId, $serviceContractId, $bianhao=null, $vin=null)
    {
        // 如果没有传车辆编号 || 车架号 则查询
        if (is_null($bianhao) || is_null($vin)){
            $vehicle = (new VehicleData())->getVehicleById($vehicleId);
            $bianhao = $vehicle['bianhao'];
            $vin = $vehicle['vin'];
        }
        // 查询车辆首次联保时间
        $historyOrder = VehicleWarrantyOrder::findFirst([
            'conditions' => 'vehicle_id = ?1',
            'bind'       => [
                1 => $vehicleId,
            ],
            'order' => 'create_at ASC',
        ]);
        $reductionTime = 0;
        if (false!==$historyOrder){
            $reductionTime = ceil((time() - $historyOrder->create_at)/86400);
        }
        $createWarranty = $this->config->interface->warranty->createWarranty;
        // 调用联保接口生成联保订单
        $res = $this->curl->sendCurl($this->config->baseUrl.$createWarranty->uri, [
            'secretKey' => $this->config->interface->warranty->secretKey,
            'vehicleId' => $bianhao,
            'vehicleVin' => $vin,
            'bizCode' => $this->config->interface->warranty->bizCode,
            'reductionTime' => $reductionTime, // 扣减天数
            'effectTime' => time(),
        ], $createWarranty->method,['secretKey' => $this->config->interface->warranty->secretKey]);
        // 异常抛出
        if (200!=$res['statusCode']){
            throw new DataException([500, '联保服务创建失败'.$res['msg']]);
        }
        // 获取联保订单编号
        $ordedrSn = $res['content']['data']['warrantySn'];
        // 联保类型1有限2无限
        $warrantyType = $res['content']['data']['warrantyType'];
        // 投保状态1未生效2生效中3已失效
        $warrantyStatus = $res['content']['data']['warrantyStatus'];
        $WarrantyOrder = new VehicleWarrantyOrder();
        $res = $WarrantyOrder->save([
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'service_contract_id' => $serviceContractId,
            'ordedr_sn' => $ordedrSn,
            'order_status' => $warrantyStatus,
            'end_at' => 0,
        ]);
        if (!$res){
            throw new DataException([500, '联保订单创建失败']);
        }
        $this->logger->info("【发起联保成功】骑手id:{$driverId},车辆id:{$vehicleId}编号:{$bianhao},联保单号:{$ordedrSn}");
        return true;
    }

    /**
     * 终止服务的联保订单
     * @param $ServiceContractId 服务单id
     * @return bool
     */
    public function EndServiceWarranty($ServiceContractId=null)
    {
        // 如果没传服务单，则返回false
        if (is_null($ServiceContractId)){
            return false;
        }
        $WarrantyOrder = $this->getWarrantyOrderByServiceContractId($ServiceContractId);
        // 没有有效联保单，安全返回
        if (false===$WarrantyOrder){
            return null;
        }
        $WarrantyOrderSn = $WarrantyOrder->ordedr_sn;
        $endWarranty = $this->config->interface->warranty->endWarranty;
        // 调用联保接口终止保单状态
        $res = $this->curl->sendCurl($this->config->baseUrl.$endWarranty->uri, [
            'secretKey' => $this->config->interface->warranty->secretKey,
            'warrantySn' => $WarrantyOrderSn,
        ], $endWarranty->method,['secretKey' => $this->config->interface->warranty->secretKey]);
        // 安全返回
        if (200!=$res['statusCode']){
            $this->busLogger->recordingOperateLog("【联保终止失败】服务单id:{$ServiceContractId} 联保单号:{$WarrantyOrderSn} 接口返回:{$res['msg']}", '终止联保');
//            throw new DataException([500, '联保服务异常'.$res['msg']]);
            return false;
        }
        // 更新数据库联保订单状态 1未生效2生效中3已失效
        $WarrantyOrder->order_status = 3;
        $WarrantyOrder->end_at = time();
        $bol = $WarrantyOrder->save();
        if (false === $bol){
            $this->busLogger->recordingOperateLog("【联保终止失败】服务单id:{$ServiceContractId} 联保单号:{$WarrantyOrderSn} 状态保存失败", '终止联保');
        }
        return $bol;
    }

    // 获取联保订单 通过 id
    public function getWarrantyOrderById($id)
    {
        $res = VehicleWarrantyOrder::findFirst([
            'conditions' => 'id = ?1',
            'bind'       => [
                1 => $id,
            ]
        ]);
        return $res;
    }

    // 获取联保订单 通过 联保编号
    public function getWarrantyOrderBySn($WarrantyOrderSn)
    {
        $res = VehicleWarrantyOrder::findFirst([
            'conditions' => 'ordedr_sn = ?1',
            'bind'       => [
                1 => $WarrantyOrderSn,
            ]
        ]);
        return $res;
    }

    // 获取有效联保订单 通过 服务单号
    public function getWarrantyOrderByServiceContractId($ServiceContractId)
    {
        $res = VehicleWarrantyOrder::findFirst([
            'conditions' => 'service_contract_id = :service_contract_id: and order_status = 2',
            'bind'       => [
                'service_contract_id' => $ServiceContractId,
            ]
        ]);
        return $res;
    }
}

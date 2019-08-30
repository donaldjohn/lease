<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\order\VehicleWarrantyOrder;
use app\models\order\VehicleRepairOrder;
use app\services\data\StoreData;



class RentRepairData extends BaseData
{
    private $repairSn = null;
    private $repairStatus = null;
    private $falutMessage = null;
    private $falutSn = null;

    /**
     * 向联保方发起维修单
     * @param $bianhao 车辆编号
     * @param $storeId 门店ID
     * @param $userName 报障用户名
     * @param $userPhone 报障人手机号
     * @param $info 报障信息
     * @param $vehicleId 车辆ID
     * @return bool
     * @throws DataException
     */
    public function CreateRepairOrderToWarranty($bianhao, $storeId, $userName, $userPhone, $info, $vehicleId)
    {
        // 获取车辆当天维修次数
        $countNum = VehicleRepairOrder::count([
            'conditions' => 'vehicle_id = ?1 and create_at > ?2',
            'bind'       => [
                1 => $vehicleId,
                2 => strtotime(date('Y-m-d',time())),
            ],
            'order' => 'id DESC',
        ]);
        // 生成本地报障单号
        $this->falutSn = 'RP'
            .date('Ymd',time())
            .str_pad($vehicleId, 6, 0, STR_PAD_LEFT)
            .str_pad($countNum, 2, 0, STR_PAD_LEFT);
        // 获取创建维修单接口配置
        $createRepair = $this->config->interface->warranty->createRepair;
        // 查询门店的insid【联保方要求要storeId传门店的insId】
        $store = (new StoreData())->getStoreById($storeId);
        $storeInsId = $store['insId'];
        $data = [
            'vehicleId' => $bianhao,
            'storeId' => $storeInsId,
            'malfunctionUser' => $userName,
            'malfunctionMessage' => $info,
            'userPhone' => $userPhone,
            'malfunctionId' => $this->falutSn,
        ];
        // 调用联保接口生成维修单
        $res = $this->curl->sendCurl($this->config->baseUrl.$createRepair->uri, $data, $createRepair->method,[
            'secretKey' => $this->config->interface->warranty->secretKey,
        ]);
        // 异常抛出
        if (200!=$res['statusCode']){
            throw new DataException([500, '发起维修失败'.$res['msg']]);
        }
        // 记录维修单号、状态、报障信息，用以本地创建维修单
        $this->repairSn = $res['content']['data']['repairSn'];
        $this->repairStatus = $res['content']['data']['repairStatus'];
        $this->falutMessage = $info;
        return true;
    }

    /**
     * 创建维修订单【依赖先向联保方发起】
     * @param $vehicleId
     * @param $driverId
     * @param $warrantyId
     * @param $storeId
     * @return VehicleRepairOrder|bool
     * @throws DataException
     */
    public function CreateRepairOrder($vehicleId, $driverId, $warrantyId, $storeId)
    {
        if (is_null($this->repairSn) || is_null($this->repairStatus)){
            throw new DataException([500, '未生成有效维修单']);
        }
        $data = [
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'warranty_id' => $warrantyId,
            'repair_sn' => $this->repairSn,
            'store_id' => $storeId,
            'repair_status' => $this->repairStatus,
            'falut_message' => $this->falutMessage,
            'repair_sn_local' => $this->falutSn,
        ];
        $RepairOrder = new VehicleRepairOrder();
        $bol = $RepairOrder->save($data);
        if (false === $bol){
            return false;
        }
        return $RepairOrder;
    }

    /**
     * 取消维修单
     * @param $repairOrderId
     * @return bool
     * @throws DataException
     */
    public function CancelRepairOrder($repairOrderId)
    {
        $repairOrder = $this->getRepairOrderById($repairOrderId);
        if (false===$repairOrder){
            throw new DataException([500, '未查到维修单信息']);
        }
        // 维修状态1待接单2已接单3维修中4待支付5已完成 6已取消
        if (!in_array($repairOrder->repair_status, [1,2])){
            throw new DataException([500, '当前维修订单不在可取消状态']);
        }
        $repairOrderSn = $repairOrder->repair_sn;
        $cancelRepair = $this->config->interface->warranty->cancelRepair;
        // 调用联保接口取消维修单
        $res = $this->curl->sendCurl($this->config->baseUrl.$cancelRepair->uri, [
            'repairSn' => $repairOrderSn,
        ], $cancelRepair->method,['secretKey' => $this->config->interface->warranty->secretKey]);
        // 异常抛出
        if (200!=$res['statusCode']){
            throw new DataException([500, '维修服务取消失败'.$res['msg']]);
        }
        // 修改自有数据状态
        $repairOrder->repair_status = 6;
        return $repairOrder->save();
    }

    /**
     * 获取维修订单 通过 id
     * @param $repairOrderId 维修订单id
     * @return \Phalcon\Mvc\Model
     */
    public function getRepairOrderById($repairOrderId)
    {
        $repairOrder = VehicleRepairOrder::findFirst([
            'conditions' => 'id = ?1',
            'bind'       => [
                1 => $repairOrderId,
            ]
        ]);
        return $repairOrder;
    }

    /**
     * 从联保方获取维修详情
     */
    public function getRepairInfoByRepairSn($repairSn)
    {
        $getRepair = $this->config->interface->warranty->getRepair;
        // 从联保方获取维修详情
        $res = $this->curl->sendCurl($this->config->baseUrl.$getRepair->uri, [
            'secretKey' => $this->config->interface->warranty->secretKey,
            'repairSn' => $repairSn,
        ], $getRepair->method,['secretKey' => $this->config->interface->warranty->secretKey]);
        if (200!=$res['statusCode']){
            throw new DataException([500, '维修信息获取失败'.$res['msg']]);
        }
        $data = $res['content']['data'];
        // 联保配件
        $WarrantyItem = [
            'items' => [],
            'totalCost' => 0,
        ];
        // 自费配件
        $SelfItem = [
            'items' => [],
            'totalCost' => 0,
        ];
        // 支付方 1、自付 2、平台支付
        // 维修类型 1、维修 2、更换
        $repairTypes = [
            '1' => '维修',
            '2' => '更换',
        ];
        foreach ($data['orderDetail'] as $item) {
            $item['repairType'] = $repairTypes[(string)$item['repairType']];
            $item['totalCost'] = $item['fittingCost'] + $item['timeCost'];
            // 区分联保自费
            if (2==$item['payer']){
                $WarrantyItem['items'][] = $item;
                $WarrantyItem['totalCost'] += $item['totalCost'];
            }else{
                $SelfItem['items'][] = $item;
                $SelfItem['totalCost'] += $item['totalCost'];
            }
        }
        unset($data['orderDetail']);
        $data['repairItem'] = [
            'warranty' => $WarrantyItem,
            'self' => $SelfItem,
        ];
        return $data;
    }

    /**
     * 查询车辆未完结的维修单
     * @param $vehicleId
     * @return \Phalcon\Mvc\Model
     */
    public function getUnfinishedRepairByVehicleId($vehicleId)
    {
        // 查询未完结的维修单
        return VehicleRepairOrder::findFirst([
            'conditions' => 'vehicle_id = :vehicle_id: and repair_status IN ({status:array})',
            'bind'       => [
                'vehicle_id' => $vehicleId,
                'status' => [1,2,3,4],
            ]
        ]);
    }
}

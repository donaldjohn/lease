<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\order\VehicleRentOrder;




class ServiceContractData extends BaseData
{

    /**
     * 获取服务契约下有效的支付单【全部】
     * @param null $ServiceContractId
     * @return array
     * @throws DataException
     */
    public function getValidRentOrdersByServiceId($ServiceContractId=null)
    {
        if (is_null($ServiceContractId)){
            throw new DataException([500, '服务单ID异常']);
        }
        // 查询已支付&未删除的支付单
        $RentOrders = VehicleRentOrder::find([
            'service_contract_id = :service_contract_id: and pay_status=2 and is_delete=0',
            'bind' => [
                'service_contract_id' => $ServiceContractId,
            ]
        ]);
        return $RentOrders->toArray();
    }

    /**
     * 获取服务单下的租赁起止时间
     * @param $ServiceContractId
     * @return array|bool
     * @throws DataException
     */
    public function getRentStartEndTimeByServiceId($ServiceContractId)
    {
        /*$RentOrders = $this->getValidRentOrdersByServiceId($ServiceContractId);
        // 没有租赁单 安全返回
        if (empty($RentOrders)){
            return false;
        }
        // 提取服务单的租赁起止时间
        $minTime = 0;
        $maxTime = 0;
        foreach ($RentOrders as $vehicleRentOrder){
            if (0==$minTime){
                $minTime = $vehicleRentOrder['start_time'];
            }else{
                $minTime = min($minTime, $vehicleRentOrder['start_time']);
            }
            $maxTime = max($maxTime, $vehicleRentOrder['end_time']);
        }
        $data = [
            'startTime' => $minTime,
            'endTime' => $maxTime,
        ];*/

        $VRO = VehicleRentOrder::arrFindFirst([
            'service_contract_id' => $ServiceContractId,
            'pay_status' => 2,
        ],['columns' => 'MIN(start_time) AS startTime, MAX(end_time) AS endTime']);

        return $VRO->toArray();
    }

}

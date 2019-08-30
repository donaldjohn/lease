<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\cabinet\Cabinet;
use app\models\cabinet\ChargingManage;
use app\models\order\ChargingOrder;




class CabinetData extends BaseData
{
    // 获取多条换电柜信息 通过idlist
    public function getCabinetByIds($ids, $Convert=false)
    {
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0])));
        $cabinets = [];
        foreach ($ids as $id){
            try{
                $cabinets[] = $this->getCabinetById($id);
            }catch (\Exception $exception){
                // 防止异常报错
            }
        }
        if ($Convert){
            $tmp = [];
            foreach ($cabinets as $cabinet){
                $tmp[(string)$cabinet['id']] = $cabinet;
            }
            $cabinets = $tmp;
        }
        return $cabinets;
    }

    // 获取单条换电柜信息 通过id
    public function getCabinetById($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->charging, [
            'code' => 30004,
            'parameter' => [
                'id'   => $id
            ]
        ], "POST");
        if ($result['statusCode'] != '200' || !isset($result['content']['cabinetList'][0])) {
            throw new DataException([500, '未获取到换电柜信息']);
        }
        $cabinet = $result['content']['cabinetList'][0];
        return $cabinet;
    }

    /**
     * 获取骑手未支付的充换电单
     * @param $driverId
     * @return bool
     * @throws DataException
     */
    public function getUnpaidChargingOrdersBydriverId($driverId){
        // 获取换电记录
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10040",
            'parameter' => [
                'driverId' => $driverId,
                'payStatus' => 1,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            throw new DataException([500, '服务异常，换电记录获取失败-10040']);
        }
        if (!isset($result['content']['data'][0])){
            return false;
        }
        return $result['content']['data'];
    }

    // 获取骑手未支付换电单的次数和金额
    public function getUnpaidInfo($driverId)
    {
        $res = ChargingOrder::query()
            ->columns('COUNT(id) as count, SUM(amount) as amount')
            ->where('pay_status = 1 and is_delete=0')
            ->andWhere('driver_id = :driver_id:', ['driver_id'=>$driverId])
            ->execute()
            ->toArray();
        $info = $res[0];
        if (!empty($info['amount'])) $info['amount'] = round($info['amount']/10000, 2);
        return $info;
    }

    // 获取门店换电价格
    public function getStoreChargingPrice($storeId)
    {
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10049",
            'parameter' => [
                'storeId' => $storeId,
            ]
        ],"post");
        // 失败返回
        if (200 != $result['statusCode']) {
            throw new DataException([500, '服务异常，换电记录获取失败-10040']);
        }
        // null 为未设置
        $price = $result['content']['price'];
        if ($price){
            $price = round($price/10000, 2);
        }
        return $price;
    }

    // 获取换电柜换电价格
    public function getChargingPriceByQRCode($QRCode, $ver)
    {
        $storeId = null;
        $cabinet = false;
        if (1 == $ver){
            $cabinet = Cabinet::arrFindFirst([
                'qrcode' => $QRCode
            ]);
        }
        if (2 == $ver){
            $cabinet = ChargingManage::arrFindFirst([
                'qrcode' => $QRCode
            ]);
        }
        if (false == $cabinet){
            throw new DataException([500, '未查询到换电柜信息']);
        }
        $storeId = $cabinet->store_id;
        return $this->getStoreChargingPrice($storeId);
    }
}

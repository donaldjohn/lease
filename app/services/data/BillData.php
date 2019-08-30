<?php
namespace app\services\data;
use app\common\errors\DataException;
use Phalcon\Paginator\Adapter\QueryBuilder;


class BillData extends BaseData
{
    public $meta = null;
    /**
     * 获取单条支付单信息 通过支付单编号
     * @param $sn 支付单编号
     * @param bool $Convert 是否转换金额
     * @return mixed 返回账单数据
     * @throws DataException
     */
    public function getAppBillBySn($sn, $Convert=true)
    {
        $bills = $this->getAppBills([
            'businessSn' => $sn,
            // 1:通过骑手 2:通过支付单编号
            'type' => 2,
        ], $Convert);
        if (!isset($bills[0]))
            throw new DataException([500, "未能获取到支付单信息"]);
        return $bills[0];
    }

    public function getAppBillByDriver($driverId, $pageSize=null, $pageNum=null)
    {
        $data = [
            'driverId' => $driverId,
            // 1:通过骑手 2:通过支付单编号
            'type' => 1,
        ];
        if (!is_null($pageSize)) $data['pageSize'] = $pageSize;
        if (!is_null($pageNum)) $data['pageNum'] = $pageNum;
        $bills = $this->getAppBills($data);
        return $bills;
    }

    /**
     * 获取APP需要的账单列表
     * @param $condition 请求微服务数据
     * @param bool $Convert 是否转换金额
     * @return array 返回账单数据
     */
    public function getAppBills($condition, $Convert=true)
    {
        /*
        $condition = [
            'driverId' => 1,
            'businessSn' => '45',
            // 1:通过骑手 2:通过支付单编号
            'type' => '1',
        ];*/
        // 获取支付单详情列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10025",
            'parameter' => $condition,
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return [];
        }
        $data = $result['content']['data'];
        // 兼容旧调用，分页信息存至对象
        $this->meta = $result['content']['pageInfo'] ?? null;
        $bills = $data;
        // 转换金额数据
        if (true === $Convert){
            $bills = [];
            // 金额字段集
            $moneyFields = ['amount', 'totalAmount', 'packageDeposit', 'packageRent', 'productRent'];
            foreach ($data as $bill){
                    // 处理金额数据
                    foreach ($bill as $k => $v){
                        foreach ($moneyFields as $field){
                            if (isset($v[$field])){
                                $bill[$k][$field] = round($v[$field]/10000, 2);
                            }
                        }
                    }
                    $bills[] = $bill;
            }
        }
        return $bills;
    }

    /**
     * 查询骑手是否有未支付账单【单条】
     * @param $driverId
     * @return bool
     */
    public function getUnpaidBillByDriverId($driverId)
    {
        $bill =  $this->modelsManager->createBuilder()
            ->columns('b.*')
            ->addfrom('app\models\order\ServiceContract','s')
            ->where('s.driver_id = :driver_id:', ['driver_id' => $driverId])
            ->join('app\models\order\PayBill', 'b.service_contract_id = s.id','b')
            // 支付单有效 && 未支付
            ->andWhere('b.is_delete=0 and b.pay_status=1')
            ->orderBy('b.id ASC')
            ->getQuery()
            ->execute()
            ->getFirst();
        if ($bill){
            return $bill->toArray();
        }
        return false;
    }


}

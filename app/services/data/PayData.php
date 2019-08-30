<?php
namespace app\services\data;

use app\common\errors\DataException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use app\models\order\PayRecord;

// 支付类
class PayData extends BaseData
{
    // 支付渠道类型
    const AliPayType = 1;
    const WxPayType = 2;

    // 支付成功处理
    public function PaySuccess($businessSn, $payRecordSn, $amount, $payTime, $payType)
    {
        // 如果支付已被系统处理，直接响应成功
        if ($this->isAlreadyPay($businessSn, $payRecordSn)){
            return true;
        }
        // 转换金额
        $amount = $this->conversionAmount($amount, $payType);
        // 调用微服务接口处理支付回调
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10023",
            'parameter' => [
                // 商户订单号
                'businessSn' => $businessSn,
                // 支付宝流水号
                'payRecordSn' => $payRecordSn,
                // 订单金额
                'amount' => $amount,
                // 支付时间
                'payTime' => $payTime,
                // 支付类型 1 支付宝， 2 微信支付
                'payType' => $payType,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return false;
        }
        // 将相关车辆从锁车队列删除
        (new VehicleData())->delLockVehicleByBusinessSn($businessSn);
        // 关闭其它渠道支付单
        $this->ClosePay($businessSn, $payType);
        return true;
    }

    /**
     * 退款成功处理
     * @param $returnBillSn 退款单号
     * @param $businessSn 支付单号
     * @param $payRecordSn 支付流水号
     * @param $returnAmount 退款金额
     * @param $returnTime 退款时间
     * @param $payType 支付渠道类型
     * @return bool
     * @throws DataException
     */
    public function RefundSuccess($returnBillSn, $businessSn, $payRecordSn, $returnAmount, $returnTime, $payType)
    {
        // 处理入库金额
        $returnAmount = $this->conversionAmount($returnAmount, $payType);
        // 调用微服务接口处理退款回调
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10018",
            'parameter' => [
                // 退款单号
                'returnBillSn' => $returnBillSn,
                // 商户订单号
                'businessSn' => $businessSn,
                // 微信流水号
                'payRecordSn' => $payRecordSn,
                // 订单退款金额
                'amount' => $returnAmount,
                // 退款时间
                'returnTime' => $returnTime,
                // 支付类型 1 支付宝， 2 微信支付
                'payType' => $payType,
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return false;
        }
        return true;
    }

    /**
     * 关闭交易
     * @param $businessSn // 商户支付单号
     * @param null $PayType // 已支付类型
     * @return bool
     */
    public function ClosePay($businessSn, $PayType=null)
    {
        // 微信支付失败不会产生待付账单
        $status = true;
        // 关闭支付宝账单
        if ($PayType != self::AliPayType){
            $status = (new AlipayData())->close($businessSn) && $status;
        }
        return $status;
    }
    // 对交易进行退款
    public function Refund($businessSn, $refundAmount, $payType, $returnBillSn=null, $payTotal=null)
    {
        if (self::WxPayType == $payType && is_null($payTotal)){
            throw new DataException([500, '微信退款必需传入账单金额']);
        }
        // 组装退款数据
        $order = [
            // 商户订单号
            'orderNo' => $businessSn,
            // 退款金额
            'refundAmount' => $refundAmount,
        ];
        // 退款单号，部分退款时必传
        if (!is_null($returnBillSn)){
            $order['returnBillSn'] = $returnBillSn;
        }
        // 支付金额，微信退款需要
        if (!is_null($payTotal)){
            $order['total'] = $payTotal;
        }
        // 发起退款
        switch ($payType){
            case self::AliPayType:
                (new AlipayData())->Refund($order);
                break;
            case self::WxPayType:
                (new WxpayData())->Refund($order);
                break;
        }
        return true;
    }

    /**
     * 查询支付单是否已被系统处理支付
     * @param $businessSn 账单编号
     * @param $payRecordSn 流水单号
     * @return bool|null
     */
    public function isAlreadyPay($businessSn, $payRecordSn)
    {
        $res = PayRecord::findFirst([
            'business_sn = :business_sn:',
            'bind' => [
                'business_sn' => $businessSn,
            ]
        ]);
        // 系统不存在当前支付单
        if (false===$res){
            return null;
        }
        // 如果支付单已关联到当前流水单，返回true
        if ($payRecordSn == $res->pay_record_sn){
            return true;
        }
        return false;
    }

    /**
     * 转换外部金额为数据库金额单位
     * @param $amount 外部金额
     * @param $payType 支付渠道类型
     * @return int 数据库金额
     * @throws DataException
     */
    private function conversionAmount($amount, $payType)
    {
        switch ($payType){
            case self::AliPayType :
                $amount = $amount*10000;
                break;
            case self::WxPayType :
                $amount = $amount*100;
                break;
            default :
                throw new DataException([500, '支付类型不支持']);
        }
        return (int)$amount;
    }

}
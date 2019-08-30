<?php
namespace app\services\data;
use app\common\errors\DataException;

use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use Yansongda\Pay\Exceptions\GatewayException;


// 微信支付类
class WxpayData extends BaseData
{
    /**
     * 发起微信app支付
     * @param $order
     * @param array $config
     * @return \Yansongda\Pay\Gateways\Wechat\AppGateway
     */
    public function StartAppPay($order,$config=[])
    {
        // 微信支付金额单位为分，字符串型
        $order['amount'] = (string) round($order['amount'] / 100) ;
        // 组装订单信息
        $order = [
            'out_trade_no' => $order['orderNo'],
            // 单位：分
            'total_fee' => $order['amount'],
            'body' => isset($order['title']) ? $order['title'] : '得威',
            'detail' => isset($order['body']) ? $order['body'] : ' ',
        ];
        // 获取公共参数配置，传参可覆盖
        $config = array_merge($this->WxPayConfig,$config);
        // 返回组装好的SDK数据
        return Pay::wechat($config)->app($order)->getContent();
    }

    /**
     * 发起退款
     * @param $order
     * @param array $config
     * @return bool|\Yansongda\Supports\Collection
     * @throws DataException
     * @throws GatewayException
     */
    public function Refund($order,$config=[])
    {
        // 获取公共参数配置，传参可覆盖
        $config = array_merge($this->WxPayConfig,$config);

        // 微信支付金额单位为分，字符串型
        $order['total'] = (string) (int) ($order['total'] / 100) ;
        $order['refundAmount'] = (string) (int) ($order['refundAmount'] / 100) ;
        // 组装订单信息
        $order = [
            // 商户订单号
            'out_trade_no' => $order['orderNo'],
            // APP/小程序订单退款必传 app/miniapp
            'type' => 'app',
            // 订单金额
            'total_fee' => $order['total'],
            // 退款金额
            'refund_fee' => $order['refundAmount'],
            // 退款单号
            'out_refund_no' => $order['returnBillSn'],
            // 退款描述
            'refund_desc' => '得威',
            // 回调地址
            'notify_url' => $config['refund_notify_url'],
        ];

        try{
            return Pay::wechat($config)->refund($order);
        } catch (GatewayException $e) {
            $ERRMsg = $e->raw['err_code_des'] ?? false;
            if ($ERRMsg){
                throw new DataException([500, $ERRMsg]);
            }
            throw $e;
        }
        return false;
    }
}

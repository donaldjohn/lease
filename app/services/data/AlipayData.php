<?php

namespace app\services\data;

use app\common\errors\DataException;

use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use Yansongda\Pay\Exceptions\GatewayException;


// 支付宝支付类
class AlipayData extends BaseData
{
    public $RefundERRCode = [
                'ACQ.SYSTEM_ERROR' => [
                    'tip' => '系统错误',
                    'help' => '请使用相同的参数再次调用',
                ],
                'ACQ.INVALID_PARAMETER' => [
                    'tip' => '参数无效',
                    'help' => '请求参数有错，重新检查请求后，再调用退款',
                ],
                'ACQ.SELLER_BALANCE_NOT_ENOUGH' => [
                    'tip' => '卖家余额不足',
                    'help' => '商户支付宝账户充值后重新发起退款即可',
                ],
                'ACQ.REFUND_AMT_NOT_EQUAL_TOTAL' => [
                    'tip' => '退款金额超限',
                    'help' => '检查退款金额是否正确，重新修改请求后，重新发起退款',
                ],
                'ACQ.REASON_TRADE_BEEN_FREEZEN' => [
                    'tip' => '请求退款的交易被冻结',
                    'help' => '联系支付宝小二，确认该笔交易的具体情况',
                ],
                'ACQ.TRADE_NOT_EXIST' => [
                    'tip' => '交易不存在',
                    'help' => '检查请求中的交易号和商户订单号是否正确，确认后重新发起',
                ],
                'ACQ.TRADE_HAS_FINISHED' => [
                    'tip' => '交易已完结',
                    'help' => '该交易已完结，不允许进行退款，确认请求的退款的交易信息是否正确',
                ],
                'ACQ.TRADE_STATUS_ERROR' => [
                    'tip' => '交易状态非法',
                    'help' => '查询交易，确认交易是否已经付款',
                ],
                'ACQ.DISCORDANT_REPEAT_REQUEST' => [
                    'tip' => '不一致的请求',
                    'help' => '检查该退款号是否已退过款或更换退款号重新发起请求',
                ],
                'ACQ.REASON_TRADE_REFUND_FEE_ERR' => [
                    'tip' => '退款金额无效',
                    'help' => '检查退款请求的金额是否正确',
                ],
                'ACQ.TRADE_NOT_ALLOW_REFUND' => [
                    'tip' => '当前交易不允许退款',
                    'help' => '检查当前交易的状态是否为交易成功状态以及签约的退款属性是否允许退款，确认后，重新发起请求',
                ],
                'ACQ.REFUND_FEE_ERROR' => [
                    'tip' => '交易退款金额有误',
                    'help' => '请检查传入的退款金额是否正确',
                ]
            ];

    /**
     * 发起支付宝APP支付
     * @param $order
     * @param array $config
     * @return \Yansongda\Pay\Gateways\Alipay\AppGateway
     */
    public function StartAppPay($order, $config = [])
    {
        // 支付宝支付金额单位为元, 字符串型
        $order['amount'] = (string)round($order['amount'] / 10000, 2);
        // 组装订单信息
        $order = [
            'out_trade_no' => $order['orderNo'],
            'total_amount' => $order['amount'],
            'subject' => isset($order['title']) ? $order['title'] : '得威',
            'body' => isset($order['body']) ? $order['body'] : ' ',
//            // 测试花呗通道
//            'extend_params' => [
//                'hb_fq_num' => '3',
//                'hb_fq_seller_percent' => '100',
//            ],
        ];
        // 获取公共参数配置，传参可覆盖
        $config = array_merge($this->AliPayConfig, $config);
        // 返回组装好的支付请求数据 字符串类型
        return Pay::alipay($config)->app($order)->getContent();
    }

    /**
     * 发起退款
     * @param $order
     * @param array $config
     * @return bool|\Yansongda\Supports\Collection
     * @throws DataException
     * @throws GatewayException
     */
    public function Refund($order, $config = [])
    {
        // 支付宝支付金额单位为元, 字符串型
        $order['refundAmount'] = (string)round($order['refundAmount'] / 10000, 2);
        // 组装订单信息
        $order = [
            // 商户订单号
            'out_trade_no' => $order['orderNo'],
            // 退款金额
            'refund_amount' => $order['refundAmount'],
            // 退款单号，部分退款时必传
            'out_request_no' => $order['returnBillSn'],
        ];
        // 获取公共参数配置，传参可覆盖
        $config = array_merge($this->AliPayConfig, $config);
        try{
            return Pay::alipay($config)->refund($order);
        } catch (GatewayException $e) {
            $ERRMsg = $e->raw['alipay_trade_refund_response']['sub_msg'] ?? false;
            if ($ERRMsg){
                throw new DataException([500, $ERRMsg]);
            }
            throw $e;
        }
        return false;
    }

    /**
     * 关闭支付宝交易【仅可关闭未支付交易，已支付交易需使用撤销接口】
     * @param $order
     * @param array $config
     * @return bool
     */
    public function Close($order, $config = [])
    {
        // 获取公共参数配置，传参可覆盖
        $config = array_merge($this->AliPayConfig, $config);
        try {
            // 关闭失败会抛异常
            $res = Pay::alipay($config)->close($order);
        } catch (GatewayException $e) {
            // 无此交易，成功返回
            if (isset($e->raw['alipay_trade_close_response'])
                && 'ACQ.TRADE_NOT_EXIST' == $e->raw['alipay_trade_close_response']['sub_code']) {
                return true;
            }
            return false;
        }
        // 记录日志
        $log = '【关闭支付宝订单成功】' . json_encode($res, JSON_UNESCAPED_UNICODE);
        $this->logger->info($log);
        return true;
    }

}

<?php
namespace app\modules\pay;

use app\common\library\ZuulApiService;
use app\modules\BaseController;
use Phalcon\Http\Response\Headers;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use app\services\data\VehicleData;
use app\services\data\PayData;

//支付宝测试
class AlipayController extends BaseController
{
    /**
     * 发起支付测试
     * @return mixed
     */
    public function StartAction($type)
    {
        $order = [
            'out_trade_no' => 'WEN_DEV'.time(),
            'total_amount' => '0.01',
            'subject' => '服务单'.rand(10010,20268).' '.rand(1,12).'月账单',
            'body' => '2018.3.'.rand(1,20).'~2018.4.'.rand(10,30).'账期',
        ];
        // 创建支付载体
        $alipay = Pay::alipay($this->AliPayConfig);
        // 选择支付类型
        switch ($type){
            case 'app':
                $alipay = $alipay->app($order);
                break;
            case 'web':
                $alipay = $alipay->web($order);
                break;
            default :
                $alipay = $alipay->wap($order);
        }
        // 返回交易信息
        return $alipay->send();
    }

    /**
     * 异步回调通知处理
     */
    public function AsyncAction()
    {
        $alipay = Pay::alipay($this->AliPayConfig);

        try{
            // 验证签名并返回数据，验证失败则抛出异常
            $data = $alipay->verify();
        } catch (\Throwable $e) {
            Log::debug('Alipay 回调异常：'.$e->getMessage());
            // $e->getMessage();
            echo 'fail';
            return;
        }
        $PayData = new PayData();
        // TODO:如果是退款成功回调
        if (isset($data->gmt_refund)){
            // 没有退款单号，服务端无法处理
            if (!isset($data->out_biz_no) || is_null($data->out_biz_no)){
                Log::debug('Alipay 无退款单号退款回调：', $data->all());
                return $alipay->success()->send();
            }
            // 处理退款结果
            $bol = $PayData->RefundSuccess($data->out_biz_no, $data->out_trade_no, $data->trade_no, $data->refund_fee, strtotime($data->gmt_refund), PayData::AliPayType);
            // 通知支付宝处理结果
            return $bol ? $alipay->success()->send() : 'fail';
        }
        // TODO:如果是付款成功回调
        if (isset($data->receipt_amount)){
            // 处理支付结果
            $bol = $PayData->PaySuccess($data->out_trade_no, $data->trade_no, $data->total_amount, strtotime($data->gmt_payment), PayData::AliPayType);
            // 通知支付宝处理结果
            return $bol ? $alipay->success()->send() : 'fail';
        }
        Log::debug('Alipay 其它异步回调：', $data->all());
        // 通知支付宝处理成功
        return $alipay->success()->send();
    }

}

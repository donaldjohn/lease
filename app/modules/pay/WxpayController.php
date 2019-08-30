<?php
namespace app\modules\pay;

use app\common\library\ZuulApiService;
use app\modules\BaseController;
use Phalcon\Http\Response\Headers;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use function foo\func;
use Symfony\Component\HttpFoundation\Request;
use Yansongda\Supports\Collection;
use Yansongda\Pay\Exceptions\InvalidSignException;
use Yansongda\Pay\Gateways\Wechat\Support;
use app\services\data\PayData;
use app\services\data\VehicleData;

//微信测试
class WxpayController extends BaseController
{
    /**
     * 发起支付测试
     * @return mixed
     */
    public function StartAction($type)
    {
        $order = [
            'out_trade_no' => 'WEN_DEV'.time(),
            // 单位：分
            'total_fee' => rand(1,16),
            'body' => '服务单'.rand(10010,20268).' '.rand(1,12).'月账单',
            'detail' => '2018.3.'.rand(1,20).'~2018.4.'.rand(10,30).'账期',
        ];
        // 初始化支付配置
        $wechat = Pay::wechat($this->WxPayConfig);
        switch ($type){
            case 'app':
                return $wechat->app($order)->send();
                break;
            case 'wap':
                return $wechat->wap($order)->send();
                break;
            case 'qr':
                // 返回二维码内容文本
                return $wechat->scan($order)->code_url;
                break;
            default :
                return json_encode(['msg'=>'你要干啥？']);
        }
    }


    /**
     * 支付异步回调通知处理
     */
    public function AsyncAction()
    {
        // 初始化支付配置
        $wechat = Pay::wechat($this->WxPayConfig);
        try{
            // 验签并返回数据，失败会抛出异常
            $data = $wechat->verify();
        } catch (\Throwable $e) {
            Log::debug('微信 异步处理异常: '.$e->getMessage());
            return ;
        }
        $PayData = new PayData();
        // TODO:如果是支付回调
        if (isset($data->time_end)){
            // 处理支付信息
            $bol = $PayData->PaySuccess($data->out_trade_no, $data->transaction_id, $data->total_fee, strtotime($data->time_end), PayData::WxPayType);
            // 通知微信处理结果
            return $bol ? $wechat->success()->send() : 'fail';
        }
        Log::debug('微信 其它异步回调：', $data->all());
        // 通知微信处理成功
        return $wechat->success()->send();
    }

    /**
     * 退款异步回调通知处理
     */
    public function RefundasyncAction()
    {
        // 初始化支付配置
        $wechat = Pay::wechat($this->WxPayConfig);
        try{
            // 验签并返回数据，失败会抛出异常
//            $data = $wechat->verify(null, true);
            $data = $this->WxVerify(null, true);
        } catch (\Throwable $e) {
            Log::debug('微信 退款异步处理异常: '.$e->getMessage());
            return ;
        }
        // 失败退款不入库
        if ('SUCCESS' != $data->refund_status){
            Log::error('微信支付 退款失败回调：  ', $data->all());
            return $wechat->success()->send();
        }
        $PayData = new PayData();
        Log::debug('微信 退款回调：', $data->all());
        // 处理退款结果
        $bol = $PayData->RefundSuccess($data->out_refund_no, $data->out_trade_no, $data->transaction_id, $data->refund_fee, strtotime($data->success_time), PayData::WxPayType);
        return $bol ? $wechat->success()->send() : 'fail';
    }

    private function WxVerify($content = null, $refund = false): Collection
    {
        $content = $content ?? Request::createFromGlobals()->getContent();

        $data = Support::fromXml($content);

        if ($refund){
            $data = array_merge($data, Support::fromXml(
                openssl_decrypt(base64_decode($data['req_info']), 'AES-256-ECB', md5($this->WxPayConfig['key']), OPENSSL_RAW_DATA)
            ));
            unset($data['req_info']);
        }

        Log::debug('Receive Wechat Request:', $data);

        if ($refund || Support::generateSign($data, $this->config->get('key')) === $data['sign']) {
            return new Collection($data);
        }

        Log::warning('Wechat Sign Verify FAILED', $data);

        throw new InvalidSignException('Wechat Sign Verify FAILED', $data);
    }

}

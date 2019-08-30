<?php
use Phalcon\Cli\Task;
use app\models\order\PayBill;
use app\models\order\Deposit;
use app\models\order\VehicleRentOrder;
use app\models\order\ServiceContract;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Exceptions\GatewayException;

class PaybilltimeoutTask extends Task
{
    const UnDel = 0; // 未删除
    const IsDel = 1; // 已删除

    const UnPay = 1; // 未支付
    const IsPay = 2; // 已支付
    const ClosePay = 3; // 已关闭

    const ServiceUnPay = 1; // 服务单未支付
    const ServiceOver = 5; // 服务单已结束

    public function TimeOutCloseAction()
    {
        $this->log->info('TimeOutClose Start');
        // 查询超时支付单
        $bills = PayBill::find([
            'pay_status = :pay_status: and is_delete = :is_delete: and create_time < :time:',
            'bind' => [
                'pay_status' => self::UnPay,
                'is_delete' => self::UnDel,
                'time' => time()-900,
            ],
        ]);
        $log = '';
        foreach ($bills as $bill){
            try{
                $log .= PHP_EOL;
                $log .= '处理支付单:'.$bill->business_sn.PHP_EOL;
                // 请求支付宝关闭交易
                $bol = $this->CloseAliPayBill($bill->business_sn);
                if (false===$bol){
                    $log .= '关闭支付宝交易失败:'.$bill->business_sn.PHP_EOL;
                    continue;
                }
                $bol1 = $this->CloseWechatBill($bill->business_sn);
                if (false===$bol1) {
                    $log .= '关闭微信交易失败:'.$bill->business_sn.PHP_EOL;
                    continue;
                }
                // 开启事务
                $this->dw_order->begin();
                // 关闭支付单
                $bill->pay_status = 3;
                $bill->update_time = time();
                $bol = $bill->save();
                if (false===$bol){
                    $log .= '关闭支付单失败:'.$bill->business_sn.PHP_EOL;
                    $this->dw_order->rollback();
                    continue;
                }
                // 关闭关联押金单
                $bol = $this->CloseDepositByPayBill($bill->id);
                if (false===$bol){
                    $log .= '关闭押金单失败:'.$bill->id.PHP_EOL;
                    $this->dw_order->rollback();
                    continue;
                }
                // 关闭关联租车单
                $bol = $this->CloseRentOrderByPayBill($bill->id);
                if (false===$bol){
                    $log .= '关闭租车单失败:'.$bill->id.PHP_EOL;
                    $this->dw_order->rollback();
                    continue;
                }
                // 如果待支付套餐，关闭套餐
                $bol = $this->CloseUnpayServiceContract($bill->service_contract_id);
                if (false===$bol){
                    $log .= '关闭套餐失败:'.$bol.PHP_EOL;
                    $this->dw_order->rollback();
                    continue;
                }
                // 提交事务
                $this->dw_order->commit();
                $log .= '关闭支付单成功:'.$bill->business_sn;
            }catch (\Exception $e){
                $this->log->info($e->getMessage());
            }
        }
        $this->log->info($log);
        $this->log->info('TimeOutClose End');
    }

    // 关闭支付宝订单
    public function CloseAliPayBill($orderSN)
    {
        try{
            // 关闭失败会抛异常
            Pay::alipay($this->AliPayConfig)->close($orderSN);
        }catch (GatewayException $e){
            $this->log->info(json_encode($e, JSON_UNESCAPED_UNICODE));
            // 无此交易，成功返回
            if (isset($e->raw['alipay_trade_close_response'])
                && 'ACQ.TRADE_NOT_EXIST' == $e->raw['alipay_trade_close_response']['sub_code']){
                return true;
            }
            return false;
        }
        return true;
    }


    // 关闭支付宝订单
    public function CloseWechatBill($orderSN)
    {
        try{
            // 关闭失败会抛异常
            Pay::wechat($this->WxPayConfig)->close($orderSN);
//          if ($result->get("return_code") == "false") {
//              $this->log->info(json_encode($result, JSON_UNESCAPED_UNICODE));
//              return false;
//          }
        }catch (GatewayException $e){
            $this->log->info(json_encode($e, JSON_UNESCAPED_UNICODE));
//            // 无此交易，成功返回
//            if (isset($e->raw['wechat_trade_close_response'])
//                && 'ACQ.TRADE_NOT_EXIST' == $e->raw['alipay_trade_close_response']['sub_code']){
//                return true;
//            }
            return false;
        }
        return true;
    }

    // 关闭押金单
    public function CloseDepositByPayBill($payBillId)
    {
        $deposit = Deposit::findFirst([
            'pay_bill_id = :pay_bill_id:',
            'bind' => [
                'pay_bill_id' => $payBillId,
            ]
        ]);
        if (false===$deposit){
            return null;
        }
        $deposit->status = self::ClosePay;
        $deposit->update_time = time();
        return $deposit->save();
    }

    // 关闭租金单
    public function CloseRentOrderByPayBill($payBillId)
    {
        $RentOrder = VehicleRentOrder::findFirst([
            'pay_bill_id = :pay_bill_id:',
            'bind' => [
                'pay_bill_id' => $payBillId,
            ]
        ]);
        if (false===$RentOrder){
            return null;
        }
        $RentOrder->pay_status = self::ClosePay;
        $RentOrder->update_time = time();
        return $RentOrder->save();
    }

    // 关闭未支付的服务单
    public function CloseUnpayServiceContract($service_contract_id)
    {
        $ServiceContract = ServiceContract::findFirst([
            'id = :service_contract_id: and status = :status:',
            'bind' => [
                'service_contract_id' => $service_contract_id,
                'status' => self::ServiceUnPay,
            ]
        ]);
        if (false===$ServiceContract){
            return null;
        }
        $ServiceContract->status = self::ServiceOver;
        $ServiceContract->is_delete = self::IsDel;
        return $ServiceContract->save();
    }

}
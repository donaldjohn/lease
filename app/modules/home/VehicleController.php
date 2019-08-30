<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/6/15
 * Time: 14:00
 */
namespace app\modules\home;

use app\common\errors\DataException;
use app\modules\BaseController;
use Phalcon\Config;

class VehicleController extends BaseController
{
    /**
     * 验证数据
     */
    public function initialize()
    {
        $request = $this->request;
        $data = $request->getJsonRawBody(true);
        if (!isset($data['channel'])) {
            throw new DataException([500,'渠道参数未传']);
        }
        $channel = $data['channel'];
        if (!isset($this->config->$channel->key)) {
            throw new DataException([500,'渠道错误']);
        }
        if (!isset($data['timestamp']) || ($data['timestamp'] - time() > 600)) {
            throw new DataException([500,'超时']);
        }
        $flag = $this->CheckSign($data, $this->config->$channel->key);
        if (!$flag) {
            throw new DataException([500,'签名失败']);
        }
    }

    /**
     * 安骑锁车/解锁及电门开关的回调接口
     */
    public function CallbackAction()
    {
        $data = $this->request->getJsonRawBody();
        if (!$data->udid) {
            return $this->toError(500,"未传设备ID");
        }
        if (!$data->status || !in_array($data->status, [0,1])) {
            return $this->toError(500,"传入状态参数错误");
        }
        if (!$data->opt || !in_array($data->opt, ['lock','switch'])) {
            return $this->toError(500,"传入opt参数错误");
        }
        $data = [
            'parameter' => ['udid' => $data->udid, 'status' => $data->status, 'opt' => $data->opt],
            'code' => '60201',
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'], $result['msg']);
        };
        return $this->toSuccess();
    }
    /**
     * 验证签名
     * @param $data
     * @param $key
     * @return bool
     */
    public function CheckSign($data, $key)
    {
        if (!isset($data['sign'])) {
            return false;
        }
        $sign = $data['sign'];
        unset($data['sign']);
        $check_sign = $this->CreateSign($data, $key);
        if ($check_sign <> $sign) {
            return false;
        }
        return true;
    }
    /**
     * 生成sign
     * 按照 ASII排序+key md5加密生成sign
     * @param $data
     * @param $secret
     * @return string
     */
    public function CreateSign($data, $secret)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            $str = $str.$val;
        }
        $sign = md5($str.$secret);
        return $sign;
    }
}
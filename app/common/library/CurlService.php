<?php
/**
 * Created by PhpStorm.
 * User: yechuzheng
 * Date: 2017/10/27
 * Time: 下午2:26
 */

namespace app\common\library;


use app\common\errors\CurlException;
use app\common\errors\MicroException;
use app\common\logger\business\Message;
use app\services\auth\AuthService;
use Phalcon\Di\Injectable;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Formatter\Line;

class CurlService extends Injectable
{
    public function httpRequest($url,$params = array(),$type = "get", $EnableLog=true)
    {
        // 兼容纯url
        if (is_string($url)){
            $url = [$url, '00000'];
        }
        $raw_token = $this->request->getHeader(AuthService::JWT_HEADER_KEY);
        $userHeader = [
            AuthService::JWT_HEADER_KEY => $raw_token,
        ];
//        $t1 = microtime(true);
        $result = $this->sendCurl($url[0],$params,$type, $userHeader, $EnableLog);
//        $t2 = microtime(true);
        /**
         * 注入日志到业务日志;
         */
//        $message = new Message();
//        $message->level = 'info';
//        $message->bizModuleCode = $url[1];
//        $message->timestamp = time();
//        $message->requestId = $this->app->getRequestId();
//        $message->desc = '业务调用能力日志';
//        $message->inParameter = json_encode($params,JSON_UNESCAPED_UNICODE);
//        $message->outParameter = json_encode($result,JSON_UNESCAPED_UNICODE);
//        $message->customField = round(($t2-$t1)*1000, 2).'ms';
//        $this->busLogger->setMessage($message);
        /**
         * 返回数据判断是否存在status
         * 存在status即服务层出现问题
         */
        if (isset($result['status']))
            throw new MicroException([500,'能力层出现问题：'.$url[0]. json_encode($params).$result['message']]);
        return $result;
    }

    public function sendCurl($url,$params = array(),$type = "GET", $userHeader=[], $EnableLog=true)
    {
        $type = strtoupper($type);
        $code = $params['code'] ?? ''; // 超时抛异常用
        $ch = curl_init();
        foreach ($userHeader as $k => $v){
            if (!is_numeric($k)) $userHeader[$k] = $k.':'.$v;
        }
        $headers = array_values($userHeader);
        $headers[] = 'Content-type:application/json';

        if ('GET'==$type && is_array($params) && count($params)>0){
            // 处理GET参数
            $queryStr = '?';
            foreach ($params as $key => $val){
                $params[$key] = $key.'='.$val;
            }
            $queryStr .= implode('&', $params);
            $url .= $queryStr;
        }
        // 处理参数
        if(is_object($params) || is_array($params)){
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLINFO_HEADER_OUT,true);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT_MS, 5000);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 6000);
        // 关闭ssl证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // curl计时
        $t1 = microtime(true);
        switch (strtoupper($type)){
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
                break;
            case "PUT" :
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
                break;
            case "DELETE":
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }

        $file_contents = curl_exec($ch);//获得返回值
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        //$code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 获取返回的状态码
        curl_close($ch);
        // curl耗时
        $curlTime = (int) ((microtime(true)-$t1)*1000);
        // 记录至全局变量
        $GLOBALS['RunTimeInfo']['cN'] += 1;
        $GLOBALS['RunTimeInfo']['cT'] += $curlTime;

        $log = '===================curl==================='. $curlTime .'ms';
        $log .= PHP_EOL.$url;
        $log .= PHP_EOL.$params;
        $log .= PHP_EOL.$file_contents;

        // 是否使用日志
        if ($EnableLog){
            $this->logger->info($log);
        }
        /**
         * 数据获取失败curl相关问题
         */
        if ($curl_errno > 0) {
            $log .= PHP_EOL."curl_errno: {$curl_errno} err: {$curl_error}";
            $this->logger->error($log);
            $uri = strstr($url, '0/');
            throw new CurlException([ HttpService::CURL_TIME_OUT[0],$curl_error.' '.$uri.' '.$code]);
        }
        return json_decode($file_contents,true);
    }

    //使用curl_muti_init
    public function httpMutiRequest($url= array() ,$params = array(),$type = "get")
    {

    }

}
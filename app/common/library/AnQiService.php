<?php
/**
 * 锁车\解锁接口
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/22
 * Time: 16:14
 */
namespace app\common\library;

class AnQiService
{
    const LOCK = 1;
    const UNLOCK = 0;
    protected $url = "http://api.vipcare.com";       //接口地址
    protected $secret = "XohZebcUf7EEoZCKGycMDPXT";  //接口秘钥
    protected $udid = "";                            //设备ID
    static protected $model;
    public static $code_arr = [                      //返回code状态
        200  => "操作成功",
        8002 => "时间校验失败",
        8003 => "不支持设备",
        2001 => "参数错误",
        1005 => "无效的设备码",
        8004 => "签名错误",
        8001 => "服务异常",
        3022 =>  "设备离线",
    ];
    public static $lock_operation = [
        self::UNLOCK => "解锁",
        self::LOCK => "锁车"
    ];
    public static $lock_status = [
        10 => "未锁",
        20 => "已锁"
    ];

    public function __construct($udid = "")
    {
        $this->udid = $udid;
    }

    /**
     * 锁车接口
     */
    public function superLock($lock = self::UNLOCK)
    {
        $time = time();
        $param = [
            "timestamp" => $time,
            "sign" => $this->getSign($time),
            "lock" => $lock,
            "udid" => $this->udid,
        ];
        $curl = new CurlService();
        $url = $this->getUrl("/tool/superLock", $param);
        $result  = $curl->httpRequest($url, '', "get");            //发送请求
        //发送请求
        $arr["code"] = $result['code'];
        $arr["msg"] = isset($result['msg'])?$result['msg']:self::$code_arr[$result['code']];
        return $arr;
    }

    /**
     * 查询状态接口
     */
    public function getLockStatus()
    {
        /**
         * 组合URL
         */
        $time = time();
        $param = [
            "timestamp" => $time,
            "sign" => $this->getSign($time),
            "udid" => $this->udid
        ];
        $curl = new CurlService();
        $result  = $curl->httpRequest($this->url."/tool/getLockStatus", $param, "GET");
        $arr["code"] = $result['code'];
        $arr["status"] = $result['lock_status'];
        $arr["msg"] = isset($result['msg'])?$result['msg']:self::$code_arr[$result['code']];
        $arr["status_name"] = self::$lock_status[$result['lock_status']];
        return $arr;
    }

    public function getSign($time)
    {
        return md5($time . $this->secret);
    }

    public static function instance($udid = "")
    {
        self::$model = new static($udid);
        return self::$model;
    }
    public function getUrl($uri, $param)
    {
        return $this->url . $uri . "?" . http_build_query($param);
    }
}
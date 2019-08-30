<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 17:37
 */
namespace app\common\library;


class QqMapService
{
    protected static $instance;
    const BASE_URL = "http://apis.map.qq.com/ws/geocoder/v1/";
    const KEY = "AIYBZ-UYOWP-CLGDG-VM5HL-2OGFH-ZEFXZ";


    public static function  getLocation($ip) {
        $url = self::getUrl($ip);
        $data= (new CurlService())->sendCurl($url);//print_r($data['result']['ad_info']['adcode']);exit;
        if($data['status'] != 0) {
            return false;
        } else {
            $result = $data['result'];
            $location = [
                "region_id"  => $result['ad_info']['adcode'],
            ];
            return $location;
        }
    }

    public static function getUrl($ip)
    {
        $ip["key"] = self::KEY;
        $query = "";
        foreach ($ip as $key=>$item){
            $query .= $key."=".$item."&";
        }
        $query = substr($query,0,strlen($query)-1);
        return self::BASE_URL."?".$query;
    }
}
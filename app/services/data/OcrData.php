<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: OcrData.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\services\data;

use app\common\errors\DataException;

use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use Yansongda\Pay\Exceptions\GatewayException;


/**
 * Class OcrData
 * @package app\services\data
 *
 *  阿里云OCR 图像识别
 */
class OcrData extends BaseData
{

    const OCR_VIN_URL = "https://vin.market.alicloudapi.com/api/predict/ocr_vin";
    const OCR_GENERAL_URL = "https://tysbgpu.market.alicloudapi.com/api/predict/ocr_general";
// 将文件做base64编码
//$baseStr = base64_encode(file_get_contents($file->getTempName()));
    public function GENERAL($appCode,$fileBase) {
        $url = self::OCR_GENERAL_URL;
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appCode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $bodys = [];
        $bodys['image'] = $fileBase;
        $configure['min_size'] = 16;
        $configure['output_prob'] = true;
        $bodys['configure'] = $configure;
        $result = $this->curl->sendCurl($url,$bodys,'POST',$headers,true);
        if ($result['success'] != true) {
            return false;
        } else {
            if (isset($result['ret'] )) {
                $words = '';
                foreach($result['ret'] as $item ) {
                    $words = $words.$item['word'];
                }
                return $words;
            } else {
                return false;
            }
        }
    }


    public function Vin($appCode,$fileBase) {
        $url = self::OCR_VIN_URL;
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appCode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $bodys = [];
        $bodys['image'] = $fileBase;
//        $configure['min_size'] = 16;
//        $configure['output_prob'] = true;
        $bodys['configure'] = [];
        $result = $this->curl->sendCurl($url,$bodys,'POST',$headers,true);
        if ($result['success'] != true) {
            return false;
        } else {
            if (!isset($result['vin'])) {
                return false;
            }
            return $result['vin'];
        }
    }

}
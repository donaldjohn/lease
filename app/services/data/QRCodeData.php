<?php
namespace app\services\data;
use app\common\errors\DataException;




class QRCodeData extends BaseData
{
    const ERWEIMA_URL = "http://lease.dev.e-dewin.com";

    // 生成二维码内容
    public function getQRCodeContent($bianhao)
    {
        return self::ERWEIMA_URL . "?erweima_id=".$bianhao;
    }
}

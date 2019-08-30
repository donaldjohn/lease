<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/6/15
 * Time: 14:00
 */
namespace app\modules\home;

use app\modules\BaseController;

class FileController extends BaseController
{
    /**
     * 上传文件base64编码
     */
    public function UpbaseAction()
    {
        // 是否有文件上传
        if (!$this->request->hasFiles()) {
            return $this->toError(500,'未收到文件');
        }
        // 获取文件
        $file = $this->request->getUploadedFiles()[0];
        if (0==$file->getSize() || $file->getSize()/1024 > 2048){
            return $this->toError(500,'文件大小不支持，请选择2M以内的文件');
        }
        // 将文件做base64编码
        $baseStr = base64_encode(file_get_contents($file->getTempName()));
        // 传输存储文件
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10030",
            'parameter' => [
                'suffiex' => pathinfo($file->getName(), PATHINFO_EXTENSION),
                'fileStr' => $baseStr,
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError(500, '文件服务异常');
        }
        // 成功返回地址
        return $this->toSuccess([
            'fileURI' => 'http://'.$result['content']['address'],
        ]);
    }
}
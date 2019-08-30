<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/16
 * Time: 19:33
 */
namespace app\modules\qrcode;

use app\common\errors\AppException;
use app\common\library\PhpExcel;

use app\modules\BaseController;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

class IndexController extends BaseController
{
//    const ERWEIMA_URL = "http://lease.dev.e-dewin.com";
    /**
     * 二维码的状态值
     * @var array
     */
    static $status_list = [
        1 => "未发放",
        2 => "已发放",
        3 => "已激活",
    ];
    /**
     * 二维码列表页面
     */
    public function IndexAction()
    {
        try {
            $pageSize = $this->request->getQuery('pageSize', "int", 20);
            $pageNum = $this->request->getQuery('pageNum', "int", 1);
            $status = $this->request->getQuery('status', "int", '');
            $bianhao = $this->request->getQuery('bianhao');
            $createTimeStart = $this->request->getQuery('createTimeStart');
            $createTimeEnd = $this->request->getQuery('createTimeEnd');
            $activeTimeStart = $this->request->getQuery('activeTimeStart');
            $activeTimeEnd = $this->request->getQuery('activeTimeEnd');
            $pram = [
                "pageSize" => (int)$pageSize,
                "pageNum" => (int)$pageNum,
                "status" => $status,
                "bianhao" => $bianhao,
                "createTimeStart" => $createTimeStart ? strtotime($createTimeStart) : '',
                "createTimeEnd" => $createTimeEnd ? strtotime($createTimeEnd) : '',
                "activeTimeStart" => $activeTimeStart ? strtotime($activeTimeStart) : '',
                "activeTimeEnd" => $activeTimeEnd ? strtotime($activeTimeEnd) : '',
            ];
            $pram = array_filter($pram);
            $data = [
                'parameter' => $pram,
                'code' => '60004',
            ];
            //调用微服务接口获取数据
            $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
            if ($result['statusCode'] <> 200) {
                return $this->toError($result['statusCode'],$result['msg']);
            };
            $item = [];
            foreach ($result['content']['qrCodeVos'] as $key => $val) {
                $val['createTime'] = date('Y-m-d H:i:s', $val['createTime']);
                $item[] = $val;
            }
            return $this->toSuccess($item, $result['content']['pageInfo']);
        } catch (AppException $e) {
            return $this->toError('500', $e->getMessage());
        }
    }
    /**
     * 创建二维码
     */
    public function CreateAction()
    {
        if (!isset($this->content->amount)) {
            return $this->toError('500', '未填写发放数量');
        }
        $Amount = intval($this->content->amount);
        $params = [
            "userId" => $this->authed->userId,//暂时写个定值
            "amount" => $Amount,
        ];
        $put_params = ["code" => "60001","parameter" => $params];
        $request = $this->curl->httpRequest($this->Zuul->vehicle, $put_params, "POST");
        if ($request['statusCode'] <> 200) {
            return $this->toError($request['statusCode'], $request['msg']);
        }
        return $this->toSuccess();
    }

    /**
     * 发放二维码
     */
    public function ExportAction()
    {
        $amount = intval($this->request->getQuery('amount'));
        if ($amount <= 0) {
            return $this->toError('500', '未填写发放条数');
        }
        $params = [
            "userId" => isset($this->authed->userId) ? $this->authed->userId : '42',//暂时写个定值
            "amount" => $amount,
        ];
        $put_params = ["code" => "60003","parameter" => $params];
        $request = $this->curl->httpRequest($this->Zuul->vehicle, $put_params, "POST");
        if ($request['statusCode'] <> 200) {
            return $this->toError($request['statusCode'], $request['msg']);
        }
        $sheetRow = ['批次号','二维码编号', '二维码内容', '状态', '创建时间',];//表头
        $label = ['createBatch','bianhao', 'content', 'status', 'createTime',];
        if ($request['statusCode'] <> 200) {
            return $this->toError($request['statusCode'], $request['msg']);
        }
        $data = [];
        $i = 0;
        foreach ($request['content']['qrCodeList'] as $key => $val) {
            foreach ($label as $k => $v) {
                if ($v == 'createTime') {
                    $data[$i][] = date("Y-m-d H:i:s", $val[$v]);
                } elseif ($v == 'status') {
                    $data[$i][] = self::$status_list[$val[$v]];
                } elseif ($v == 'content') {
                    $data[$i][] =  self::ERWEIMA_URL."?erweima_id=" . $val['bianhao'];
                } else {
                    $data[$i][] = $val[$v];
                }
            }
            $i++;
            unset($request['content']['qrCodeList'][$key]);
        }
        PhpExcel::downloadExcel('二维码', $sheetRow, $data);
    }

    /**
     *获取二维码未发放数量
     */
    public function CountAction()
    {
        $put_params = ["code" => "60002"];
        $request = $this->curl->httpRequest($this->Zuul->vehicle, $put_params, "POST");
        if ($request['statusCode'] <> 200) {
            return $this->toError($request['statusCode'], $request['msg']);
        }
        return $this->toSuccess($request['content']);
    }
    /**
     * 显示二维码的图片
     */
    public function ImageAction()
    {
        try {
            $bianhao = $this->request->getQuery('bianhao');
            $title = $this->config->qrcode->url . "?erweima_id=" . $bianhao;
            $label = $this->createBianhaoLabel($bianhao);
            $qrCode = new QrCode($title);
            $qrCode->setSize(132);
            $qrCode->setWriterByName('png');
            $qrCode->setMargin(10);
            $qrCode->setEncoding('UTF-8');
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM);
            $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
            $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);
            $qrCode->setLabel($label,12, __DIR__.'/msyh.ttf', LabelAlignment::CENTER);
            $qrCode ->setValidateResult(false);
            header('Content-Type: '.$qrCode->getContentType());
            echo $qrCode->writeString();
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }

    // 导出二维码【压缩包】
    public function ExportZipAction()
    {
        if(!isset($_GET['amount']) || !($_GET['amount'] > 0)){
            return $this->toError(500, '导出数量有误');
        }
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 60032,
            'parameter' => [
                'amount' => $_GET['amount'],
                'userId' => $this->authed->userId
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $url = $result['content']['address'] ?? null;
        if (empty($url)){
            return $this->toError(500,'服务返回地址异常：'.$url);
        }
        return $this->toSuccess([
            'url' => 'https://'.$url,
        ]);
    }

    /**
     * 二维码的图片下面的编号处理为 0001-9999-9999
     * @param $bianhao
     * @return mixed
     *  2018/11/15 兼容13位二维码
     */
    private function createBianhaoLabel($bianhao)
    {
        if (strlen($bianhao) == 13) {
            return preg_replace("/(\d{4})(\d{5})(\d{4})/", "$1-$2-$3", $bianhao);
        } else {
            return preg_replace("/(\d{4})(\d{4})(\d{4})/", "$1-$2-$3", $bianhao);
        }

    }
}
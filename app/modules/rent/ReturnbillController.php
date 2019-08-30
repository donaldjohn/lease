<?php
namespace app\modules\rent;


use app\modules\BaseController;
use app\services\data\AlipayData;
use app\services\data\PayData;
use app\services\data\WxpayData;

//退款单模块
class ReturnbillController extends BaseController
{
    /**
     * 退款单列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            // 服务单号
            'serviceSn' => 0,
            // 骑手姓名
            'driverName' => '',
            // 骑手联系方式
            'phone' => '',
            // 退款单号
            'returnBillSn' => 0,
            // 支付流水号
            'payRecordSn' => 0,
            // 状态 1：待审核 2：已审核 3：已退款
            'status' => 0,
            // 页码
            'pageNum' => [
                'def' => 1,
            ],
            // 页大小
            'pageSize' => [
                'def' => 20,
            ],
            'operatorInsId' => [
                'def' => null,
            ],
            'parentOperatorInsId' => [
                'def' => null,
            ]
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        if ($this->authed->userType == 9) {
            $parameter['operatorInsId'] = $this->authed->insId;
        } else if ($this->authed->userType == 11) {
            $parameter['parentOperatorInsId'] = $this->authed->insId;
        }
        if (false === $parameter){
            return;
        }
        // 查询退款单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10033",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : ['pageNum' => (int)$parameter['pageNum'], 'pageSize' => (int)$parameter['pageSize'], 'total' => 0];
        // 处理金额 和时间
        foreach ($list as $k => $item) {
            $list[$k]['refundAmount'] = round($item['refundAmount']/10000, 2);
            $list[$k]['returnAmount'] = round($item['returnAmount']/10000, 2);
            $list[$k]['createTime'] = (0==$item['createTime']) ? '-' : date('Y-m-d H:i:s', $item['createTime']);
            $list[$k]['auditTime'] = (0==$item['auditTime']) ? '-' : date('Y-m-d H:i:s', $item['auditTime']);
            $list[$k]['returnTime'] = (0==$item['returnTime']) ? '-' : date('Y-m-d H:i:s', $item['returnTime']);
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    /**
     * 退款单审核
     */
    public function AuditAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $this->logger->info("【退款审核】用户id：".$this->authed->userId.PHP_EOL.$this->request->getRawBody());
        $fields = [
            'returnBillSn' => '请选择退款单',
            'check' => '请选择审核结果',
        ];
        $request = $this->getArrPars($fields, $request);
        // check=1 通过 status=2
        $status = 1==$request['check'] ? 2 : 3;

        // 查询退款信息
        $retRes = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10033",
            'parameter' => [
                'returnBillSn' => $request['returnBillSn']
            ]
        ],"post");
        // 失败返回
        if (!isset($retRes['statusCode']) || $retRes['statusCode'] != '200' || !isset($retRes['content']['data'][0])) {
            return $this->toError(500, '未找到有效退款单');
        }
        // 调试判断脏数据
        if (is_null($retRes['content']['data'][0]['businessSn'])){
            return $this->toError(500, '退款单异常');
        }
        // 更改退款单状态
        $res = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10042",
            'parameter' => [
                'returnBillSn' => $request['returnBillSn'],
                'status' => $status,
            ]
        ],"post");
        // 失败返回
        if (!isset($res['statusCode']) || $res['statusCode'] != '200') {
            return $this->toError(500, '操作失败');
        }
        // 拒绝成功返回
        if (3==$status){
            return $this->toSuccess();
        }
        // 商户订单号
        $businessSn = $retRes['content']['data'][0]['businessSn'];
        // 退款金额
        $refundAmount = $retRes['content']['data'][0]['refundAmount'];
        // 退款单号，部分退款时必传
        $returnBillSn = $retRes['content']['data'][0]['returnBillSn'];
        // 支付金额，微信退款需要
        $payTotal = $retRes['content']['data'][0]['paidAmount'];
        // 支付类型，1：支付宝，2：微信
        $payType = $retRes['content']['data'][0]['payType'];
        (new PayData())->Refund($businessSn, $refundAmount, $payType, $returnBillSn, $payTotal);
        return $this->toSuccess();
    }

    /**
     * 退款单发起退款
     */
    public function RefundAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'returnBillSn' => '请选择退款单',
        ];
        $request = $this->getArrPars($fields, $request);

        // 查询退款信息
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10033",
            'parameter' => [
                'returnBillSn' => $request['returnBillSn']
            ]
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200' || !isset($result['content']['data'][0])) {
            return $this->toError(500, '未找到有效退款单');
        }
        // 状态判断 1：待审核 2：审核成功 3：审核失败 4：退款成功
        if (2 != $result['content']['data'][0]['status']){
            return $this->toError(500, '仅审核通过且未实际退款的退款单可以发起此操作');
        }
        $this->logger->info("【后台发起退款】用户id：".$this->authed->userId.PHP_EOL.$this->request->getRawBody());
        // 商户订单号
        $businessSn = $result['content']['data'][0]['businessSn'];
        // 退款金额
        $refundAmount = $result['content']['data'][0]['refundAmount'];
        // 退款单号，部分退款时必传
        $returnBillSn = $result['content']['data'][0]['returnBillSn'];
        // 支付金额，微信退款需要
        $payTotal = $result['content']['data'][0]['paidAmount'];
        // 支付类型，1：支付宝，2：微信
        $payType = $result['content']['data'][0]['payType'];
        (new PayData())->Refund($businessSn, $refundAmount, $payType, $returnBillSn, $payTotal);
        return $this->toSuccess();
    }

}

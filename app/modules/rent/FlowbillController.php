<?php
namespace app\modules\rent;

use app\modules\BaseController;
use app\services\data\DriverData;

//流水单模块
class FlowbillController extends BaseController
{
    /**
     * 流水单列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            // 服务单号
            'serviceSn' => 0,
            // 支付流水号
            'payRecordSn' => 0,
            // 商户订单号
            'businessSn' => 0,
            // 骑手姓名
            'driverName' => 0,
            // 骑手ID
            'driverId' => 0,
            // 支付类型 1:支付宝 2:微信
            'payType' => 0,
            // 支付时间范围开始
            'startTime' => 0,
            // 支付时间范围结束
            'endTime' => 0,
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
            ],
            'storeInsId' => 0,
            'takeawaySiteName' => 0,
            'takeawayAgentName' => 0,

        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        if ($this->authed->userType == 9) {
            $parameter['operatorInsId'] = $this->authed->insId;
        } else if ($this->authed->userType == 11) {
            $parameter['parentOperatorInsId'] = $this->authed->insId;
        }
        // 查询流水单列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10038",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        $driverIds = [];
        // 处理金额 和时间
        foreach ($list as $k => $item) {
            $driverIds[] = $item['driverId'];
            $list[$k]['amount'] = round($item['amount']/10000, 2);
            $list[$k]['payTime'] = (0==$item['payTime']) ? '-' : date('Y-m-d H:i:s', $item['payTime']);
            $list[$k]['payRecordSn'] = (string)$list[$k]['payRecordSn'];
            $list[$k]['userName'] = $item['driverName']; // 兼容前端
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }


}

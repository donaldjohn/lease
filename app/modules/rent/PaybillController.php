<?php
namespace app\modules\rent;

use app\modules\BaseController;
use app\services\data\DriverData;

//支付单模块
class PaybillController extends BaseController
{
    /**
     * 支付单列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            // 服务单号
            'serviceSn' => 0,
            // 支付单号
            'businessSn' => 0,
            // 骑手姓名
            'driverName' => 0,
            // 状态 1：待支付 2：已支付 3：已关闭
            'payStatus' => 0,
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
        // 查询支付单列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10037",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 处理金额 和时间
        foreach ($list as $k => $item) {
            $list[$k]['amount'] = round($item['amount']/10000, 2);
            if(isset($item['packageDeposit'])) {
                $list[$k]['packageDeposit'] = round($item['packageDeposit']/10000, 2);
            } else {
                $list[$k]['packageDeposit'] = "-";
            }
            if (isset($item['packageRent'])) {
                $list[$k]['packageRent'] = round($item['packageRent']/10000, 2);
            } else {
                $list[$k]['packageRent'] = "-";
            }
            $list[$k]['createTime'] = (0==$item['createTime']) ? '-' : date('Y-m-d H:i:s', $item['createTime']);
            $list[$k]['payTime'] = (0==$item['payTime']) ? '-' : date('Y-m-d H:i:s', $item['payTime']);
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }


}

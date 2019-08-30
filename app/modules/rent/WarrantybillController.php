<?php
namespace app\modules\rent;

use app\modules\BaseController;

//联保单列表
class WarrantybillController extends BaseController
{
    /**
     * 联保单列表
     */
    public function ListAction()
    {
        // 定义接收字段
        $fields = [
            // 联保单号
            'ordedrSn' => 0,
            'serviceSn' => 0,
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
        // 查询押金单列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "11050",
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
            $list[$k]['createAt'] = (0 == $item['createAt']) ? '-' : date('Y-m-d H:i:s', $item['createAt']);
        }

        // 成功返回
        return $this->toSuccess($list, $meta);
    }


}

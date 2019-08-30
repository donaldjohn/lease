<?php
namespace app\modules\postoffice;

use app\models\users\User;
use app\modules\BaseController;
use app\services\data\UserData;

class CheckreportformController extends BaseController
{
    // 快递协会查看快递公司年检情况统计
    public function CompanyStatisticsAction()
    {
        $fields = [
            'companyId' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['userId'] = $this->authed->userId;
        $parameter['insId'] = $this->authed->insId;
        // 查询年检情况统计列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11041,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'] ?? [];
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 快递公司查看站点年检情况统计
    public function SiteStatisticsAction()
    {
        $fields = [
            'regionId' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['userId'] = $this->authed->userId;
        $parameter['insId'] = $this->authed->insId;
        // 查询年检情况统计列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11042,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'] ?? [];
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 快递公司下拉站点列表【查看站点年检情况统计页面用】
    public function SelectSiteAction()
    {
        $fields = [
            // 搜索字段
            'text' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['userId'] = $this->authed->userId;
        $parameter['insId'] = $this->authed->insId;
        // 查询年检情况统计列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11043,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 快递协会下拉快递公司【查看年检情况统计页面用】
    public function SelectExpressAction()
    {
        $fields = [
            // 搜索字段
            'text' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['userId'] = $this->authed->userId;
        $parameter['insId'] = $this->authed->insId;
        // 查询年检情况统计列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11044,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 导出快递公司年检情况统计
    public function ExportCompanyStatisticsAction()
    {
        $fields = [
            'companyId' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['userId'] = $this->authed->userId;
        $parameter['insId'] = $this->authed->insId;
        // 导出年检情况统计列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11045,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 导出站点年检情况统计
    public function ExportSiteStatisticsAction()
    {
        $fields = [
            'regionId' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['userId'] = $this->authed->userId;
        $parameter['insId'] = $this->authed->insId;
        // 导出
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11046,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'];
        // 成功返回
        return $this->toSuccess($data);
    }
}

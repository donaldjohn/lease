<?php
namespace app\modules\dispatch;


use app\models\dispatch\Drivers;
use app\models\dispatch\RegionDrivers;
use app\models\service\RegionVehicle;
use app\models\service\Vehicle;
use app\modules\BaseController;
use app\services\data\DriverData;
use app\services\data\RegionData;
use app\services\data\UserData;

// 年检任务
class YearcheckController extends BaseController
{

    // 快递公司下站点的年检任务
    public function ListAction()
    {
        $_GET['insId'] = $this->authed->insId;
        // $_GET['subInsId'] = (new UserData())->getSubInsIdByMainInsId($this->authed->insId); TODO: 主子合并预废弃
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11048,
            'parameter' => $_GET,
        ],"post");
        if (200 != $result['statusCode']) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 返回
        $list = $result['content']['data'] ?? [];
        $meta = $result['content']['pageInfo'] ?? [];
        return $this->toSuccess($list,$meta);
    }

    // 快递公司下站点列表
    public function SelectSiteAction()
    {
        $_GET['insId'] = $this->authed->insId;
        // $_GET['subInsId'] = (new UserData())->getSubInsIdByMainInsId($this->authed->insId); TODO: 主子合并预废弃
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11049,
            'parameter' => $_GET,
        ],"post");
        if (200 != $result['statusCode']) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 返回
        $list = $result['content']['data'] ?? [];
        return $this->toSuccess($list);
    }



}

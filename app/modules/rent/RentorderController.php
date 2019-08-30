<?php
namespace app\modules\rent;

use app\modules\BaseController;
use app\services\data\DriverData;
use app\services\data\StoreData;
use app\services\data\BillData;
use app\services\data\PackageData;
use app\services\data\VehicleData;

//租赁单模块
class RentorderController extends BaseController
{
    /**
     * 租赁单列表
     */
    public function ListAction()
    {
        $parameter = $_GET;
        if ($this->authed->userType == 9) {
            $parameter['operatorInsId'] = $this->authed->insId;
        } else if ($this->authed->userType == 11) {
            $parameter['parentOperatorInsId'] = $this->authed->insId;
        }
        // 查询租赁单
        $result = $this->CallService('order', 10039, $parameter, true);
        $list = $result['content']['data'];
        $meta = $result['content']['pageInfo'] ?? null;
        // 处理价格
        (new PackageData())->HandlePrice($list);
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

}

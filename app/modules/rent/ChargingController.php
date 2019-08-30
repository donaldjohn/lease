<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/22
 * Time: 17:40
 */

namespace app\modules\rent;


use app\modules\BaseController;
use app\services\data\PackageData;

// 换电单
class ChargingController extends BaseController
{
    public function ListAction()
    {
        $parameter = $_GET;
        switch ($this->authed->userType){
            case 9:
                $parameter['operatorInsId'] = $this->authed->insId;
                break;
            case 8:
                $parameter['storeInsId'] = $this->authed->insId;
                break;
            case 11:
                $parameter['parentOperatorInsId'] = $this->authed->insId;
                break;
        }
        $result = $this->CallService('order', 10040, $parameter, true);
        $list = (new PackageData())->HandlePrice($result['content']['data']);
        return $this->toSuccess($list, $result['content']['pageInfo']);
    }
}
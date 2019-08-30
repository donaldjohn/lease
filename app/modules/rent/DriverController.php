<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/21
 * Time: 22:45
 */

namespace app\modules\rent;


use app\modules\BaseController;

class DriverController extends BaseController
{
    // 骑手列表
    public function ListAction()
    {
        $parameter = $_GET;
        switch ($this->authed->userType){
            case 9:
                $parameter['operatorInsId'] = $this->authed->insId;
                $parameter['parentOperatorInsId'] = $this->appData->getParentInsId($this->authed->insId,$this->authed->userType);
                break;
            case 11:
                $parameter['parentOperatorInsId'] = $this->authed->insId;
                break;
        }
        $result = $this->CallService('order', 10053, $parameter, true);
        return $this->toSuccess($result['content']['data'], $result['content']['pageInfo']);
    }
}
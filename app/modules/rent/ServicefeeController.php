<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/21
 * Time: 20:14
 */

namespace app\modules\rent;

use app\modules\BaseController;
use app\services\data\PackageData;

class ServicefeeController extends BaseController
{
    public function ListAction()
    {
        $result = $this->CallService('order', 10052, $_GET, true);
        $data = (new PackageData())->HandlePrice($result['content']['data']);
        return $this->toSuccess($data, $result['content']['pageInfo']);
    }
}
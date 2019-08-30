<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\postofficeapp;

use app\modules\BaseController;

class IndexController extends PostofficebaseController
{
    /**
     * app首页
     */
    public function IndexAction()
    {
        $json = ['driverId' => $this->authed->userId];
        $result = $this->userData->common($json, $this->Zuul->vehicle,30001);
        $meta = '';
        $result = $result['data'];
        if (empty($result))
            return $this->toSuccess(null,$meta);
        return $this->tosuccess($result,$meta);
    }

}
<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\template;

use app\modules\BaseController;

class IndexController extends BaseController
{
    /**
     * 设置车辆异常通知地址
     */
    public function CallAction()
    {
        $json = ['url' => $this->config->baseUrl.'/messages','options' => ["105"]];
        $result = $this->userData->postCommon($json,$this->Zuul->vehicle,60108);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }




}
<?php
namespace app\modules\pay;

use app\common\library\ZuulApiService;
use app\modules\BaseController;
use Phalcon\Http\Response\Headers;

//上传测试
class FiletestController extends BaseController
{
    /**
     * 上传测试
     * @return mixed
     */
    public function UpfileAction()
    {
        var_dump($_POST);
        // 是否有文件上传
        if ($this->request->hasFiles()) {
            $files = $this->request->getUploadedFiles();

            foreach ($files as $file) {
                // Print file details
                echo $file->getName(), ' ', $file->getType(), ' ', $file->getSize(), "\n";

                // 相对public目录
                $file->moveTo('./upload/'.date('YmdHis-',time()).$file->getName());
            }
        }else{
            echo '未收到文件';
        }
    }

}

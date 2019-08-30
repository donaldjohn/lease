<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\area;

use app\modules\BaseController;

class IndexController extends BaseController
{
    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     * 获取area分页
     */
    public function ListAction()
    {
        $json = [];
        $indexText = $this->request->getQuery('indexText','string',null,true);
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        if (!empty($indexText)) {
            $json['indexText'] = $indexText;
        }
        $result = $this->userData->common($json, $this->Zuul->biz,12001);
        $meta = $result['pageInfo'];
        $result = $result['data'];
        if(empty($result)) {
            return $this->toSuccess(null,$meta);
        }
        return $this->toSuccess($result,$meta);
    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $result = $this->userData->postCommon($json, $this->Zuul->biz,12000);
        return $this->toSuccess($result['data'],null,200,$result['msg']);

    }

    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['areaId'] = $id;
        $result = $this->userData->postCommon($json, $this->Zuul->biz,12002);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

    public function DeleteAction($id)
    {
        $json['areaId'] = $id;
        $result = $this->userData->postCommon($json, $this->Zuul->biz,12003);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

}
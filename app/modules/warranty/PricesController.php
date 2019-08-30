<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\library\ReturnCodeService;
use app\modules\BaseController;

class PricesController extends BaseController {

    public function ListAction()
    {
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!empty($indexText))
            $json['indexText'] = $indexText;
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $this->logger->info('查询配件价格:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->product,ReturnCodeService::WARRANTY_READ_PRICES,'productSkuPriceList');
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        foreach ($result as $key =>  $item) {
            $result[$key]['gmtCreated'] = !empty($item['gmtCreated']) ? date('Y-m-d H:i:s',$item['gmtCreated']) : '-';
            $result[$key]['createAt'] = !empty($item['createAt']) ? date('Y-m-d H:i:s',$item['createAt']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);
    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['createAt'] = time();
        $json['bizId'] =1;
        $json['status'] = 1;
        $json['createAt'] = time();
        $json['updateAt'] = $json['createAt'];
        $this->logger->info('新增配件价格:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_CREATE_PRICES);
        return $this->toSuccess(null,null,200,$result['msg']);
    }



    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('修改配件价格:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_UPDATE_PRICES);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

    public function StatusAction($id)
    {
        $data = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        if(!isset($data['status'])){
            return $this->toError(500,'状态必填!');
        }
        $key = [1,2];
        if (!in_array($data['status'],$key)) {
            return $this->toError(500,'状态必需为1,2!');
        }
        $json['status'] = $data['status'];
        //$json['updateAt'] = time();
        $this->logger->info('新增配件价格:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_STATUS_PRICES);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

    public function DeleteAction($id)
    {
        $json['id'] = $id;
        $this->logger->info('删除配件价格:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_DELETE_PRICES);
        return $this->toSuccess(null,null,200,$result['msg']);
    }


    public function LeadInAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_IN_PRICES);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }


    public function LeadOutAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('商品价格导出:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->product,ReturnCodeService::WARRANTY_OUT_PRICES);
        return $this->toSuccess('http://'.$result['data']['address'],null,200,$result['msg']);
    }

}
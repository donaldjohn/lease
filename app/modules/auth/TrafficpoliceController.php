<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: PoliceController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\modules\BaseController;

class TrafficpoliceController extends BaseController
{

    const ADD_POLICE = 10111;
    const UPDATE_POLICE = 10112;
    const DELETE_POLICE = 10113;
    const LIST_POLICE = 10114;
    const SELCET_POLICE_ONE = 10118;
    const STATUS_POLICE = 10123;


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     * 查询警局
     */
    public function ListAction()
    {
        $pageNum = $this->request->getQuery('pageNum','int',1,true);
        $pageSize = $this->request->getQuery('pageSize','int',20,true);
        $searchItem = $this->request->getQuery('searchItem','string',null,true);
        $json = [];
        $json['pageNum'] = $pageNum;
        $json['pageSize'] = $pageSize;
        $json['insId'] = $this->authed->insId;
        if ($searchItem != null) {
            $json['searchItem'] = $searchItem;
        }
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'查询警局:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->user,self::LIST_POLICE,'datas');
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($result == null)
            return $this->toSuccess($result,$pageInfo);
        foreach ($result as $key =>  $item) {
            $result[$key]['createAt'] = !empty($result[$key]['createAt']) ? date('Y-m-d H:i:s',$result[$key]['createAt']) : '-';
            $result[$key]['updateAt'] = !empty($result[$key]['updateAt']) ? date('Y-m-d H:i:s',$result[$key]['updateAt']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);

    }

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 创建警局
     */
    public function CreateAction()
    {
        $userName = null;
        $json = $this->request->getJsonRawBody(true);
        // 判断上传数据确定账户username
        if (isset($json['provinceId'])) {
            $userName = $json['provinceId'];
            $resultRegion = $this->userData->getRegionName($json['provinceId']);
            if (isset($resultRegion['areaName'])) {
                $json['realName'] = $resultRegion['areaName'].'交警队';
            }
        }
        if (isset($json['cityId']) && $json['cityId'] > 0) {
            $userName = $json['cityId'];
            $resultRegion = $this->userData->getRegionName($json['cityId']);
            if (isset($resultRegion['areaName'])) {
                $json['realName'] = $resultRegion['areaName'].'交警队';
            }
        }
        if (isset($json['areaId']) && $json['areaId'] > 0) {
            $userName = $json['areaId'];
            $resultRegion = $this->userData->getRegionName($json['areaId']);
            if (isset($resultRegion['areaName'])) {
                $json['realName'] = $resultRegion['areaName'].'交警队';
            }
        }


        if ($userName != null) {
            $json['userName'] = $userName;
        } else {
            return $this->toError(500,'上传数据格式有误！');
        }
        $json['userStatus'] = 2;
        $json['password'] = $this->security->hash("123456",6);
        $json['isAdministrator'] = 2;
        $json['createAt'] = time();
        $json['parentId'] = $this->authed->insId;
        $json['userType'] = 10;
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::ADD_POLICE);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 更新警局
     */
    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'更新警局:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::UPDATE_POLICE);
        return $this->toSuccess(null,null,200,$result['msg']);

    }


    public function StatusAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'更新警员:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::STATUS_POLICE);
        return $this->toSuccess(null,null,200,$result['msg']);

    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 删除警局
     */
    public function DeleteAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'删除警局:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::DELETE_POLICE);
        return $this->toSuccess(null,null,200,$result['msg']);

    }


    public function SelfAction(){
        $json['insId'] = $this->authed->insId;
        if ($json['insId'] == 0) {
            return $this->toSuccess(null);
        }
        $result = $this->userData->common($json,$this->Zuul->user,self::SELCET_POLICE_ONE);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($result == null)
            return $this->toSuccess($result,$pageInfo);
        return $this->toSuccess($result,$pageInfo);
    }


}
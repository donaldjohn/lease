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

class PolicemanController extends BaseController
{

    const ADD_POLICE_MAN = 10116;
    const UPDATE_POLICE_MAN = 10117;
    const DELETE_POLICE_MAN = 10119;
    const LIST_POLICE_MAN = 10121;
    const STATE_POLICE_MAN = 10120;


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
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'查询警局人员:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->user,self::LIST_POLICE_MAN,'datas');
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

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        if (!isset($json['roleId'])) {
            return $this->toError(500,'角色必传！');
        }

        $json['userStatus'] = 2;
        $json['password'] = $this->security->hash("123456",6);
        $json['isAdministrator'] = 1;
        $json['insId'] = $this->authed->insId;
        $json['createAt'] = time();
        $json['parentId'] = $this->authed->userId;
        $json['userType'] = 10;
        $json['groupId'] = $this->authed->groupId;
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::ADD_POLICE_MAN);
        return $this->toSuccess(null,null,200,$result['msg']);

    }

    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'更新警员:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::UPDATE_POLICE_MAN);
        return $this->toSuccess(null,null,200,$result['msg']);

    }


    public function StatusAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'更新警员状态:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::STATE_POLICE_MAN);
        return $this->toSuccess(null,null,200,$result['msg']);

    }


    public function DeleteAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        $this->logger->info('用户：'.$this->authed->userId.':'.$this->authed->userName.'删除警员:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->user,self::DELETE_POLICE_MAN);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

}
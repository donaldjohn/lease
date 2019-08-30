<?php
namespace app\modules\dispatch;

use app\models\users\UserInstitution;
use app\modules\BaseController;
use app\services\data\RegionData;
use app\models\dispatch\RegionUser;

// 快递公司模块
class ExpressController extends BaseController
{
    // 职能用户列表 搜索
    public function UserlistAction()
    {
        $this->logger->info('获取职能用户');
        $fields = [
            'userName' => 0,
            'realName' => 0,
            'userStatus' => 0,
            "pageNum" => [
                'def' => 1,
            ],
            "pageSize" => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['excludeId'] = [$this->authed->userId];
        // 如果不是主账号查出下属区域用户
        if (1 == $this->authed->isAdministrator){
            // 非主账号且无区域
            if (!($this->authed->regionId > 0)){
                return $this->toEmptyList();
            }
            $RegionData =new RegionData();
            // 查询下属区域
            $regionIds = $RegionData->getBelongRegionIdsByRegionId($this->authed->regionId, $this->authed->insId);
            // 查询区域关联用户
            $RUs = RegionUser::find([
                'region_id IN ({regionIds:array})',
                'bind' => [
                    'regionIds' => $regionIds,
                ],
                'columns' => 'region_id, user_id'
            ]);
            if (!$RUs){
                return $this->toEmptyList();
            }
            $userIds = [];
            foreach ($RUs as $RU){
                $userIds[] = $RU->user_id;
            }
            $parameter['idList'] = $userIds;
        }else{
            // 主账号用机构id筛选用户
            $UIs = UserInstitution::find([
                'ins_id = :ins_id:',
                'bind' => [
                    'ins_id' => $this->authed->insId,
                ],
            ]);
            if (!$UIs){
                return $this->toEmptyList();
            }
            $userIds = [];
            foreach ($UIs as $UI){
                $userIds[] = $UI->user_id;
            }
            $parameter['idList'] = $userIds;
        }
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user, [
            "code" => "10004",
            "parameter" => $parameter
        ], "post");
        if (!isset($result['statusCode']) || 200!=$result['statusCode']){
            return $this->toError(500, '服务异常');
        }
        $meta = $result['content']['pageInfo'];
        $result = $result['content']['users'];

        foreach($result as $key => $item) {
            //根据角色Id查找角色名称
            if ($item['roleId'] >= 1) {
                $role = $this->roleData->getRoleById($item['roleId']);
                if ($role == false) {
                    $result[$key]['roleName'] = '---';
                } else {
                    $result[$key]['roleName'] = $role['roleName'];
                }
            } else {
                $result[$key]['roleName'] = '---';
            }
            unset($result[$key]['password']);
            $result[$key]['createAt'] = date("Y-m-d H:i:s", $item['createAt']);
            $result[$key]['updateAt'] = date("Y-m-d H:i:s", $item['updateAt']);

        }
        $users = ["data" => $result, "meta" => $meta];
        return $this->toSuccess($users['data'],$users['meta']);
    }
}

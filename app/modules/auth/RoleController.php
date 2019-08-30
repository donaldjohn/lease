<?php
namespace app\modules\auth;

use app\common\errors\AppException;
use app\common\errors\DataException;

use app\models\users\GroupFunction;
use app\models\users\GroupMenu;
use app\models\users\Role;
use app\models\users\RoleFunction;
use app\models\users\RoleMenu;
use app\models\users\UserInstitution;
use app\models\users\UserSystem;
use app\modules\BaseController;

/**
 * Class RoleController
 * @package app\modules\auth
 *
 * 角色管理
 * 查询列表 单个查询 新增 编辑 删除
 */
class RoleController extends BaseController
{
    /**
     * 查询角色列表
     */
    //TODO:: 过滤子系统角色
    public function listAction()
    {

        /**
         * 如果传过来的参数中有type则执行不分页查找
         */
        $this->logger->info("角色列表");
        $type = $this->request->getQuery("type", "string", "page");
        $roleStatus = $this->request->getQuery("roleStatus", "int", 1);
//        if ($this->system != 0) {
//            $groupId = $this->authed->groupId;
//        } else {
//            $groupId = $this->request->getQuery("groupId", "int", null);
//        }
//        $groupType = $this->request->getQuery("groupType", "int", null);
//        if (!empty($groupType)) {
//            $groupId = $this->authed->groupId;
//        }
        if ($this->authed->userType != 1 ) {
            $groupId = $this->authed->groupId;
        } else {
            $groupId = null;
        }
        if ($type != "page") {
            return $this->getRoleList($this->system,$this->authed,$roleStatus,$groupId);
        }

        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = ["pageNum" => $pageNum,"pageSize" => $pageSize];
        //调用微服务接口获取数据

        $parameter['insId'] = $this->authed->insId;
        //$parameter['systemId'] = $this->system;

        $roleName =  $this->request->getQuery('roleName', "string",null);
        if (!empty($roleName)) {
            $parameter['roleName'] = $roleName;
        }
        $roleCode =  $this->request->getQuery('roleCode', "string",null);
        if (!empty($roleCode)) {
            $parameter['roleCode'] = $roleCode;
        }
        $roleStatus =  $this->request->getQuery('roleStatus', "string",null);
        if (!empty($roleStatus)) {
            $parameter['roleStatus'] = (int)$roleStatus;
        }
        $params = ["code" => "10007","parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : null;
            $meta['pageNum'] = $pageNum;
            $meta['pageSize'] = $pageSize;
            return $this->toSuccess($result['content']['roles'],$meta);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }

    /**
     * 根据角色ID查询角色信息
     * code:10007
     */
    public function oneAction($id)
    {
        //根据id查询数据
        $res = ["id" => $id];
        $json = ['code' => 10007,"parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if (count($result['content']['roles']) != 1){
                return $this->toError(500, '未查到有效数据');
            }
            $result = $result['content']['roles'][0];
            if ($result['groupId'] != $this->authed->groupId) {
                return $this->toError(500,'非法查询');
            }
            //根据groupId 获取 group_type
            $userGroup = $this->userGroupData->getUserGroupById($result['groupId']);
            $userGroupType = isset($group['userGroupType']) ?  $userGroup['userGroupType'] : '无';
            $result["userGroupType"] = $userGroupType;
            $result['createAt'] = isset($result['createAt']) ? date('Y-m-d H:i:s' , $result['createAt']) : '无';
            $result['updateAt'] = isset($result['updateAt']) ? date('Y-m-d H:i:s' , $result['updateAt']) : '无';
            return $this->toSuccess($result);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 创建角色
     * code:10005
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);

        //if ($this->system != 0) {
            $request['groupId'] = $this->authed->groupId;
            $request['systemId'] = $this->system;
            $request['insId'] = $this->authed->insId;
        //} else {
        //    $request['systemId'] = 0;
        //    $request['insId'] = 0;
        //}


        $fields = [
            'systemId' => '系统ID必填',
            'groupId' => '用户组ID必填',
            'roleName' => '角色名称必填',
            'roleCode' => '角色代码必填',
            'roleStatus' => '角色状态必填',
            'insId' => 0,
            'roleMark' => 0,
            'createAt' => 0,
        ];
        $res = $this->getArrPars($fields, $request);
        if (!$res){
            return;
        }
        $res['createAt'] = time();
        $params = ["code" => "10005","parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 更新角色
     * code：10004
     */
    public function UpdateAction($id)
    {
        $search_params = ["id" => $id];
        $json = ['code' => 10007,"parameter" => $search_params];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        unset($search_params);
        if ($result['statusCode'] == '200') {
            $params = $result['content']['roles'][0];
            if ($params['groupId'] != $this->authed->groupId) {
                return $this->toError(500,'非法查询');
            }
            unset($result);
            $res = $this->request->getJsonRawBody();
            if (isset($res->groupId) && $this->system == 0)
                $params['groupId'] = $res->groupId;
            if (isset($res->roleName))
                $params['roleName'] = $res->roleName;
            if (isset($res->roleCode))
                $params['roleCode'] = $res->roleCode;
            if (isset($res->roleStatus))
                $params['roleStatus'] = $res->roleStatus;
            if (isset($res->roleMark))
                $params['roleMark'] = $res->roleMark;
            $params['updateAt'] = time();
            $put_params = ["code" => "10008","parameter" => $params];
            $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
            if ($result['statusCode'] == '200') {
                return $this->toSuccess($params);
            } else {
                return $this->toError($result['statusCode'],$result['msg']);
            }
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }

    /**
     * 删除角色
     * code：10006
     */
    public function DeleteAction($id)
    {
        $res = ["id" => $id];
        $json = ['code' => 10007,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if (count($result['content']['roles']) != 1) {
                return $this->toError(500, '未查到有效数据');
            }
            $result = $result['content']['roles'][0];
            if ( $result['groupId'] != $this->authed->groupId) {
                return $this->toError(500, '非法操作!');
            }
        }
        $json = ['code' => 10006,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }



    /**
     * 角色分配权限(主系统和子系统公用)
     * 1.主系统角色列表为对应用户组的权限进行赛选
     *
     */
    public function TreeAction($id)
    {

        $sub_admin = false;
//        if ($this->system != 0) {
//            $result_system = $this->userGroupData->checkUserSystems($this->authed,$this->system);
//            if ($result_system == true) {
//                $sub_admin = true;
//            }
//        }

        //根据角色id查询group数据
        $res = ["id" => $id];
        $jsonRes = ['code' => 10007,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$jsonRes,"post");
        if ($result['statusCode'] != '200')
            throw new DataException([$result['statusCode'],$result['msg']]);
        if (count($result['content']['roles']) != 1)
            throw new DataException([500,"未查到有效数据"]);
        if (!isset($result['content']['roles'][0]['groupId']))
            throw new DataException([500,"数据获取失败"]);
        if ( $result['content']['roles'][0]['groupId'] != $this->authed->groupId) {
            return $this->toError(500, '非法操作!');
        }
        $groupId = $result['content']['roles'][0]['groupId'];
        unset($result);
        /**
         * 根据用户组ID获取对应的数据
         */
        $userGroup = $this->userGroupData->getUserGroupById($groupId);
        if ($userGroup == false) throw new AppException(['500','用户组不存在!请联系管理员']);
        $userGroupType = $userGroup['groupType'];
        unset($userGroup);

        /**
         *    根据groupId获取当前ID能用的数据   数据过滤
         */

        $params = [
            'code' => '10016',
            'parameter' => [
                'groupId' => $groupId,
            ]
        ];
        //调用微服务接口获取数据
        $result_menus = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        $menus = [];
        if ($result_menus['statusCode'] == '200') {
            $result_menus = $result_menus['content']['groupMenuDOS'];
            foreach($result_menus as $item) {
                $menus[] = $item['menuId'];
            }
        } else {
            return $this->toError($result_menus['statusCode'],$result_menus['msg']);
        }

        $params = [
            'code' => '10035',
            'parameter' => [
                'groupId' => $groupId,
            ]
        ];
        //调用微服务接口获取数据
        $groupFuncs = [];
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $result_funcs = $result_funcs['content']['groupFunctionDOS'];
            foreach ($result_funcs as $item) {
                $groupFuncs[] = $item['menufuncId'];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }

        /**
         * 获取group能用的数据
         */
        $commonMenus = $this->menuData->getMenuList($this->system,1,3,0);
        $groupTypeMenus = $this->menuData->getMenuList($this->system,1,3,$userGroupType);
        $arr = array_merge($commonMenus,$groupTypeMenus);
        //合并俩个数组
        $key = "id";//去重条件
        $thirdGroupMenus = array();
        foreach($arr as $k => $v) {
            if (in_array($v[$key], $thirdGroupMenus)) {
                unset($arr[$k]);
                //删除掉数组（$arr）里相同ID的数组
            } else {
                $thirdGroupMenus[] = $v;
            }
        }
        $thirdGroupMenus = $this->sortArrByManyField($thirdGroupMenus,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
        //TODO 排序 order 怎么解决
        //查询所有功能点
        //$funcs = $this->funcData->getFuncList();
        //绑定三级菜单所对应的四级菜单
        unset($arr);
        unset($commonMenus);
        unset($groupTypeMenus);
        $third = [];
        foreach ($thirdGroupMenus as $item) {
            if (!$sub_admin) {
                if (!in_array($item['id'],$menus)) {
                    continue;
                }
            }
            $list = [];
            $list['id'] = $item['id'];
            $list['type'] = "menu";
            $list['title'] = $item['menuName'];
            $list['menuCode'] = $item['menuCode'];
            $list['menuType'] = $item['menuType'];
            $list['menuSystem'] = $item['menuSystem'];
            $list['menuOrder'] = $item['menuOrder'];
            $list['menuLevel'] = $item['menuLevel'];
            $list['parentId'] = $item['parentId'];
            $list['expand'] = true;
            //TODO 方法二: 根据menuID获取对应的功能点 增加网络开销
            $funcs = $this->funcData->getFuncByMenuId($item['id']);
            //$funcsId = $this->funcData->getFuncIds($funcs);
            $resultFuncs = [];
            foreach ($funcs as $f) {
                if (!$sub_admin){
                    if (!in_array($f['id'], $groupFuncs)) {
                        continue;
                    }
                }
                $resultFuncs[] =  $f;
            }
            $list["children"] = $resultFuncs;
            $third[] = $list;
        }
        unset($thirdGroupMenus);
        unset($resultFuncs);
        //通过子类调用父类一直递归到parentId为0为止
        $groupByParentId = [];
        foreach ($third as $item) {
            $groupByParentId[$item['parentId']][] = $item;
        }

        if (count($groupByParentId) == 0) {
            $result = ["tree" =>[],"menus"=> [] ,"funcs" => []];
            return $this->toSuccess($result);
        }

        $result_tree = $this->menuData->res_parent_tree($groupByParentId);

        //获取角色对应的数据
        $params = [
            'code' => '10025',
            'parameter' => [
                'roleId' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result_menus = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_menus['statusCode'] == '200') {
            $menus = [];
            $menus_result = $result_menus['content']['roleMenus'];
            foreach ($menus_result as $item) {
                $menus[] = $item["menuId"];
            }
        } else {
            return $this->toError($result_menus['statusCode'],$result_menus['msg']);
        }

        $params = [
            'code' => '10047',
            'parameter' => [
                'roleId' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $funcs = [];
            $funcs_result = $result_funcs['content']['roleFunctions'];
            foreach ($funcs_result as $item) {
                $funcs[] = $item["menufuncId"];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }
        $result = ["tree" =>$result_tree[0],"menus"=> $menus ,"funcs" => $funcs];
        return $this->toSuccess($result);

    }


    /**
     * 更新角色权限
     * 27  46
     */
    public function AuthAction($id)
    {
        //根据角色删除之前的所有数据
        $params = [
            'code' => '10027',
            'parameter' => [
                'roleId' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200')
            return $this->toError($result['statusCode'],$result['msg']);

        $params = [
            'code' => '10046',
            'parameter' => [
                'roleId' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200')
            return $this->toError($result['statusCode'],$result['msg']);
        $res = $this->request->getJsonRawBody(true);
        $menus = $res["menus"];
        $funcs = $res["funcs"];
//        foreach ($menus as $item) {
//            $time = time();
//            $parameter = ["roleId" => $id,"menuId" => $item,"level" => 3,'createAt' => $time];
//            $params = [
//                'code' => '10026',
//                'parameter' => $parameter
//            ];
//            //调用微服务接口获取数据
//            $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                return $this->toError($result['statusCode'],$result['msg']);
//            }
//        }
        if (count($menus) > 0) {
            $json = [];
            foreach($menus as $item) {
                $list = [];
                $list['roleId'] = $id;
                $list['menuId'] = $item;
                $list['level'] = 3;
                $json['data'][] = $list;

            }
            $params = [
                'code' => '10084',
                'parameter' => $json
            ];
            //调用微服务接口获取数据
            $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                return $this->toError($result['statusCode'],$result['msg']);
            }
        }
//        foreach($funcs as $item) {
//            $time = time();
//            $parameter = ["roleId" => $id,"menufuncId" => $item,'createAt' => $time];
//            $params = [
//                'code' => '10045',
//                'parameter' => $parameter
//            ];
//            //调用微服务接口获取数据
//            $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                return $this->toError($result['statusCode'],$result['msg']);
//            }
//        }
        if (count($funcs) > 0) {
            $json = [];
            foreach($funcs as $item) {
                $list = [];
                $list['roleId'] = $id;
                $list['menufuncId'] = $item;
                $json['data'][] = $list;
            }
            $params = [
                'code' => '10082',
                'parameter' => $json
            ];
            //调用微服务接口获取数据
            $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                return $this->toError($result['statusCode'],$result['msg']);
            }
        }
        return $this->toSuccess(true);
    }


    /**
     * @param $roleStatus
     * @param $groupId
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     *
     */
    protected function getRoleList($system,$authed,$roleStatus,$groupId)
    {
        $roleLists = [];
        $parameter = [];
        if (!empty($roleStatus)) {
            $parameter['roleStatus'] = $roleStatus;
        } else {
            $parameter['roleStatus'] = 1;
        }
        if (!empty($groupId))
            $parameter['groupId'] = $groupId;

        //if ($system != 0) {
            $parameter['insId'] = $authed->insId;
        //}
        $params = ["code" => "10007", "parameter" =>$parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == '200') {
            $result = $result['content'];
            if (is_array($result) && count($result) > 1) {
                foreach ($result as $item) {
                    $api = [];
                    $api["id"] = $item["id"];
                    $api["roleCode"] = $item["roleCode"];
                    $api["roleName"] = $item["roleName"];
                    $roleLists[] = $api;
                }
            } else {
                $roleLists = (array)$result;
            }
            return $this->toSuccess($roleLists);
        }
        return $this->toError($result['statusCode'], $result['msg']);
    }




    /**
     * @param $tree
     * @param $pid
     * @return array
     */
    //递归树，效率低
    private  function res_tree($tree,$pid){
        $result = array();                                //每次都声明一个新数组用来放子元素
        foreach($tree as $v){
            if($v['parentId'] == $pid){//匹配子记录
                $v['children'] = self::res_tree($tree,$v['id']); //递归获取子记录
                if($v['children'] == null){
                    unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
                }
                $result[] = $v;
            }
        }
        return $result;                                  //返回新数组
    }


    private function sortArrByManyField(){
        $args = func_get_args();
        if(empty($args)){
            return null;
        }
        $arr = array_shift($args);
        if(!is_array($arr)){
            throw new AppException([500,"第一个参数不为数组"]);
        }
        foreach($args as $key => $field){
            if(is_string($field)){
                $temp = array();
                foreach($arr as $index=> $val){
                    if(is_array($val))
                        $temp[$index] = $val[$field];
                }
                $args[$key] = $temp;
            }
        }
        $args[] = &$arr;//引用值
        call_user_func_array('array_multisort',$args);
        return array_pop($args);
    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     *
     * 角色权限分配
     * 角色权限 分为主系统和子系统
     * 1.主系统
     *  1.根据上级用户组ID获取数据
     *
     * 2.子系统
     *  子系统角色管理只有子系统管理员才能使用
     *  查询子系统所有权限
     */
    public function RoleMenuAction($id)
    {
        $subAdmin = false;
        if ($this->system > 0) {
            /**
             * 如何判断当前用户是否是子系统admin
             */
            //$userSystem = UserInstitution::findFirst(['conditions' => 'user_id = :user_id: and ins_id = :ins_id: and is_admin = 1','bind' => ['user_id' => $this->authed->userId,'ins_id' =>$this->authed->userId]]);
            $userSystem = $this->modelsManager->createBuilder()
                ->columns('i.id,i.system_id,i.type_id,ui.user_id')
                ->addFrom('app\models\users\Institution','i')
                ->leftJoin('app\models\users\UserInstitution','i.id = ui.ins_id','ui')
                ->andWhere('i.id = :id: and i.is_sub = :is_sub: and i.type_id = :type_id: and ui.user_id = :user_id: and ui.is_admin = 1',
                    ['id' => $this->authed->insId,'is_sub' => 2,'type_id' => $this->authed->userType,'user_id' => $this->authed->userId])
                ->getQuery()
                ->getSingleResult();
            if ($userSystem == false) {
                return $this->toError(500,'非法操作!');
            }
            $subAdmin = true;
        }

        $role = $this->modelsManager->createBuilder()
            ->columns('r.id,r.group_id,r.ins_id,r.role_name,g.group_type')
            ->addFrom('app\models\users\Role','r')
            ->leftJoin('app\models\users\Group','g.id = r.group_id','g')
            ->andWhere('r.id = :id:',['id' => $id])
            ->getQuery()->getSingleResult();
        if ($role == false) {
            return $this->toError(500,'当前角色不存在!');
        }

        $systemMenus = $this->modelsManager->createBuilder()
            ->columns('m.id,m.menu_name,m.menu_code,m.menu_system,m.parent_id,m.menu_level,m.menu_order,
            mf.id as funcId,mf.func_name,mf.func_code,mf.status')
            ->addFrom('app\models\users\Menu','m')
            ->leftJoin('app\models\users\MenuFunction','m.id = mf.menu_id','mf')
            ->andWhere('m.menu_system = :menu_system: and m.menu_status = 1', ['menu_system' => (string)$this->system])
            ->inWhere('m.menu_type',[0,$role->group_type])
            ->orderBy('m.menu_level asc,m.menu_order asc')
            ->getQuery()
            ->execute()->toArray();


        if ($subAdmin == false) {
            $menuRelations = [];
            foreach($systemMenus as $item) {
                $menuRelations[$item['id']] = (int)$item['parent_id'];
            }
            $groupLists = [];
            //获取当前用户组所属的关系集合
            //数据库里只存了3级 所及要去获取2级和1级
            $groups = GroupMenu::find(['group_id = :group_id:','bind' => ['group_id' => $role->group_id]])->toArray();
            foreach($groups as $item) {
                $groupLists[] = $item['menu_id'];
                $item = $item['menu_id'];
                while (isset($menuRelations[$item]) && $menuRelations[$item] > 0) {
                    $groupLists[] = $menuRelations[$item];
                    $item = (int)$menuRelations[$item];
                }
            }

            $gfuncs = [];
            //获取已存的funcID
            $groupFuncs = GroupFunction::find(['columns' => 'menufunc_id','conditions' => 'group_id = :groupId:','bind' => ['groupId' => $role->group_id]])->toArray();
            foreach($groupFuncs as $item) {
                $gfuncs[] = $item['menufunc_id'];
            }


            $tree = [];
            $lists = [];
            foreach($systemMenus as $item) {
                if (!in_array($item['id'],$lists) && in_array($item['id'],$groupLists)) {
                    $list = [];
                    $list['id'] = $item['id'];
                    $list['type'] = 'menu';
                    $list['title'] = $item['menu_name'];
                    $list['menuCode'] = $item['menu_code'];
                    $list['menuLevel'] = $item['menu_level'];
                    $list['menuSystem'] = $item['menu_system'];
                    $list['menuOrder'] = $item['menu_order'];
                    $list['parentId'] = $item['parent_id'];
                    $tree[] = $list;
                    $lists[] = $item['id'];
                    if ($item['menu_level'] == 3 && in_array($item['funcId'],$gfuncs)) {
                        $list = [];
                        $list['id'] = $item['funcId'];
                        $list['type'] = 'func';
                        $list['title'] = $item['func_name'];
                        $list['menuCode'] = $item['func_code'];
                        $list['parentId'] = $item['id'];
                        $tree[] = $list;
                    }
                } else {
                    if ($item['menu_level'] == 3 && in_array($item['funcId'],$gfuncs)) {
                        $list = [];
                        $list['id'] = $item['funcId'];
                        $list['type'] = 'func';
                        $list['title'] = $item['func_name'];
                        $list['menuCode'] = $item['func_code'];
                        $list['parentId'] = $item['id'];
                        $tree[] = $list;
                    }
                }
            }
        } else {
            $tree = [];
            $lists = [];
            foreach($systemMenus as $item) {
                if (!in_array($item['id'],$lists)) {
                    $list = [];
                    $list['id'] = $item['id'];
                    $list['type'] = 'menu';
                    $list['title'] = $item['menu_name'];
                    $list['menuCode'] = $item['menu_code'];
                    $list['menuLevel'] = $item['menu_level'];
                    $list['menuSystem'] = $item['menu_system'];
                    $list['menuOrder'] = $item['menu_order'];
                    $list['parentId'] = $item['parent_id'];
                    $tree[] = $list;
                    $lists[] = $item['id'];
                    if ($item['menu_level'] == 3) {
                        if (isset($item['funcId']) && $item['funcId'] > 0) {
                            $list = [];
                            $list['id'] = $item['funcId'];
                            $list['type'] = 'func';
                            $list['title'] = $item['func_name'];
                            $list['menuCode'] = $item['func_code'];
                            $list['parentId'] = $item['id'];
                            $tree[] = $list;
                        }
                    }
                } else {
                    if ($item['menu_level'] == 3) {
                        if (isset($item['funcId']) && $item['funcId'] > 0) {
                            $list = [];
                            $list['id'] = $item['funcId'];
                            $list['type'] = 'func';
                            $list['title'] = $item['func_name'];
                            $list['menuCode'] = $item['func_code'];
                            $list['parentId'] = $item['id'];
                            $tree[] = $list;
                        }
                    }
                }
            }
        }


        //rolemenu
        $menus = [];
        $roleMenus = RoleMenu::find(['role_id = :role_id:','bind' => ['role_id' => $id]])->toArray();
        foreach ($roleMenus as $item) {
            $menus[] = $item['menu_id'];
        }

        $funcs = [];
        //获取已存的funcID
        $roleFuncs = RoleFunction::find(['columns' => 'menufunc_id','conditions' => 'role_id = :role_id:','bind' => ['role_id' => $id]])->toArray();
        foreach($roleFuncs as $item) {
            $funcs[] = $item['menufunc_id'];
        }
        $result = ['tree' => $tree,'menus' => $menus,'funcs' => $funcs];
        return $this->toSuccess($result);


    }



    public function RlistAction()
    {

        $insId = $this->authed->insId;
        $roles = Role::find(['conditions'=>'ins_id = :insId: and group_id = :groupId: and role_status = 1','bind' =>['insId' => $insId,'groupId' => $this->authed->groupId]])->toArray();
        return $this->toSuccess($roles);
    }


}

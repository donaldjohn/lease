<?php
namespace app\modules\auth;

use app\common\errors\AppException;
use app\common\errors\DataException;
use app\models\users\GroupFunction;
use app\models\users\GroupMenu;
use app\models\users\Menu;
use app\modules\BaseController;


/**
 * Class UserGroupController
 * @package app\modules\auth
 *
 *  用户组管理接口
 */
class UsergroupController extends BaseController
{
    //查询
    public function ListAction()
    {
        /**
         * 如果传过来的参数中有type则执行不分页查找
         */
        $type = $this->request->getQuery("type", "string", "page");
        $groupStatus = $this->request->getQuery("groupStatus", "int", 1);
        $groupType = $this->request->getQuery("groupType", "int", null);
        if ($type != "page") {
            return $this->getUserGroupList($groupStatus,$groupType);
        }
        // 参数处理
        $fields = [
            'parameter' => 0,
            'id' => 0,
            'groupName' => 0,
            'groupCode' => 0,
            'groupType' => 0,
            'groupStatus' => 0,
            'groupRemark' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (!$parameter){
            return;
        }
        $params = [
            'code' => '10014',
            'parameter' => $parameter
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : 0;
            $meta['pageNum'] = $parameter['pageNum'];
            $meta['pageSize'] = $parameter['pageSize'];
            return $this->toSuccess($result['content']['groupDOS'],$meta);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }

    //查询单个
    public function oneAction($id)
    {
        $params = [
            'code' => '10014',
            'parameter' => [
                'id' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            if (count($result['content']['groupDOS']) != 1){
                return $this->toError(500, "未获取到有效数据");
            }
            return $this->toSuccess($result['content']['groupDOS'][0]);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }


    /**
     * 新增用户组
     * code: 10012
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'groupName' => '用户组名称不能为空',
            'groupCode' => '用户组代码不能为空',
            'groupType' => '用户组类别不能为空',
            'groupStatus' => '用户组状态不能为空',
            'groupRemark' => [
                'def' => ''
            ],
        ];
        $parameter = $this->getArrPars($fields, $request);
        if (!$parameter){
            return;
        }
        $parameter['createAt'] = time();
        $params = [
            'code' => '10012',
            'parameter' => $parameter
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['statusCode'], '新增成功' );
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 编辑用户组
     * code：10013
     */
    public function UpdateAction($id)
    {
        // 兼容前端调试
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'groupName' => 0,
            'groupCode' => 0,
            'groupType' => 0,
            'groupStatus' => 0,
            'groupRemark' => 0,
        ];
        $parameter = $this->getArrPars($fields, $request,true);
        if (!$parameter){
            return;
        }
        $parameter['id'] = $id;
        $parameter['updateAt'] = time();
        $params = [
            'code' => '10013',
            'parameter' => $parameter
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['statusCode'], '编辑成功' );
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 删除用户组
     * code：10023
     */
    public function DeleteAction($id)
    {
        $params = [
            'code' => '10023',
            'parameter' => [
                'id' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            return $this->toSuccess($result['statusCode'], '删除成功' );
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 生成功能点树
     */
    public function TreeAction($id)
    {
        //根据groupId 获取 group_type
        $userGroup = $this->userGroupData->getUserGroupById($id);
        if ($userGroup == false) throw new AppException(['500','用户组不存在!请联系管理员']);
        $userGroupType = $userGroup['groupType'];
        unset($userGroup);
        //查询menu等级  menutype = 0 为通用
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

        $menusFuncs = $this->funcData->getFuncList(1,null);
        $third = [];
        foreach ($thirdGroupMenus as $item) {
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
            //TODO  方法一： 有问题  如何解决其他系统的无效code 减少开销
//            foreach ($funcs as $key => $funcitem) {
//                if ($funcitem["parentId"] == $list['id']) {
//                    $fList = [];
//                    $fList['id'] = $funcitem['id'];
//                    $fList['type'] = "func";
//                    $fList['title'] = $funcitem['funcCode'];
//                    $fList['funcCode'] = $funcitem['funcCode'];
//                    $fList['parentId'] = $funcitem['menuId'];
//                    $list['children'][] = $fList;
//                    unset($funcs[$key]);
//                }

            //TODO 方法二: 根据menuID获取对应的功能点 增加网络开销
            $funcs = $this->funcData->getFuncByMenuId2($item['id'],$menusFuncs);
            if (count($funcs) != 0)
                $list["children"] = $funcs;
            $third[] = $list;
        }
        unset($thirdGroupMenus);
        //通过子类调用父类一直递归到parentId为0为止
        $groupByParentId = [];
        foreach ($third as $item) {
            $groupByParentId[$item['parentId']][] = $item;
        }
        //$result_tree = [];
        //foreach ($groupByParentId as $key => $item) {
        //    $result_tree[] = $this->menuData->res_parent_tree($key,$item);
        //}
        $result_tree = $this->menuData->res_parent_tree($groupByParentId);
        $params = [
            'code' => '10016',
            'parameter' => [
                'groupId' => $id,
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
                'groupId' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $funcs = [];
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $result_funcs = $result_funcs['content']['groupFunctionDOS'];
            foreach ($result_funcs as $item) {
                $funcs[] = $item['menufuncId'];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }
        if (count($result_tree)) {
            $result = ["tree" =>$result_tree[0],"menus"=> $menus ,"funcs" => $funcs];
        } else {
            $result = ["tree" =>$result_tree,"menus"=> $menus ,"funcs" => $funcs];
        }

        return $this->toSuccess($result);
    }





    //$pointTree = $this->sortArrByManyField($pointTree,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
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
                    if(is_object($val))
                        $temp[$index] = $val->$field;
                }
                $args[$key] = $temp;
            }
        }
        $args[] = &$arr;//引用值
        call_user_func_array('array_multisort',$args);
        return array_pop($args);
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




    /**
     * update用户组权限
     */
    public function AuthAction($id)
    {
        //根据用户组删除之前的所有数据
        $params = [
            'code' => '10034',
            'parameter' => [
                'groupId' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200')
            return $this->toError($result['statusCode'],$result['msg']);

        $params = [
            'code' => '10024',
            'parameter' => [
                'groupId' => $id,
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
//            $parameter = ["groupId" => $id,"menuId" => $item,"level" => 3];
//            $params = [
//                'code' => '10015',
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
                $list['groupId'] = $id;
                $list['menuId'] = $item;
                $list['level'] = 3;
                $json['data'][] = $list;

            }
            $params = [
                'code' => '10081',
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
//            $parameter = ["groupId" => $id,"menufuncId" => $item];
//            $params = [
//                'code' => '10033',
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
                $list['groupId'] = $id;
                $list['menufuncId'] = $item;
                $json['data'][] = $list;
            }
            $params = [
                'code' => '10083',
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
     *
     * @param int $apiStatus
     * @param int $moduledId
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 获取api列表
     *
     * TODO： $moduleId 是什么
     */
    private function getUserGroupList($groupStatus,$groupType)
    {
        $groupList = [];
        $parameter = [];
        if (!empty($groupStatus))
            $parameter['groupStatus'] = $groupStatus;
        if (!empty($groupType))
            $parameter['groupType'] = $groupType;
        if (empty($parameter)) {
            $params = ["code" => "10014"];
        } else {
            $params = ["code" => "10014", "parameter" => $parameter];
        }
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == '200') {
            $result = $result['content'];
            if (is_array($result) && count($result) > 1) {
                foreach ($result as $item) {
                    $api = [];
                    $api["id"] = $item["id"];
                    $api["groupName"] = $item["groupName"];
                    $api["groupCode"] = $item["groupCode"];
                    $groupList[] = $api;
                }
            } else {
                $groupList = (array)$result;
            }
            return $this->toSuccess($groupList);
        }
        return $this->toError($result['statusCode'], $result['msg']);
    }


    /**
     * 生成随机CODE
     */
    public function CodeAction()
    {
        $code = rand(100000,999999);
        $groups = $this->getUserGroupList(null,null);
        $items = [];
        foreach ($groups as $item) {
            $items[] = $item['groupCode'];
        }
        do {
            if (!in_array($code,$items)) {
                break;
            }
            $code = rand(100000,999999);
        } while (1==1);

        return $this->toSuccess($code);
    }


    /**
     * @param $id
     *
     * 优化版
     * 只返回对应的3级菜单数组和功能点数组
     */
    public function GroupMenuAction($id)
    {
        //根据groupId 获取 group_type
        $userGroup = $this->userGroupData->getUserGroupById($id);
        if ($userGroup == false) throw new AppException(['500','用户组不存在!请联系管理员']);
        $userGroupType = $userGroup['groupType'];
        unset($userGroup);

        $systemMenus = $this->modelsManager->createBuilder()
            ->columns('m.id,m.menu_name,m.menu_code,m.menu_system,m.parent_id,m.menu_level,m.menu_order,
            mf.id as funcId,mf.func_name,mf.func_code,mf.status')
            ->addFrom('app\models\users\Menu','m')
            ->leftJoin('app\models\users\MenuFunction','m.id = mf.menu_id','mf')
            ->andWhere('m.menu_system = :menu_system: and m.menu_status = 1', ['menu_system' => $this->system])
            ->inWhere('m.menu_type',[0,$userGroupType])
            ->orderBy('m.menu_level asc,m.menu_order asc')
            ->getQuery()
            ->execute()->toArray();

        $tree = [];
        $lists = [];
        $del = [];
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
                /**
                 * 删除 没有下级的menu
                 */
                $del[] = $item['id'];
                if (in_array($item['parent_id'],$del)) {
                    //array_merge(array_diff($del, array($list['parentId'])));
                    $key=array_search($item['parent_id'] ,$del);
                    array_splice($del,$key,1);
                }
                if ($item['menu_level'] == 3) {
                    if (isset($item['funcId']) && $item['funcId'] > 0) {
                        $list = [];
                        $list['id'] = $item['funcId'];
                        $list['type'] = 'func';
                        $list['title'] = $item['func_name'];
                        $list['menuCode'] = $item['func_code'];
                        $list['parentId'] = $item['id'];
                        $tree[] = $list;
                        if (in_array($item['id'],$del)) {
                            $key=array_search($item['id'],$del);
                            array_splice($del,$key,1);
                        }
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
                        if (in_array($item['id'],$del)) {
                            $key=array_search($item['id'],$del);
                            array_splice($del,$key,1);
                        }
                    }
                }
            }
        }
        foreach ($tree as $key => $item) {
            if (in_array($item['id'],$del)) {
                unset($tree[$key]);
            }
        }
        $tree = array_values($tree);
        $menus = [];
        //获取已存的menuID
        $groupMenus = GroupMenu::find(['columns' => 'menu_id','conditions' => 'group_id = :groupId:','bind' => ['groupId' => $id]])->toArray();

        foreach ($groupMenus as $item) {
            $menus[] = $item['menu_id'];
        }
        $funcs = [];
        //获取已存的funcID
        $groupFuncs = GroupFunction::find(['columns' => 'menufunc_id','conditions' => 'group_id = :groupId:','bind' => ['groupId' => $id]])->toArray();
        foreach($groupFuncs as $item) {
            $funcs[] = $item['menufunc_id'];
        }


//        $menuRelations = [];
//
//        //创建所有父子级关系
//        foreach($systemMenus as $item) {
//            if (!isset($menuRelations[$item['id']])) {
//                $menuRelations[$item['id']] = $item['parent_id'];
//            }
//        }
//
//        $tree = [];
//        foreach($systemMenus as $item) {
//            if ($item['menu_level'] == 1) {
//                if (!isset($tree[$item['id']])) {
//                    $list = [];
//                    $list['id'] = $item['id'];
//                    $list['type'] = 'menu';
//                    $list['title'] = $item['menu_name'];
//                    $list['menuCode'] = $item['menu_code'];
//                    $list['menuLevel'] = $item['menu_level'];
//                    $list['menuSystem'] = $item['menu_system'];
//                    $list['menuOrder'] = $item['menu_order'];
//                    $list['parentId'] = $item['parent_id'];
//                    $list['expand'] = true;
//                    $tree[$item['id']] = $list;
//                }
//            }
//
//            if ($item['menu_level'] == 2) {
//                if (!isset($tree[$item['parent_id']][$item['id']])) {
//                    $list = [];
//                    $list['id'] = $item['id'];
//                    $list['type'] = 'menu';
//                    $list['title'] = $item['menu_name'];
//                    $list['menuCode'] = $item['menu_code'];
//                    $list['menuLevel'] = $item['menu_level'];
//                    $list['menuSystem'] = $item['menu_system'];
//                    $list['menuOrder'] = $item['menu_order'];
//                    $list['parentId'] = $item['parent_id'];
//                    $list['expand'] = true;
//                    $tree[$item['parent_id']]['children'][$item['id']] = $list;
//                }
//            }
//
//            if ($item['menu_level'] == 3) {
//                if (!isset($tree[$menuRelations[$item['parent_id']]]['children'][$item['parent_id']]['children'][$item['id']])) {
//                    $list = [];
//                    $list['id'] = $item['id'];
//                    $list['type'] = 'menu';
//                    $list['title'] = $item['menu_name'];
//                    $list['menuCode'] = $item['menu_code'];
//                    $list['menuLevel'] = $item['menu_level'];
//                    $list['menuSystem'] = $item['menu_system'];
//                    $list['menuOrder'] = $item['menu_order'];
//                    $list['parentId'] = $item['parent_id'];
//                    $list['expand'] = true;
//                    if (isset($item['func_name'])) {
//                        $f = [];
//                        $f['id'] = $item['funcId'];
//                        $f['type'] = 'func';
//                        $f['title'] = $item['func_name'];
//                        $f['funcCode'] = $item['func_code'];
//                        $list['children'] = $f;
//                    }
//                    $tree[$menuRelations[$item['parent_id']]]['children'][$item['parent_id']]['children'][$item['id']] = $list;
//                } else {
//                        $f = [];
//                        $f['id'] = $item['funcId'];
//                        $f['type'] = 'func';
//                        $f['title'] = $item['func_name'];
//                        $f['funcCode'] = $item['func_code'];
//                    $tree[$menuRelations[$item['parent_id']]]['children'][$item['parent_id']]['children'][$item['id']]['children'][] = $f;
//                }
//            }
//
//        }
//
//        $menus = [];
//        //获取已存的menuID
//        $groupMenus = GroupMenu::find(['columns' => 'menu_id','conditions' => 'group_id = :groupId:','bind' => ['groupId' => $id]])->toArray();
//
//        foreach ($groupMenus as $item) {
//            $menus[] = $item['menu_id'];
//        }
//        $funcs = [];
//        //获取已存的funcID
//        $groupFuncs = GroupFunction::find(['columns' => 'menufunc_id','conditions' => 'group_id = :groupId:','bind' => ['groupId' => $id]])->toArray();
//        foreach($groupFuncs as $item) {
//            $funcs[] = $item['menufunc_id'];
//        }
//        $resultTree = [];
//        self::foo($tree);
//        foreach($tree as $item) {
//            $resultTree[$item['menuOrder']] = $item;
//        }
//        $tree = array_values($resultTree);
        $result = ['tree' => $tree,'menus' => $menus,'funcs' => $funcs];
        return $this->toSuccess($result);

    }
    private static function foo(&$ar) {

        if(! is_array($ar)) return;

        foreach($ar as $k=>&$v) {

            if(is_array($v)) self::foo($v);

            if($k == 'children') $v = array_values($v);

        }

    }



    private function getGroupMenuIdLists($groupId) {
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
            return $menus;
        }
        throw new DataException($result_menus['statusCode'],$result_menus['msg']);
    }
}

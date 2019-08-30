<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: MenuController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\auth;


use app\modules\BaseController;
use app\common\errors\AppException;
use app\common\errors\MicroException;

class MenuController extends BaseController
{

    /**
     * 根据用户信息和系统ID获取相应的menu和方法
     */
    public function IndexAction()
    {
        $authed = $this->authed;

        //获取menu
        $menu = $this->menuTree($this->system,$authed->isAdministrator,$authed->groupId,$authed->roleId);
        //获取func
        $funcs = $this->getFuncs($this->system,$authed->isAdministrator,$authed->groupId,$authed->roleId);

        /**
         * 递归调用排序 array_multisort(array_column($arr,'age'),SORT_DESC,$arr);
         */
        $menu = $this->recursion_orderby($menu,'menuOrder','children',SORT_ASC);
        $result = ['menu' => $menu, 'funcs' => $funcs];
        return $this->toSuccess($result);

    }


    /**
     * 递归根据特定key对数组排序
     * @param $data
     * @param string $orderKey
     * @param string $sonKey
     * @param int $orderBy
     * @return mixed
     */
    function recursion_orderby($data, $orderKey = 'order', $sonKey = 'children', $orderBy = SORT_ASC)
    {
        $func = function ($value) use ($sonKey, $orderKey, $orderBy) {
            if (isset($value[$sonKey]) && is_array($value[$sonKey])) {
                $value[$sonKey] = $this->recursion_orderby($value[$sonKey], $orderKey, $sonKey, $orderBy);
            }
            return $value;
        };
        return $this->array_orderby(array_map($func, $data), $orderKey, $orderBy);
    }

    function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }



    /**
     * @param $isAdmin
     * @param $groupId
     * @param $roleId
     * @return array
     * @throws AppException
     * @throws MicroException
     * @throws \app\common\errors\CurlException
     *
     * 如果admin为2 则使用groupId获取menutree 反之为roleId获取menutree
     */
    private function menuTree($system = 0,$isAdmin = 1,$groupId = null,$roleId = null)
    {
        $allMenu = array();
        $systemMenuResult = $this->menuData->getMenuList($system,1,null,null);
        foreach($systemMenuResult as $item) {
            $allMenu[$item['id']] = $item;
        }

        //根据用户组查询菜单
        if ($system == 0 && $isAdmin == 2) {
            //主系统主账号管理员
            return $this->getGroupMenu($this->system,$groupId,$allMenu);
        } else if ($system == 0 && $isAdmin == 1) {
            //主系统非主账号
            return $this->getRoleMenu($this->system,$groupId,$roleId,$allMenu);
        } else if ($system != 0 && $isAdmin == 2) {
            //子系统超级管理员
            return $this->getSystemMenu($this->system,$allMenu,$allMenu);
        } else if ($system != 0 && $isAdmin == 1) {
            //子系统非管理员
            return $this->getRoleMenu($this->system,$groupId,$roleId,$allMenu);
        }
        //后台超级管理员 展示所有数据
        if ($isAdmin == 0) {
            $menus = $this->getMenuList();
            //$arr = $this->sortArrByManyField($menus,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
            //unset($menus);
            $result = $this->res_tree($menus,0);  //理论上不用进行递归树
            return $result;
        } else {
            return [];
        }
    }


    private function getSystemMenu($system,&$allMenu)
    {
        $commonMenus = $this->menuData->getMenuList($system,1,3, null);
        $thirdGroupMenus = [];
        foreach($commonMenus as $k => $v) {
            $list = [];
            $list['title'] = $v['menuName'];
            $list['name'] = $v['menuName'];
            $list['path'] = $v['menuUri'];
            $list['url'] = $v['menuUri'];
            $list['code'] = $v['menuCode'];
            $list['icon'] = "ICON";
            $list['menuLevel'] = $v['menuLevel'];
            $list['menuOrder'] = $v['menuOrder'];
            $list['parentId'] = $v['parentId'];
            $thirdGroupMenus[] = $list;
        }

        /**
         * 根据level和order排序，目前只需要根据order排序
         */
        //$thirdGroupMenus = $this->sortArrByManyField($thirdGroupMenus,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
        //通过子类调用父类一直递归到parentId为0为止
        $groupByParentId = [];
        foreach ($thirdGroupMenus as $item) {
            $groupByParentId[$item['parentId']][] = $item;
        }
        $result_tree = $this->menuData->res_parent_menu_tree($groupByParentId,$allMenu);
        if (count($result_tree)){
            return $result_tree[0];
        }
        return $result_tree;

    }


    /**
     * @param $id
     * @return array
     * @throws AppException
     * @throws MicroException
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     *  获取用户组菜单树
     */
    private function getGroupMenu($system,$id,&$allMenu)
    {

        /**
         * 获取用户组类型
         */
        $userGroup = $this->userGroupData->getUserGroupById($id);
        if ($userGroup == false) throw new AppException(['500','用户组不存在!请联系管理员']);
        $userGroupType = $userGroup['groupType'];
        unset($userGroup);

        /**
         * 获取用户对应用户组的menus   过滤
         */
        //获取角色对应的menuIds
        $params = [
            'code' => '10016',
            'parameter' => [
                'groupId' => $id,
            ]
        ];
        $result_menus = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_menus['statusCode'] == '200') {
            $menus = [];
            $menus_result = $result_menus['content']['groupMenuDOS'];
            foreach ($menus_result as $item) {
                $menus[] = $item["menuId"];
            }
        } else {
            return $this->toError($result_menus['statusCode'],$result_menus['msg']);
        }
        unset($result_menus);
        //return $this->menuData->getTreeUse($system,$userGroupType,$menus);

        /**
         * 获取用户组菜单
         * menuType = 0 为通用菜单
         */
        $commonMenus = $this->menuData->getMenuList($system,1,3,0);
        $groupTypeMenus = $this->menuData->getMenuList($system,1,3,$userGroupType);
        $arr = array_merge($commonMenus,$groupTypeMenus);
        //合并俩个数组
        $key = "id";//去重条件
        $thirdGroupMenus = array();
        foreach($arr as $k => $v) {
            if (in_array($v[$key], $thirdGroupMenus)) {
                unset($arr[$k]);
                //删除掉数组（$arr）里相同ID的数组
            } else {
                if(in_array($v['id'],$menus)) {
                    $list = [];
                    $list['title'] = $v['menuName'];
                    $list['name'] = $v['menuName'];
                    $list['path'] = $v['menuUri'];
                    $list['url'] = $v['menuUri'];
                    $list['code'] = $v['menuCode'];
                    $list['icon'] = "ICON";
                    $list['menuLevel'] = $v['menuLevel'];
                    $list['menuOrder'] = $v['menuOrder'];
                    $list['parentId'] = $v['parentId'];
                    $thirdGroupMenus[] = $list;
                }
            }
        }

        /**
         * 根据level和order排序，目前只需要根据order排序
         */
        //$thirdGroupMenus = $this->sortArrByManyField($thirdGroupMenus,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
        //通过子类调用父类一直递归到parentId为0为止
        $groupByParentId = [];
        foreach ($thirdGroupMenus as $item) {
            $groupByParentId[$item['parentId']][] = $item;
        }

        $result_tree = $this->menuData->res_parent_menu_tree($groupByParentId,$allMenu);
        if (count($result_tree)){
            return $result_tree[0];
        }
        return $result_tree;
    }




    private function getRoleMenu($system,$groupId,$roleId,&$allMenu)
    {
        //获取角色对应的menuIds
        $params = [
            'code' => '10025',
            'parameter' => [
                'roleId' => $roleId,
            ]
        ];
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


        $userGroup = $this->userGroupData->getUserGroupById($groupId);
        if ($userGroup == false) throw new AppException(['500','用户组不存在!请联系管理员']);
        $userGroupType = $userGroup['groupType'];
        unset($userGroup);
        $commonMenus = $this->menuData->getMenuList($system,1,3,0);
        $groupTypeMenus = $this->menuData->getMenuList($system,1,3,$userGroupType);
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
        //$thirdGroupMenus = $this->sortArrByManyField($thirdGroupMenus,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
        //TODO 排序 order 怎么解决
        unset($arr);
        unset($commonMenus);
        unset($groupTypeMenus);
        $third = [];
        foreach ($thirdGroupMenus as $item) {
            if (!in_array($item['id'],$menus)) {
                continue;
            }
            $list = [];
            $list['title'] = $item['menuName'];
            $list['name'] = $item['menuName'];
            $list['path'] = $item['menuUri'];
            $list['url'] = $item['menuUri'];
            $list['code'] = $item['menuCode'];
            $item['icon'] = "ICON";
            $list['menuLevel'] = $item['menuLevel'];
            $list['menuOrder'] = $item['menuOrder'];
            $list['parentId'] = $item['parentId'];
            $third[] = $list;
        }
        unset($thirdGroupMenus);
        unset($resultFuncs);
        //通过子类调用父类一直递归到parentId为0为止
        $groupByParentId = [];
        foreach ($third as $item) {
            $groupByParentId[$item['parentId']][] = $item;
        }
        $result_tree = $this->menuData->res_parent_menu_tree($groupByParentId,$allMenu);
        if (count($result_tree)){
            return $result_tree[0];
        }
        return $result_tree;
    }

    /**
     * @return bool
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 获取menu列表
     */
    private function getMenuList()
    {
        //TODO： 不同用户获取不同的menu tree
        //查询所有 可用menu
        $parameter = ["menuStatus" => 1];
        $params = [
            'code' => '10021',
            'parameter' => $parameter
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] == '200') {
            return  $result['content']['menus'];
        } else {
            return false;
        }
    }


    private function getFuncs($system,$isAdmin,$groupId = null,$roleId = null)
    {
        //根据用户组查询菜单
        if ($system == 0 && $isAdmin == 2) {
            //主系统主账号管理员 功能点
            return $this->getGroupFuncCodes($system,$groupId);
        } else if ($system == 0 && $isAdmin == 1) {
            //主系统非主账号
            return $this->getRoleFuncCodes($system,$roleId);

        } else if ($system != 0 && $isAdmin == 2) {
            //子系统超级管理员
            return $this->getSystemCodes($system);

        } else if ($system != 0 && $isAdmin == 1) {
            //子系统非管理员
            return $this->getRoleFuncCodes($system,$roleId);

        }

        if ($isAdmin == 0) {
            return $this->getFuncCodes($system);
        }

        return [];
    }

    private function getFuncCodes($system)
    {
        $object = ['system'=> $system];
        $params = [
            'code' => '10032',
            'parameter' => (Object)$object
        ];
        //调用微服务接口获取数据
        $funcs = [];
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $result_funcs = $result_funcs['content']['menuFunctions'];
            foreach ($result_funcs as $item) {
                $funcs[] = $item['funcCode'];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }
        return $funcs;

    }

    private function getGroupFuncCodes($system,$groupId)
    {
        $params = [
            'code' => '10035',
            'parameter' => [
                'groupId' => $groupId,
            ]
        ];
        //调用微服务接口获取数据
        $funcs = [];
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $result_funcs = $result_funcs['content']['groupFunctionDOS'];
            foreach ($result_funcs as $item) {
                $funcs[] = $item['funcCode'];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }
        return $funcs;

    }


    private function getRoleFuncCodes($system,$roleId)
    {
        $params = [
            'code' => '10047',
            'parameter' => [
                'roleId' => $roleId,
            ]
        ];
        //调用微服务接口获取数据
        $funcs = [];
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $result_funcs = $result_funcs['content']['roleFunctions'];
            foreach ($result_funcs as $item) {
                $funcs[] = $item['funcCode'];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }
        return $funcs;
    }


    private function getSystemCodes($system){

        $params = [
            'code' => '10080',
            'parameter' => [
                'id' => $system,
            ]
        ];
        //调用微服务接口获取数据
        $funcs = [];
        $result_funcs = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_funcs['statusCode'] == '200') {
            $result_funcs = $result_funcs['content']['data'];
            foreach ($result_funcs as $item) {
                $funcs[] = $item['funcCode'];
            }
        } else {
            return $this->toError($result_funcs['statusCode'],$result_funcs['msg']);
        }
        return $funcs;

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
                $item = [];
                $item['title'] = $v['menuName'];
                $item['path'] = $v['menuUri'];
                $item['name'] = $v['menuName'];
                $item['icon'] = "ICON"; //TODO： icon从哪里来
                $item['code'] = $v['menuCode'];
                $item['parentId'] = $v['parentId'];
                $item['menuLevel'] = $v['menuLevel'];
                $item['parentId'] = $v['parentId'];
                $item['id'] = $v['id'];
                $item['children'] = self::res_tree($tree,$item['id']); //递归获取子记录
                $result[] = $item;
//                    if($v['children'] == null){
//                        unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
//                    }

            }
        }
        return $result;  //返回新数组
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


}
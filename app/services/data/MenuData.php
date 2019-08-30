<?php
namespace app\services\data;
use app\common\errors\DataException;



/**
 * Class RoleData
 * @package app\services\data
 * 对角色数据进行封装
 * DI注入
 * 方便分离服务层和数据层的问题
 */
class MenuData extends BaseData
{

    /**
     * @param $groupId
     * @return mixed
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 根据用户组ID获取菜单
     */
    public function getMenusByGroupId($groupId)
    {
        $params = [
            'code' => '10016',
            'parameter' => [
                'groupId' => $groupId,
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        //判断数据格式是否存在
        if ( !isset($result['content']['groupMenuDOS']))
            throw new DataException([500,"数据格式不存在"]);
        return $result['content']['groupMenuDOS'];
    }


    /**
     * @return bool
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 获取menu列表
     */
    public function getMenuList($menuSystem = 0,$menuStatus = 1,$menuLevel = null,$menuType = null)
    {
        $parameter = [];
        if (isset($menuLevel))
            $parameter['menuLevel'] = $menuLevel;
        if (isset($menuSystem))
            $parameter['menuSystem'] = $menuSystem;
        if (isset($menuStatus))
            $parameter['menuStatus'] = $menuStatus;
        if (isset($menuType))
            $parameter['menuType'] = $menuType;
        $params = [
            'code' => '10021',
            'parameter' => $parameter
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        //判断数据格式是否存在
        if ( !isset($result['content']['menus']))
            throw new DataException([500,"数据格式不存在"]);
        return  $result['content']['menus'];

    }

    /**
     * @param null $roleId
     * @return mixed
     * @throws DataException
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 获取角色菜单列表
     */
    public function getRoleMenuList($roleId = null)
    {
        $parameter = [];
        if (!isset($roleId))
            $parameter['roleId'] = $roleId;
        $params = [
            'code' => '10025',
            'parameter' => $parameter
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        //判断数据格式是否存在
        if ( !isset($result['content']['roleMenus']))
            throw new DataException([500,"数据格式不存在"]);
        return  $result['content']['roleMenus'];
    }


    public function getMenu($id)
    {
        $parameter = ["id" => $id];
        $params = [
            'code' => '10021',
            'parameter' => $parameter
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        //判断数据格式是否存在
        if ( !isset($result['content']['menus']))
            throw new DataException([500,"数据格式不存在"]);
        return  $result['content']['menus'];
    }



    //一直递归调用直到所有的parentId为0然后返回
    public function res_parent_tree($arr){
        $i = 0;
        $parents = [];
        foreach ($arr as $key => $item) {
            if(isset($item[0]['parentId']) && $item[0]['parentId'] == 0){
                $i++;
                continue;
            }
            $parent_result = $this->menuData->getMenu($key);
            if (isset($parent_result[0]['id']) && $parent_result[0]['id'] != 0) {
                $list = [];
                $list['id'] = $parent_result[0]['id'];
                $list['type'] = 'menu';
                $list['title'] = $parent_result[0]['menuName'];
                $list['menuCode'] = $parent_result[0]['menuCode'];
                $list['menuType'] = $parent_result[0]['menuType'];
                $list['menuSystem'] = $parent_result[0]['menuSystem'];
                $list['menuOrder'] = $parent_result[0]['menuOrder'];
                $list['parentId'] = $parent_result[0]['parentId'];
                $list['expand'] = true;
                $list['children'] = $item;
                $parents[$list['parentId']][] = $list;
            }
        }
        if (count($arr) == $i) {
            return $arr;
        }
        return self::res_parent_tree($parents);

    }




    public function res_parent_menu_tree($arr,&$allMenu){
        $i = 0;
        $parents = [];
        foreach ($arr as $key => $item) {
            if(isset($item[0]['parentId']) && $item[0]['parentId'] == 0){
                $i++;
                continue;
            }
            //$parent_result = $this->menuData->getMenu($key);
            $parent_result = $allMenu[$key];
//            if (isset($parent_result[0]['id']) && $parent_result[0]['id'] != 0) {
//                $list = [];
//                $list['title'] = $parent_result[0]['menuName'];
//                $list['name'] = $parent_result[0]['menuName'];
//                $list['path'] = $parent_result[0]['menuUri'];
//                $list['url'] = $parent_result[0]['menuUri'];
//                $list['code'] = $parent_result[0]['menuCode'];
//                $list['icon'] = "ICON";
//                $list['parentId'] = $parent_result[0]['parentId'];
//                $list['children'] = $item;
//                $parents[$list['parentId']][] = $list;
//            }
            if (isset($parent_result['id']) && $parent_result['id'] != 0) {
                $list = [];
                $list['title'] = $parent_result['menuName'];
                $list['name'] = $parent_result['menuName'];
                $list['path'] = $parent_result['menuUri'];
                $list['url'] = $parent_result['menuUri'];
                $list['code'] = $parent_result['menuCode'];
                $list['icon'] = "ICON";
                $list['parentId'] = $parent_result['parentId'];
                $list['menuOrder'] = $parent_result['menuOrder'];
                $list['children'] = $item;
                $parents[$list['parentId']][] = $list;
            }
        }

        if (count($arr) == $i) {
            return $arr;
        }
        return self::res_parent_menu_tree($parents,$allMenu);

    }

    /**
     * 获取已选区域树
     */
    public function getTreeUse($system,$userGroupType,$ids)
    {
        // 获取全部区域
        $commonMenus = $this->menuData->getMenuList($system,1,null,0);
        $groupTypeMenus = $this->menuData->getMenuList($system,1,null,$userGroupType);
        $menus = array_merge($commonMenus,$groupTypeMenus);
        $data = $this->sortArrByManyField($menus,'menuLevel',SORT_ASC,'menuOrder',SORT_ASC);
        //三级菜单







//        //合并俩个数组
//        unset($result);
//        // 处理数据结构，方便递归
//        $tmpParent = [];
//        $tmpSelf = [];
//        foreach ($data as $val){
//            // 强制字符串关联数组
//            // 父子级关系
//            $tmpParent[(string)$val['parentId']][] = [
//                'id' => $val['id']
//            ];
//            $tmpSelf[(string)$val['id']] = [
//                'title' => $val['menuName'],
//                'name' => $val['menuName'],
//                'path' => $val['menuUri'],
//                'url' => $val['menuUri'],
//                'code' => $val['menuCode'],
//                'icon' => "ICON",
//                'menuLevel' => $val['menuLevel'],
//                'menuOrder' => $val['menuOrder'],
//                'parentId' => $val['parentId'],
//                'id' => $val['id']
//            ];
//        }
//        unset($data);
//        // 初始化数据
//        $data = [];
//        //fix
//
//        foreach ($ids as $id){
//            $data[] = ['id' => $id];
//        }
//        // 获取子级数据
//        $this->ChildTreeRecursive($tmpParent, $tmpSelf, $data);
//        // 父级树
//        $this->ParentTreeRecursive($tmpSelf, $data);
//        return $data;
    }

    /**
     * 处理子级树
     */
    public function ChildTreeRecursive(&$tmpParent, &$tmpSelf, &$data){
        // 递归处理区域树
        foreach ($data as $k => $v){
            if (isset($tmpSelf[(string)$v['id']])) {
                $data[$k] = $tmpSelf[(string)$v['id']];
                // unset($tmpSelf[(string)$v['areaId']]);
                if (isset($tmpParent[(string)$v['id']])){
                    // 如有子级区域，加入
                    $data[$k]['children'] = $tmpParent[(string)$v['id']];
                    $this->ChildTreeRecursive($tmpParent, $tmpSelf, $data[$k]['children']);
                }
            } else {
                throw new DataException([500,'非法区域数据,请检查数据!']);
            }
        }
    }
    /**
     * 处理父级树
     */
    public function ParentTreeRecursive(&$tmpSelf, &$data){
        // 处理数据便于父级递归
        $dataParent = [];
        $tmpData = [];
        foreach ($data as $k => $val){
            $dataParent[(string)$val['parentId']][] = $val['id'];
            $tmpData[(string)$val['parentId']][] = $val;
            unset($data[$k]);
        }
        // 结束状态
        $over = true;
        // 递归处理区域树
        foreach ($tmpData as $k => $v){
            // 顶级 过
            if (0==$k) continue;
            $over = false;
            // 父级写入
            $data[$k] = $tmpSelf[(string)$k];
            // 子级写入
            $data[$k]['children'] = $v;
        }
        // 结束判定
        if (!$over) {
            unset($tmpData);
            // 获取父级数据
            return $this->ParentTreeRecursive($tmpSelf, $data);
        }else{
            // 地址赋值
            $data = $tmpData['0'];
            return;
        }
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



    private  function res_tree($tree,$pid){
        $result = array();                                //每次都声明一个新数组用来放子元素
        foreach($tree as $key => $v){
            if($v['parentId'] == $pid){//匹配子记录
                $item = [];
                $item['title'] = $v['menuName'];
                $item['path'] = $v['menuUri'];
                $item['name'] = $v['menuName'];
                $item['icon'] = "ICON"; //TODO： icon从哪里来
                $item['code'] = $v['menuCode'];
                $item['parentId'] = $v['parentId'];
                $item['menuLevel'] = $v['menuLevel'];
                $item['menuOrder'] = $v['menuOrder'];
                $item['parentId'] = $v['parentId'];
                $item['id'] = $v['id'];
                $item['children'] = self::res_tree($tree,$item['id']); //递归获取子记录
                unset($tree[$key]);
                $result[] = $item;
//                    if($v['children'] == null){
//                        unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
//                    }
            }
        }
        return $result;  //返回新数组
    }



}
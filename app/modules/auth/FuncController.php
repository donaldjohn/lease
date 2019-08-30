<?php
namespace app\modules\auth;


use app\common\errors\AppException;
use app\modules\BaseController;
use phpDocumentor\Reflection\Types\Object_;

//功能模块
class FuncController extends BaseController
{
    /**
     * 查询功能块
     * code： 10021
     */
    public function listAction()
    {
        /**
         * 如果传过来的参数中有type则执行不分页查找
         */
        $type = $this->request->getQuery("type","string","page");
        $menuStatus = $this->request->getQuery("menuStatus","int",null);
        $menuLevel = $this->request->getQuery("menuLevel","int",null);//1,2,3
        $menuType = $this->request->getQuery("menuType","int",null);
        $menuCode = $this->request->getQuery("menuCode","string",null);
        $menuSystem = $this->request->getQuery("menuSystem","string",null);
        if ($type != "page") {
            return $this->getFuncList($menuStatus,$menuLevel,$this->system,$menuType);
        }
        /**
         * 一般情况下进行分页查询
         */
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = ["pageNum" => (int)$pageNum,"pageSize" => (int)$pageSize];

        /**
         * 参数绑定
         */
        if (!empty($this->request->getQuery('menuName', "string",null))){
            $parameter['menuName'] = $this->request->getQuery('menuName', "string");
        }
        if (!empty($menuStatus)){
            $parameter['menuStatus'] = (int)$menuStatus;
        }
        if (!empty($menuLevel)){
            $parameter['menuLevel'] = (int)$menuLevel;
        }
        if (!is_null($menuType)){
            $parameter['menuType'] = (int)$menuType;
        }
        if (!empty($menuCode)){
            $parameter['menuCode'] = $menuCode;
        }
        if (!empty($menuSystem)){
            $parameter['menuSystem'] = $menuSystem;
        }


        //调用微服务接口获取数据
        $params = ["code" => "10021","parameter" => $parameter];
        $result_menus = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result_menus['statusCode'] == '200') {
            $meta['total'] = isset($result_menus['content']['pageInfo']['total']) ?  $result_menus['content']['pageInfo']['total'] : 0;
            $meta['pageNum'] = $pageNum;
            $meta['pageSize'] = $pageSize;
            $result = [];
            foreach($result_menus['content']['menus'] as $item) {
                if (isset($systems[$item['menuSystem']]))
                    $item['systemName'] = "主系统";
                // 根据parentId 查询 上级func
                if ($item['parentId'] != 0) {
                    $p_menu = $this->getFuncById($item['parentId']);
                    if($p_menu != false) {
                        $item['parentMenuName'] = $p_menu['menuName'];
                        $item['parentMenuCode'] = $p_menu['menuCode'];
                    }

                } else {
                    $item['parentMenuName'] = '无';
                    $item['parentMenuCode'] = '无';
                }
                $result[] = $item;
            }
            return $this->toSuccess($result,$meta);
        } else {
            return $this->toError($result_menus['statusCode'],$result_menus['msg']);
        }

    }

    /**
     * 查询功能块
     * code： 10021
     */
    public function oneAction($id)
    {
        //根据id查询数据
        $res = ["id" => $id];
        $json = ['code' => 10021,'parameter' => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if(count($result['content']['menus']) == 1)
                return $this->toSuccess($result['content']['menus'][0]);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 新增功能块
     * code： 10018
     */
    public function CreateAction()
    {
        $res = [];
        $request = $this->request->getJsonRawBody();
        if (!isset($request->menuName)) return $this->toError(500,"菜单名称必填");
        $res['menuName'] = $request->menuName;
        if (!isset($request->menuCode)) return $this->toError(500,"菜单Code具有唯一性");
        $res['menuCode'] = $request->menuCode;
        //if (!isset($request->menuSystem)) return $this->toError(500,"菜单系统必填");
        $res['menuSystem'] = 0;
        if (!isset($request->menuType)) return $this->toError(500,"菜单类别必填");
        $res['menuType'] = $request->menuType;
        if (!isset($request->parentId)) return $this->toError(500,"父ID必填");
            $res['parentId'] = $request->parentId;
        if (!isset($request->menuStatus)) return $this->toError(500,"菜单状态 1：启用 2:禁用必填");
        $res['menuStatus'] = $request->menuStatus;
        if (!isset($request->menuLevel)) return $this->toError(500,"	菜单级别最多三级 必填");
        $res['menuLevel'] = $request->menuLevel;
        if (!isset($request->menuOrder)) return $this->toError(500,"菜单排序必填");
        $res['menuOrder'] = $request->menuOrder;
        if (isset($request->menuUri))
            $res['menuUri'] = $request->menuUri;
        if (isset($request->menuDescribe))
            $res['menuDescribe'] = $request->menuDescribe;
        $res['createAt'] = time();
        $params = ["code" => "10018","parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * 修改功能块
     * code： 10020
     */
    public function UpdateAction($id)
    {
        $search_params = ["id" => $id];
        $json = ['code' => 10021,'parameter' => $search_params];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        unset($search_params);
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            $params = $result['content']['menus'][0];
            unset($result);
            $res = $this->request->getJsonRawBody();
            if (isset($res->menuName) && $params['menuName'] != $res->menuName) {
                $params['menuName'] = $res->menuName;
            } else {
                unset($params['menuName']);
            }
            if (isset($res->menuCode) && $params['menuCode'] != $res->menuCode) {
                $params['menuCode'] = $res->menuCode;
            } else {
                unset($params['menuCode']);
            }
//            if (isset($res->menuSystem) && $params['menuSystem'] != $res->menuSystem) {
//                $params['menuSystem'] = $res->menuSystem;
//            } else {
//                unset($params['menuSystem']);
//            }
            if (isset($res->menuType))
                $params['menuType'] = $res->menuType;
            if (isset($res->parentId))
                $params['parentId'] = $res->parentId;

            if (isset($res->menuStatus) && $params['menuStatus'] != $res->menuStatus) {
                $status = $this->checkMenuStatus($id,$params['menuLevel'],$params['parentId'],$res->menuStatus);
                if ($status['code'] == 500)
                    return $this->toError(500,$status['msg']);
            }
                $params['menuStatus'] = (int)$res->menuStatus;
            if (isset($res->menuLevel))
                $params['menuLevel'] = (int)$res->menuLevel;
            if (isset($res->menuOrder))
                $params['menuOrder'] = $res->menuOrder;
            if (isset($res->menuUri))
                $params['menuUri'] = $res->menuUri;
            if (isset($res->menuDescribe))
                $params['menuDescribe'] = $res->menuDescribe;
            $params['updateAt'] = time();
            $put_params = ["code" => "10020","parameter" => $params];
            $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
            if (isset($result['statusCode']) && $result['statusCode'] == '200') {
                return $this->toSuccess($params);
            } else {
                return $this->toError($result['statusCode'],$result['msg']);
            }
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }

    }

    /**
     * 删除功能块
     * code： 10019
     */
    public function DeleteAction($id)
    {
        $res = ["id" => $id];
        $params = ["code" => "10019","parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $this->toSuccess($result['content']);
        } else {
            return $this->toError($result['statusCode'],$result['msg']);
        }
    }


    /**
     * @param $menuStatus
     * @param $menuLevel
     * @param $enuSystem
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     *
     * 获取功能块列表
     */
    private function getFuncList($menuStatus,$menuLevel,$menuSystem)
    {
        $parameter = [];
        if (!empty($menuStatus))
            $parameter['menuStatus'] = $menuStatus;
        if (!empty($menuLevel))
            $parameter['menuLevel'] = $menuLevel;
        if (!empty($menuSystem))
            $parameter['menuSystem'] = $menuSystem;
        if (!empty($menuType))
            $parameter['menuType'] = $menuType;
        $params = ["code" => "10021","parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            $result = $result['content'];
            if (is_array($result) && count($result) > 1) {
                foreach ($result as $item) {
                    $api = [];
                    $api["id"] = $item["id"];
                    $api["menuName"] = $item["menuName"];
                    $api["menuCode"] = $item["menuCode"];
                    $api["menuSystem"] = $item["menuSystem"];
                    $busList[] = $api;
                }
            } else {
                $busList = (array)$result;
            }
            return $this->toSuccess($busList);
        }
        return $this->toError($result['statusCode'],$result['msg']);
    }


    //返回未使用的Order
    public function OrderAction()
    {
        $parameter = [];
        $menuLevel = $this->request->getQuery('menuLevel', "int",null);
        if (!empty($menuLevel)){
            $parameter['menuLevel'] = (int)$this->request->getQuery('menuLevel', "int");
        }
        $order = [];
        $big = 1;
        if (count($parameter)  == 0) {
            $params = ["code" => "10021"];
        } else {
            $params = ["code" => "10021", "parameter" => $parameter];
        }
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == '200') {
            $result = $result['content']["menus"];
            if (count($result) > 1) {
                foreach ($result as $item) {
                    if($big < $item['menuOrder']) {
                        $big = $item['menuOrder'];
                    }

                    $list[] = $item['menuOrder'];
                }
                $i = 1;
                do {
                    if (!in_array($i, $list))
                        $order[] = $i;
                    $i++;
                } while ($i <= 1000);
            } else {
                $i = 1;
                do {
                    $order[] = $i;
                    $i++;
                } while ($i <= 1000);
            }
            return $this->toSuccess($order);

        }
        return $this->toError(500,"获取数据失败");
    }



    private function getFuncById($id)
    {
        $res = ["id" => $id];
        $json = [
            'code' => 10021,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$json,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if(count($result['content']['menus']) == 1)
                return $result['content']['menus'][0];
        } else {
            return false;
        }
    }


    private  function checkMenuStatus($id,$level,$parentId,$status)
    {

        if ($status == 1) {
            //之前是禁止现在开启,需要判断上级是否开启.
            if ($level == 1)
                return ['code' => 200, 'content' => true];
            $json = ['id' => $parentId,'menuStatus' => 1];
            $result = $this->funcData->getFunc($json);
            if ($result['code'] == 200) {
                if (count($result['content']) == 1) {
                    return ['code' => 200, 'content' => true];
                } else {
                    return ['code' => 500, 'content' => false,'msg' => '上级目录禁用中,请先开启!'];
                }
            } else {
                throw new AppException([$result['code'],$result['msg']]);
            }
        } else if ($status == 2) {
            //之前是开启现在禁止,需要判断下级是否全部禁止
            if ($level == 3) {
                //判断是否存在用户组里
                $params = [
                    'code' => '10016',
                    'parameter' => [
                        'menuId' => $id,
                    ]
                ];
                //调用微服务接口获取数据
                $result_menus = $this->curl->httpRequest($this->Zuul->user,$params,"post");
                if ($result_menus['statusCode'] == '200') {
                    $result_menus = $result_menus['content']['groupMenuDOS'];
                    if (count($result_menus) != 0) {
                        return ['code' => 500, 'content' => false,'msg'=>'已被使用,不能禁用'];
                    } else {
                        return ['code' => 200, 'content' => true];
                    }
                } else {
                    return $this->toError($result_menus['statusCode'],$result_menus['msg']);
                }
                return ['code' => 200, 'content' => true];
            }
            $json = ['parentId' => $id,'menuStatus' => 1];
            $result = $this->funcData->getFunc($json);
            if ($result['code'] == 200) {
                if (count($result['content']) == 0) {
                    return ['code' => 200, 'content' => true];
                } else {
                    return ['code' => 500, 'content' => false,'msg' => '下级目录开启中,请先禁用!'];
                }
            } else {
                throw new AppException([$result['code'],$result['msg']]);
            }

        }
    }
}

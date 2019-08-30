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
class FuncData extends BaseData
{


    /**
     * @param $groupId
     * @return mixed
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     *
     */
    public function getGroupFuncs($groupId)
    {
        $params = [
            'code' => '10035',
            'parameter' => [
                'id' => $groupId,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['groupFunctionDOS']))
            throw new DataException([500, "数据不存在"]);
        return $result['content']['groupFunctionDOS'];
    }



    public function getFuncList($status = 1, $menuId = null)
    {
        $parameter = [];
        if (isset($status))
            $parameter["status"] = 1;
        if (isset($menuId))
            $parameter["menuId"] = $menuId;
        $params = [
            'code' => '10032',
            'parameter' => (object)$parameter
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['menuFunctions']))
            throw new DataException([500, "数据不存在"]);
        return $result['content']['menuFunctions'];

    }

    /**
     * @param $menuId
     * @return array
     * @throws \app\common\errors\DataException
     * 根据menuId获取功能点列表 格式转换
     */
    public function getFuncByMenuId($menuId)
    {
        $result = $this->funcData->getFuncList(1,$menuId);
        $funcs = [];
        foreach ($result as $item) {
            $list = [];
            $list['id'] = $item['id'];
            $list['type'] = "func";
            $list['title'] = $item['funcName'];
            $list['funcCode'] = $item['funcCode'];
            $list['funcName'] = $item['funcName'];
            $list['parentId'] = $item['menuId'];
            $funcs[] = $list;
        }
        return $funcs;
    }

    /**
     * @param $groupId
     * @param $result
     * @return array
     */
    public function getFuncByMenuId2($id,&$result)
    {
        $funcs = [];
        foreach ($result as $item) {
            if ($item['menuId'] == $id) {
                $list = [];
                $list['id'] = $item['id'];
                $list['type'] = "func";
                $list['title'] = $item['funcName'];
                $list['funcCode'] = $item['funcCode'];
                $list['funcName'] = $item['funcName'];
                $list['parentId'] = $item['menuId'];
                $funcs[] = $list;
            }
        }
        return $funcs;
    }

    /**
     * @param $menuId
     * @return array
     * @throws \app\common\errors\DataException
     * 根据menuId获取功能点列表 格式转换
     */
    public function getFuncIds($result)
    {
        $funcs = [];
        foreach ($result as $item) {
            $funcs[] = $item['id'];
        }
        return $funcs;
    }


    public function getFunc($json)
    {
        //根据id查询数据
        $params = [
            'code' => '10021',
            'parameter' => $json
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            if(isset($result['content']['menus']))
                return ['code' => 200,'content' => $result['content']['menus'],'msg' => ''];
        } else {
            return ['code' => $result['statusCode'],'content' => '','msg' => $result['msg']];
        }

    }

}
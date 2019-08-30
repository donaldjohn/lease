<?php
namespace app\modules\traffic;

use app\models\service\Road;
use app\models\service\RoadSection;
use app\modules\BaseController;
use app\services\data\UserData;

// 道路管理
class RoadController extends BaseController
{
    // 道路列表
    public function ListAction()
    {
        $fields = [
            'searchText' => 0,
            'pageSize' => [
                'def' => 20,
            ],
            'pageNum' => [
                'def' => 1,
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        // 获取当前用户机构区域信息
        $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);
        if ($area){
            $parameter = array_merge($parameter, $area);
        }
        // 查询道路列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "15000",
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常:'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 处理时间戳
        $this->handleBackTimestamp($list);
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 新增道路
    public function AddRoadAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 省ID
            'provinceId' => '请选择省',
            // 市ID
            'cityId' => '请选择市',
            // 区ID
            'areaId' => '请选择区',
            // 道路名称
            'roadName' => '请输入道路名称',
            // 坐标
            'coordinates' => '请选择坐标',
            // 状态 0 禁用 1 启用
            'status' => '请选择状态',
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 过滤跨区域的新增
        $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);
        if (is_array($area)){
            foreach ($area as $k => $v){
                if (isset($parameter[$k]) && $parameter[$k] != $v){
                    return $this->toError(500, '所选区域超出当前机构管辖范围');
                }
            }
        }
        // 判断同区域下道路名称是否存在
        $hasRoad = Road::findFirst([
            'area_id = :area_id: AND road_name = :road_name:',
            'bind' => [
                'area_id' => $parameter['areaId'],
                'road_name' => $parameter['roadName'],
            ],
        ]);
        if ($hasRoad){
            return $this->toError(500, '同区域下道路名称已存在');
        }
        // 增加道路
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15001',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败'.$result['msg']);
        }
        $data = [
            'id' => $result['content']['id'],
        ];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 编辑道路
    public function EditRoadAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 省ID
            'provinceId' => 0,
            // 市ID
            'cityId' => 0,
            // 区ID
            'areaId' => 0,
            // 道路名称
            'roadName' => 0,
            // 坐标
            'coordinates' => 0,
            // 状态 0 禁用 1 启用
            'status' => 0,
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request, true);
        $parameter['id'] = $id;
        // 过滤跨区域的编辑
        $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);
        if (is_array($area)){
            foreach ($area as $k => $v){
                if (isset($parameter[$k]) && $parameter[$k] != $v){
                    return $this->toError(500, '所选区域超出当前机构管辖范围');
                }
            }
        }
        // 如果有道路名称 || 区域
        if (isset($parameter['roadName']) || isset($parameter['areaId'])){
            if (!(isset($parameter['roadName']) && isset($parameter['areaId']))){
                // 查询旧数据
                $road = Road::findFirst([
                    'id = :id:',
                    'bind' => [
                        'id' => $id
                    ]
                ]);
                if (false === $road){
                    return $this->toError(500, '不存在的道路');
                }
            }
            $areaId = $parameter['areaId'] ?? $road->area_id;
            $roadName = $parameter['roadName'] ?? $road->road_name;
            // 判断同区域下道路名称是否存在
            $hasRoad = Road::findFirst([
                'id != :id: AND area_id = :area_id: AND road_name = :road_name:',
                'bind' => [
                    'id' => $id,
                    'area_id' => $areaId,
                    'road_name' => $roadName,
                ],
            ]);
            if ($hasRoad){
                return $this->toError(500, '区域下道路名称已存在');
            }
        }
        // 修改道路
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15001',
            'parameter' => $parameter
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    // 删除道路
    public function DelRoadAction($id)
    {
        // 查询道路下是否有路段
        $roadSection = RoadSection::findFirst([
            'road_id = :road_id:',
            'bind' => [
                'road_id' => $id,
            ],
        ]);
        if ($roadSection){
            return $this->toError(500, '当前道路下有路段关系，不可删除');
        }
        // 删除道路
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "15002",
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    // 路段列表
    public function RoadSectionListAction()
    {
        $fields = [
            'roadId' => '无效的道路',
            'pageSize' => [
                'def' => 20,
            ],
            'pageNum' => [
                'def' => 1,
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        // 查询道路列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15004',
            'parameter' => $parameter
        ],"post");
        // 异常返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '服务异常:'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 处理时间戳
        $this->handleBackTimestamp($list);
        // 处理坐标集合
        foreach ($list as $k => $item){
            $list[$k]['upPointLnglat'] = explode('|', $item['upPointLnglat']);
            $list[$k]['downPointLnglat'] = explode('|', $item['downPointLnglat']);
        }
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 添加路段
    public function AddRoadSectionAction()
    {
        $fields = [
            'roadId' => '请确认标记所属道路',
            'roadSectionName' => '请输入路段名称',
            'status' => '请选择路段状态',
        ];
        $request = $this->request->getJsonRawBody(true);
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 新增路段
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15003',
            'parameter' => $parameter
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess([
            'id' => $result['content']['id'],
        ]);
    }

    // 标记路段
    public function MarkRoadSectionAction($id)
    {
        $fields = [
            'upStartLnglat' => '请输入上行起始坐标',
            'upEndLnglat' => '请输入上行终点坐标',
            'upLeftWidth' => '请输入上行左边宽度',
            'upRightWidth' => '请输入上行右边宽度',
            'downStartLnglat' => '请输入下行起始坐标',
            'downEndLnglat' => '请输入下行终点坐标',
            'downLeftWidth' => '请输入下行左边宽度',
            'downRightWidth' => '请输入下行右边宽度',
            'upPointLnglat' => '请选择上行点坐标',
            'downPointLnglat' => '请选择下行点坐标',
            'upCenterpointLnglat' => 0,
            'downCenterpointLnglat' => 0
        ];
        $request = $this->request->getJsonRawBody(true);
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        $parameter['id'] = $id;
        // 处理坐标信息
        foreach ($parameter['upPointLnglat'] as $key => $value){
            $parameter['upPointLnglat'][$key] = implode(',', $value);
        }
        $parameter['upPointLnglat'] = implode('|', $parameter['upPointLnglat']);
        foreach ($parameter['downPointLnglat'] as $key => $value){
            $parameter['downPointLnglat'][$key] = implode(',', $value);
        }
        $parameter['downPointLnglat'] = implode('|', $parameter['downPointLnglat']);
        // 修改路段信息
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15003',
            'parameter' => $parameter
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    // 编辑路段
    public function EditRoadSectionAction($id)
    {
        $fields = [
            // 路段名称
            'roadSectionName' => 0,
            // 状态 0 禁用 1 启用
            'status' => 0,
        ];
        $request = $this->request->getJsonRawBody(true);
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        if (empty($parameter)){
            return $this->toError(500, '未修改任何信息');
        }
        $parameter['id'] = $id;
        // 修改路段信息
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15003',
            'parameter' => $parameter
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    // 删除路段
    public function DelRoadSectionAction($id)
    {
        // 删除路段
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15005',
            'parameter' => [
                'id' => $id,
            ]
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }
}

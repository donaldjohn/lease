<?php
namespace app\modules\traffic;

use app\models\service\PeccancyRecord;
use app\models\service\PeccancyType;
use app\models\users\Association;
use app\models\users\Institution;
use app\models\users\Postoffice;
use app\modules\BaseController;
use app\services\data\RegionData;
use app\services\data\UserData;

// 违章管理
class PeccancyController extends BaseController
{
    // 违规车辆列表
    public function ListAction()
    {
        $fields = [
            // 车牌号
            'vehicleLicence' => 0,
            // 违章时间 精度为天
            'peccancyTime' => 0,
            // 快递公司
            'expressInsId' => 0,
            'pageSize' => [
                'def' => 20,
            ],
            'pageNum' => [
                'def' => 1,
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        // 如果有违章时间，算出起止时间戳
        if (isset($parameter['peccancyTime'])){
            $parameter['createAtStart'] = strtotime($parameter['peccancyTime']);
            $parameter['createAtEnd'] = $parameter['createAtStart'] + 3600*24;
            unset($parameter['peccancyTime']);
        }
        // 如果是快递公司职能用户
        if (isset($this->authed->regionId) && $this->authed->regionId>0){
            // 查询下属站点
            $siteIds = (new RegionData())->getBelongRegionIdsByRegionId($this->authed->regionId, $this->authed->insId);
            $parameter['regionIdList'] = $siteIds;
        }elseif ($this->authed->insId > 0){
            // TODO:邮管/快递协会/快递公司
            // 查询当前机构能关联的快递公司
            $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
            if ($expressIdList && !empty($expressIdList)){
                $parameter['expressInsIdList'] = $expressIdList;
            }
            // 获取当前用户机构区域信息
            $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);
            if ($area){
                $parameter = array_merge($parameter, $area);
            }
        }
        // 查询车辆违规列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15006',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 查询开单人姓名
        $sourcePersonIds = [];
        $insIds = [];
        foreach ($list as $item){
            if ($item['sourcePerson']>0){
                $sourcePersonIds[] = $item['sourcePerson'];
            }
            if ($item['insId']>0){
                $insIds[] = $item['insId'];
            }
        }
        $sourcePersonNames = $this->userData->getUserNameByIds($sourcePersonIds);
        // 获取快递公司名称
        $expressNames = $this->userData->getCompanyNamesByInsIds($insIds);
        foreach ($list as $k => $v){
            $list[$k]['sourcePersonName'] = $sourcePersonNames[$v['sourcePerson']] ?? '';
            // 快递公司
            $list[$k]['expressName'] = $expressNames[$v['insId']] ?? '';
            // 处理一对多数据为数组
            $list[$k]['types'] = explode('|', $v['types']);
            $list[$k]['picPaths'] = explode('|', $v['picPaths']);
            $list[$k]['createTime'] =  $list[$k]['createAt'];
        }
        // 处理时间戳
        $this->handleBackTimestamp($list);
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 处理违章
    public function ProcessAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 处理方式，0：扣分、1：罚款、2扣分+罚款、3：思想教育、4：作废
            'processType' => '请选择处理类型',
            // 骑手id
            'driverId' => 0,
            // 骑手姓名
            'driverName' => 0,
            // 骑手身份证号
            'identify' => 0,
            // 扣分数
            'processScore' => 0,
            // 罚款金额
            'processAmount' => 0,
            // 过程记录
            'marks' => 0,
            // 作废原因
            'reason' => 0,
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        switch ($parameter['processType']){
            // 扣分
            case 0:
                if (!isset($parameter['processScore'])){
                    return $this->toError(500, '扣分操作请填写分数');
                }
                break;
            // 罚款
            case 1:
                if (!isset($parameter['processAmount'])){
                    return $this->toError(500, '罚款操作请填写金额');
                }
                break;
            // 扣分+罚款
            case 2:
                if (!isset($parameter['processScore']) || !isset($parameter['processAmount'])){
                    return $this->toError(500, '请同时填写分数和金额');
                }
                break;
            // 思想教育
            case 3:
                if (!isset($parameter['marks'])){
                    return $this->toError(500, '请填写过程记录');
                }
                break;
            // 作废
            case 4:
                if (!isset($parameter['reason'])){
                    return $this->toError(500, '请填写作废原因');
                }
                break;
        }
        // 处理人
        $parameter['userId'] = $this->authed->userId;
        // 违章单ID
        $parameter['peccancyId'] = $id;
        // 查询违章单是否已被处理
        $peccancyRec = PeccancyRecord::findFirst([
            'id = :id:',
            'bind' => [
                'id' => $id,
            ]
        ]);
        if (false === $peccancyRec){
            return $this->toError(500, '无效的违章单');
        }
        // 已被处理
        if (PeccancyRecord::UN_PROCESS != $peccancyRec->status){
            return $this->toError(500, '不可重复处理违章单');
        }
        // 如果没有骑手id
        if (!isset($parameter['driverId']) && isset($parameter['identify'])){
            $driver =  $this->modelsManager->createBuilder()
                ->addfrom('app\models\dispatch\DriversIdentification','di')
                ->where('di.IdentificationNumber = :identify:', [
                    'identify' => $parameter['identify']
                ])
                ->join('app\models\dispatch\Drivers', 'di.driver_id = d.id','d')
                ->columns('d.id')
                ->getQuery()
                ->getSingleResult();
            $parameter['driverId'] = $driver->id ?? 0;
        }
        // 增加违章处理
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15008',
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

    // 违章处理详情
    public function ProcessinfoAction($id)
    {
        // 违章处理详情
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "15010",
            'parameter' => [
                'peccancyId' => $id
            ],
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败'.$result['msg']);
        }
        $data = $result['content']['data'];
        $data['processUserName'] = $this->userData->getUserNameByIds($data['userId'])[$data['userId']] ?? '';
        return $this->toSuccess($data);
    }

    // 获取违章单的轨迹
    public function LocusAction($id)
    {
        // 查询违章单类型，系统判定只会有一个
        $peccancyType = PeccancyType::arrFindFirst([
            'peccancy_id' => $id
        ]);
        if (false===$peccancyType){
            return $this->toError(500, '违章单异常');
        }
        $type = $peccancyType->type;
        // 轨迹记录
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15009',
            'parameter' => [
                'peccancyId' => $id,
                'type' => $type
            ],
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败'.$result['msg']);
        }
        $data = $result['content']['data'];
        $data['type'] = $type;
        return $this->toSuccess($data);
    }

    // 处理违章单【新】
    public function ProcessPeccancyAction($id)
    {
        $parameter = $this->request->getJsonRawBody(true);
        $parameter['id'] = $id;
        $parameter['userId'] = $this->authed->userId;
        // 调用服务
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15021',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500, '操作失败:' . ($result['content']['data']['cause'] ?? $result['msg']));
        }
        $data = $result['content']['data'] ?? [];
        // 成功返回
        return $this->toSuccess($data);
    }
}

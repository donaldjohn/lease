<?php
namespace app\modules\postoffice;

use app\models\users\User;
use app\modules\BaseController;
use app\services\data\UserData;

class VehicleinspectionController extends BaseController
{
    // 年检列表
    public function ListAction()
    {
        $fields = [
            // 快递公司insId
            'insId' => 0,
            // 车牌号
            'plateNum' => 0,
            // 年检状态  0  待上传 1 待审核 2  审核通过 3 审核不通过
            'status' => 0,
            // 年检超期，1：未超期（当前时间在年检期限内），2：已超期
            'expired' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        // 仅邮管局或快递协会可查看
        if (!in_array($this->authed->userType, [2,3,7])){
            return $this->toError(500,'当前用户必须是邮管局/快递协会/快递公司');
        }
        // TODO:邮管/快递协会/快递公司
        // 查询当前机构能关联的快递公司
        $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType, true);
        if (false!==$expressIdList){
            if (empty($expressIdList)){
                return $this->toEmptyList();
            }
            $parameter['insIdList'] = $expressIdList;
        }
        // 年检列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11006,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];

        // 审核人ids
        $auditUserIds = [];
        // 快递公司机构ids
        $insIds = [];
        foreach ($list as $item){
            if ($item['auditUserId']>0){
                $auditUserIds[] = $item['auditUserId'];
            }
            if ($item['insId']>0){
                $insIds[] = $item['insId'];
            }
        }
        // 获取用户名
        $auditUserNames = $this->userData->getUserNameByIds($auditUserIds);
        // 获取快递公司名称
        $expressNames = $this->userData->getCompanyNamesByInsIds($insIds);
        foreach ($list as $k => $v){
            // effectiveEndTime 是否大于当前时间 gt
            $list[$k]['effectiveEndTimeGTNow'] = ($v['effectiveEndTime']>time());
            $list[$k]['auditUserName'] = $auditUserNames[$v['auditUserId']] ?? '';
            // 快递公司
            $list[$k]['expressName'] = $expressNames[$v['insId']] ?? '';
        }
        // 处理时间戳
        $this->handleBackTimestamp($list, ['effectiveStartTime', 'effectiveEndTime', 'auditTime']);
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 年检审核
    public function AuditAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 状态  0  待上传 1 待审核 2  审核通过 3 审核不通过
            'status' => [
                'need' => '请选择审核状态',
                'in' => [2,3]
            ],
            'reason' => 0
        ];
        $parameter = $this->getArrPars($fields, $request);
        if (3==$parameter['status'] && !isset($parameter['reason'])){
            return $this->toError(500, '审核拒绝需填写原因');
        }
        if (!isset($request['idWithRow']) || empty($request['idWithRow'])){
            return $this->toError(500, '未收到要处理的年检单');
        }
        $parameter['idWithRow'] = $request['idWithRow'];
        $parameter['auditUserId'] = $this->authed->userId;
        // 更新年检
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11005,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'] ?? [];
        return $this->toSuccess($data);
    }

    // 年检延长
    public function ActiveAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['idWithRow']) || empty($request['idWithRow'])){
            return $this->toError(500, '未收到要处理的年检单');
        }
        $parameter['idWithRow'] = $request['idWithRow'];
        $parameter['active'] = 1;
        // $parameter['auditUserId'] = $this->authed->userId;
        // 更新年检
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11005,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'] ?? [];
        return $this->toSuccess($data);
    }

    // 年检审核前详情
    public function InfoAction($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11007,
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $currentAudit = $result['content']['currentAudit'] ?? [];
        $vehicle = $result['content']['vehicle'] ?? [];
        $data = array_merge($currentAudit, $vehicle);
        $imgUrls = !empty($data['imgUrl']) ? explode('|', $data['imgUrl']) : [];
        foreach ($imgUrls as $imgK =>  $imgUrl){
            $imgUrl = explode('^', $imgUrl);
            $imgUrls[$imgK] = [
                'name' => $imgUrl[0] ?? '',
                'url' => $imgUrl[1] ?? '',
            ];
        }
        $data['imgUrl'] = $imgUrls;
        // 处理时间戳
        $this->handleBackTimestamp($data, ['effectiveStartTime', 'effectiveEndTime', 'auditTime']);
        // 获取用户名
        $auditUserNames = $data['auditUserId']>0 ? $this->userData->getUserNameByIds($data['auditUserId']) : [];
        // 获取快递公司名称
        $expressNames = $data['insId']>0 ? $this->userData->getCompanyNamesByInsIds($data['insId']) : [];
        $data['auditUserName'] = $auditUserNames[$data['auditUserId']] ?? '';
        // 快递公司
        $data['expressName'] = $expressNames[$data['insId']] ?? '';
        return $this->toSuccess($data);
    }

    // 车辆年检历史列表
    public function RecordAction($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11007,
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $vehicle = $result['content']['vehicle'];
        // 车辆当前年检状态 0  待上传 1 待审核 2  审核通过 3 审核不通过
        $vehicle['yearCheckStatus'] = $result['content']['currentAudit']['status'];
        $list[] = $result['content']['currentAudit'];
        $list = array_merge($list, $result['content']['failedAudit']??[]);
        // 审核人ids
        $auditUserIds = [];
        foreach ($list as $key => $item){
            // 过滤未审核的记录
            if (in_array($item['status'], [0,1])){
                unset($list[$key]);
                continue;
            }
            if ($item['auditUserId']>0){
                $auditUserIds[] = $item['auditUserId'];
            }
        }
        // 获取用户名
        $auditUserNames = $this->userData->getUserNameByIds($auditUserIds);
        // 快递公司
        $vehicle['expressName'] = $vehicle['insId']>0 ? $this->userData->getExpressNamesByInsId($vehicle['insId']) : '';
        foreach ($list as $key => $item){
            // 审核人
            $item['auditUserName'] = $auditUserNames[$item['auditUserId']] ?? '';
            $imgUrls = !empty($item['imgUrl']) ? explode('|', $item['imgUrl']) : [];
            foreach ($imgUrls as $imgK =>  $imgUrl){
                $imgUrl = explode('^', $imgUrl);
                $imgUrls[$imgK] = [
                    'name' => $imgUrl[0] ?? '',
                    'url' => $imgUrl[1] ?? '',
                ];
            }
            $item['imgUrl'] = $imgUrls;
            $list[$key] = $item;
        }
        // 处理时间戳
        $this->handleBackTimestamp($list, ['effectiveStartTime', 'effectiveEndTime', 'auditTime']);
        $data = [
            'list' => $list,
            'vehicle' => $vehicle
        ];
        return $this->toSuccess($data);
    }

    // 新增年检项目
    public function AddItemAction()
    {
        if (3 != $this->authed->userType){
            return $this->toError(500, '非快递协会，不可进行此操作');
        }
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'itemName' => '请填写项目名称',
            'schemaDesc' => '请填写描述',
            'samplePic' => '请上传项目示例图片'
        ];
        $parameter = $this->getArrPars($fields, $request);
        // 操作用户id
        $parameter['submitUserId'] = $this->authed->userId;
        // 机构id
        $parameter['insId'] = $this->authed->insId;
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11008,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            $tips = [
                '1015' => '项目名称已存在',
                '1020' => '当前用户不是市级快递协会无权限操作',
            ];
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        $data = [
            'id' => $result['content']['id'] ?? null,
        ];
        // 成功返回
        return $this->toSuccess($data);
    }

    // 编辑年检项目
    public function EditItemAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'itemName' => 0,
            'schemaDesc' => 0,
            'samplePic' => 0
        ];
        $parameter = $this->getArrPars($fields, $request, true);
        // 操作用户id
        $parameter['submitUserId'] = $this->authed->userId;
        // 机构id
        $parameter['insId'] = $this->authed->insId;
        $parameter['id'] = $id;
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11009,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            $tips = [
                '1015' => '项目名称已存在',
                '1020' => '当前用户不是市级快递协会无权限操作',
            ];
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        // 成功返回
        return $this->toSuccess();
    }
    // 年检项目列表
    public function ItemListAction()
    {
        $fields = [
            // 年检项名称
            'itemName' => 0,
            // 年检项状态  0:禁用、1：启用
            'status' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $parameter['insId'] = $this->authed->insId;
        $parameter['userId'] = $this->authed->userId;
        // 年检列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11010,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            $tips = [
                '1018' => '当前用户必须是邮管局或快递协会',
                '1019' => '当前用户对应的邮管局或快递协会不存在'
            ];
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        $list = $result['content']['list'];
        // 处理时间戳
        $this->handleBackTimestamp($list);
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 启禁用年检项目
    public function ItemStatusAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 年检项状态 0:禁用、1：启用
            'status' => [
                'need' => '未收到变更状态',
                'in' => [0,1]
            ],
        ];
        $parameter = $this->getArrPars($fields, $request);
        $parameter['id'] = $id;
        // 机构id
        $parameter['insId'] = $this->authed->insId;
        // 启禁用年检项目
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11011,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            $tips = [
                '1020' => '当前用户不是市级快递协会无权限操作',
            ];
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        return $this->toSuccess();
    }

    // 删除年检项目
    public function DelItemAction($id)
    {
        // 删除年检项目
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11012,
            'parameter' => [
                'id' => $id,
                'insId' => $this->authed->insId,
            ]
        ],"post");
        if (200 != $result['statusCode']){
            $tips = [
                '1016' => '不是禁用，无法删除',
                '1017' => '当前年检项正在被年检任务使用，无法删除',
                '1020' => '当前用户不是市级快递协会无权限操作',
            ];
            $msg = $tips[$result['statusCode']] ?? $result['msg'];
            return $this->toError($result['statusCode'], $msg);
        }
        return $this->toSuccess();
    }

    // 年检项目详情
    public function ItemInfoAction($id)
    {
        // 年检项目详情
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11013,
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['YearlyCheckItem'];
        $this->handleBackTimestamp($data);
        return $this->toSuccess($data);
    }

    // 年检任务批量打印
    public function BatchPrintAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['printList']) || empty($request['printList'])){
            return $this->toError(500, '打印列表不可为空');
        }
        // 调用服务
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11022,
            'parameter' => [
                'printList' => $request['printList']
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['result'];
        return $this->toSuccess($data);
    }

    // 年检任务单个打印标志
    public function PrintAction($id)
    {
        // 调用服务
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11023,
            'parameter' => [
                'id' => $id
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content'];
        return $this->toSuccess($data);
    }

}

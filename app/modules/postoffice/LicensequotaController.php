<?php
namespace app\modules\postoffice;

use app\models\users\User;
use app\modules\BaseController;
use app\services\data\UserData;

class LicensequotaController extends BaseController
{
    // 申请配额
    public function AddAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'applyCount' => [
                'need' => '请填写申请配额数量',
                'min' => 1
            ]
        ];
        $parameter = $this->getArrPars($fields, $request);
        $parameter['insId'] = $this->authed->insId;
        $parameter['applyUserId'] = $this->authed->userId;
        // 查询用户名
        $user = User::arrFindFirst([
            'id' => $this->authed->userId
        ]);
        if (false===$user){
            return $this->toError(500, '用户异常');
        }
        $parameter['loginUser'] = $user->getUserName();
        // 新增配额申请
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11000,
            'parameter' => $parameter
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        return $this->toSuccess([
            'id' => $result['content']['id'] ?? null
        ]);
    }

    // 处理配额申请
    public function ProcessAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        if (isset($request['submit'])){
            $request['status'] = 1;
        }
        // 状态，0：未提交、1：待审核、2：审核通过、3：审核拒绝
        if (!isset($request['status']) || !in_array($request['status'], [1,2,3])){
            return $this->toError(500,'错误的操作');
        }
        $parameter['id'] = $id;
        $parameter['status'] = $request['status'];
        $parameter['remark'] = $request['remark'] ?? '';
        // 审核
        if (in_array($request['status'], [2,3])){
            if (3 != $this->authed->userType || 2 != $this->authed->userType){
                return $this->toError(500,'非快递协会人员或邮管局人员，不可审核');
            }
            $parameter['auditUserId'] = $this->authed->userId;
            $parameter['auditTime'] = time();
        }
        if (2 == $request['status']){
            if (!isset($request['actualCount']) || !($request['actualCount']>0)){
                return $this->toError(500,'分配数量有误');
            }
            $parameter['actualCount'] = $request['actualCount'];
        }
        // 处理配额申请
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11000,
            'parameter' => $parameter,
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'操作失败');
        }
        return $this->toSuccess();
    }

    // 配额申请单列表
    public function ListAction()
    {
        $fields = [
            // 申请编号
            'applyNum' => 0,
            // 状态，0：未提交、1：待审核、2：审核通过、3：审核拒绝
            'status' => 0,
            // 快递公司id
            'insId' => 0,
            'pageSize' => [
                'def' => 20,
            ],
            'pageNum' => [
                'def' => 1,
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        // TODO:邮管/快递协会/快递公司
        // 查询当前机构能关联的主系统快递公司
        $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false !== $expressIdList && empty($expressIdList)){
            return $this->toEmptyList();
        }
        if ($expressIdList && !empty($expressIdList)){
            if (1==count($expressIdList)){
                $parameter['insId'] = $expressIdList[0];
            }else{
                $parameter['insIds'] = $expressIdList;
            }
        }
        // 不是快递公司，过滤未提交数据
        if (7 != $this->authed->userType){
            $parameter['submitted'] = 1;
        }
        // 获取列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11001,
            'parameter' => $parameter,
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'];
        // 申请/审核人
        $userIds = [];
        // 快递公司
        $insIds = [];
        foreach ($list as $k => $item){
            $userIds[] = $item['applyUserId'];
            $userIds[] = $item['auditUserId'];
            $insIds[] = $item['insId'];
        }
        $userNames = (new UserData())->getUserNameByIds($userIds);
        $insNames = (new UserData())->getCompanyNamesByInsIds($insIds);
        foreach ($list as $k => $item){
            $list[$k]['auditUserName'] = $userNames[$item['auditUserId']] ?? '';
            $list[$k]['applyUserName'] = $userNames[$item['applyUserId']] ?? '';
            $list[$k]['insName'] = $insNames[$item['insId']] ?? '';
            $list[$k]['applyTime'] = 0==$item['applyTime'] ? '' : date('Y-m-d H:i:s', $item['applyTime']);
            $list[$k]['auditTime'] = 0==$item['auditTime'] ? '' : date('Y-m-d H:i:s', $item['auditTime']);
        }
        return $this->toSuccess($list, $meta);
    }

    // 快递公司配额使用记录列表
    public function UsedQuotaListAction()
    {
        if (7==$this->authed->userType){
            $parameter['insId'] = $this->authed->insId;
        }else{
            $fields = [
                'insId' => '请选择快递公司'
            ];
            // 过滤参数
            $parameter = $this->getArrPars($fields, $_GET);
            /* 暂不过滤关系
            // 查询机构关联快递公司
            $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
            if (false===$expressIdList){
                //
            }elseif (empty($expressIdList)){
                return $this->toEmptyList();
            }else{
                $parameter['insIds'] = $expressIdList;
            }*/
        }
        $parameter['pageSize'] = $_GET['pageSize'] ?? 20;
        $parameter['pageNum'] = $_GET['pageNum'] ?? 1;
        // 获取列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11003,
            'parameter' => $parameter,
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'] ?? [
                'total' => 0,
                'pageSize' => 0,
                'pageNum' => 1,
            ];
        // 处理时间戳
        $this->handleBackTimestamp($list);
        return $this->toSuccess($list, $meta);
    }

    // 快递公司配额使用情况列表
    public function ExpressQuotaListAction()
    {
        $fields = [
            // 快递公司id
            'insId' => 0,
            'pageSize' => [
                'def' => 20,
            ],
            'pageNum' => [
                'def' => 1,
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        // TODO:邮管/快递协会/快递公司
        // 查询当前机构能关联的主系统快递公司
        $expressIdList = $this->userData->getExpressIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false !== $expressIdList && empty($expressIdList)){
            return $this->toEmptyList();
        }
        if ($expressIdList && !empty($expressIdList)){
            $parameter['insIds'] = $expressIdList;
        }
        // 获取列表
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11002,
            'parameter' => $parameter,
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $list = $result['content']['data'];
        // 分页数据
        $meta = $result['content']['pageInfo'] ?? [
                'total' => 0,
                'pageSize' => 0,
                'pageNum' => 1,
            ];
        // 快递公司
        $insIds = [];
        foreach ($list as $item){
            $insIds[] = $item['insId'];
        }
        $insNames = (new UserData())->getCompanyNamesByInsIds($insIds);
        foreach ($list as $k => $item){
            $list[$k]['insName'] = $insNames[$item['insId']] ?? '';
        }
        // 处理时间戳
        $this->handleBackTimestamp($list);
        return $this->toSuccess($list, $meta);
    }


    // 快递公司自己配额信息
    public function ExpressQuotaInfoAction()
    {
        // 查询配额信息
        $result = $this->curl->httpRequest($this->Zuul->vehicle,[
            'code' => 11002,
            'parameter' => [
                'insId' => $this->authed->insId,
                'pageSize' => 1
            ],
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError(500,'系统异常：'.$result['msg']);
        }
        $data = $result['content']['data'][0] ?? [
                'quoatCount' => 0,
                'usedCount' => 0,
                'unusedCount' => 0
            ];
        return $this->toSuccess($data);
    }

    // 【透传】编辑配额-11071
    public function EditAction()
    {
        return $this->PenetrateTransferToService('vehicle', 11071);
    }

    // 【透传】删除配额-11072
    public function DelAction()
    {
        return $this->PenetrateTransferToService('vehicle', 11072);
    }
}

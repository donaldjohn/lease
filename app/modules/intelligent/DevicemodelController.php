<?php
namespace app\modules\intelligent;

use app\models\service\DeviceModel;
use app\models\service\Vehicle;
use app\modules\BaseController;

class DevicemodelController extends BaseController
{
    // 设备型号列表
    public function ListAction()
    {
        $fields = [
            'modelText' => 0,
            'ins_id' => 0,
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
        ];
        $param = $this->getArrPars($fields, $_GET);
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\DeviceModel','dm')
            ->leftJoin('app\models\users\Company', 'c.ins_id = dm.ins_id','c')
            ->andWhere('dm.is_delete = 0');
        if (isset($param['ins_id'])){
            $model = $model->andWhere('dm.ins_id = :ins_id:', ['ins_id'=>$param['ins_id']]);
        }
        if (isset($param['modelText'])){
            $model = $model->andWhere(
                'dm.model_code like :modelText: OR dm.model_name like :modelText:',
                ['modelText' => '%'.$param['modelText'].'%']
            );
        }
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(*) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        $list = $model->columns('dm.id, dm.model_code, dm.model_name, dm.ins_id,dm.is_support_lock, dm.qrcode_rule, dm.support_set_frequency, dm.has_frequency_set, dm.create_at, c.company_name')
            ->orderBy('dm.create_at DESC')
            ->limit($param['pageSize'], ($param['pageNum']-1)*$param['pageSize'])
            ->getQuery()
            ->execute()
            ->toArray();
        //结果处理返回
        $meta = [
            'pageNum'=> $param['pageNum'],
            'total' => $count,
            'pageSize' => $param['pageSize']
        ];
        return $this->toSuccess($list, $meta);
    }

    // 新增设备型号
    public function AddAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 型号代码
            'model_code' => [
                'need' => '请输入型号代码',
                'maxl' => 20,
            ],
            // 型号名称
            'model_name' => [
                'need' => '请输入型号名称',
                'maxl' => 20,
            ],
            // 请选择供应商
            'ins_id' => [
                'need' => '请选择供应商',
            ],
            // 采集频率自定义
            'support_set_frequency' => [
                'need' => '请选择是否支持自定义采集频率',
            ],
            // 二维码规则正则
            'qrcode_rule' => '二维码规则缺失',
            'is_support_lock' => [
                'need' => '是否支持远程锁车',
             ],
        ];
        $param = $this->getArrPars($fields, $request);
        // 查询正则规则是否存在
        if ($this->hasQrcodeRule($param['qrcode_rule'])){
            return $this->toError(500, '匹配规则已存在');
        }
        // 查询型号代码是否已存在
        if ($this->hasModelCode($param['model_code'])){
            return $this->toError(500, '型号代码已存在');
        }
        // 查询型号名称是否在同一机构下已存在
        if ($this->hasModelNameInIns($param['model_name'], $param['ins_id'])){
            return $this->toError(500, '型号名称已存在');
        }
        if (!in_array($param['is_support_lock'],[0,1])) {
            return $this->toError(500, '请选择支持还是不支持远程锁车');
        }
        $data = [
            'model_code' => $param['model_code'],
            'model_name' => $param['model_name'],
            'ins_id' => $param['ins_id'],
            'qrcode_rule' => $param['qrcode_rule'],
            'create_at' => time(),
            'update_at' => time(),
            'is_support_lock' => $param['is_support_lock'],
            'support_set_frequency' => $param['support_set_frequency'],
        ];
        // 处理二维码匹配前缀
        $data['match_prefix'] = $this->regexToPrefix($param['qrcode_rule']);
        // 数据入库
        $res = (new DeviceModel())->create($data);
        if (false===$res){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }

    // 编辑设备型号
    public function EditAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            // 型号代码
            'model_code' => [
                'need' => '请输入型号代码',
                'maxl' => 20,
            ],
            // 型号名称
            'model_name' => [
                'need' => '请输入型号名称',
                'maxl' => 20,
            ],
            // 请选择供应商
            'ins_id' => [
                'need' => '请选择供应商',
            ],
            // 采集频率自定义
            'support_set_frequency' => [
                'need' => '请选择是否支持自定义采集频率',
            ],
            // 二维码规则正则
            'qrcode_rule' => '二维码规则缺失',
            'is_support_lock' => [
                'need' => '是否支持远程锁车',
            ],
        ];
        $param = $this->getArrPars($fields, $request);
        // 处理二维码匹配前缀
        $param['match_prefix'] = $this->regexToPrefix($param['qrcode_rule']);
        // 查询设备型号信息
        $deviceModel = DeviceModel::arrFindFirst(['id' => $id]);
        if (false===$deviceModel){
            return $this->toError(500, '设备型号不存在');
        }
        // 查询正则规则是否存在
        if ($this->hasQrcodeRule($param['qrcode_rule'], $id)){
            return $this->toError(500, '匹配规则已存在');
        }
        // 查询型号代码是否已存在
        if ($this->hasModelCode($param['model_code'], $id)){
            return $this->toError(500, '型号代码已存在');
        }
        // 查询型号名称是否在同一机构下已存在
        if ($this->hasModelNameInIns($param['model_name'], $param['ins_id'], $id)){
            return $this->toError(500, '型号名称已存在');
        }
        if (!in_array($param['is_support_lock'],[0,1])) {
            return $this->toError(500, '请选择支持还是不支持远程锁车');
        }
        // 补充时间
        $param['update_at'] = time();
        // 是否需要清理关系
        $needClear = false;
        if (1==$deviceModel->support_set_frequency && 0==$param['support_set_frequency']){
            $param['has_frequency_set'] = 0;
            $needClear = true;
        }
        // 数据入库
        $bol = $deviceModel->update($param);
        if (false===$bol){
            return $this->toError(500, '操作失败');
        }
        // 清理关系
        if ($needClear){
            // 32794-删除频率配置
            $res = $this->PenetrateTransferToService('charging', 32794,
                ['deviceModelId'=>$id], true);
        }
        return $this->toSuccess();
    }

    // 删除设备型号
    public function DelAction($id)
    {
        // 查询设备型号信息
        $deviceModel = DeviceModel::arrFindFirst(['id' => $id]);
        if (false===$deviceModel){
            return $this->toError(500, '设备型号不存在');
        }
        // 查询设备是否被使用
        $vehicle = Vehicle::arrFindFirst(['device_model_id'=>$id]);
        if ($vehicle){
            return $this->toError(500, '设备已被使用，不可删除');
        }
        // 是否需要清理关系
        $needClear = false;
        if (1==$deviceModel->has_frequency_set){
            $data['has_frequency_set'] = 0;
            $needClear = true;
        }
        // 补充时间
        $data['update_at'] = time();
        $data['is_delete'] = 1;
        // 数据入库
        $bol = $deviceModel->update($data);
        if (false===$bol){
            return $this->toError(500, '操作失败');
        }
        if ($needClear){
            // 32794-删除频率配置
            $res = $this->PenetrateTransferToService('charging', 32794,
                ['deviceModelId'=>$id], true);
        }
        return $this->toSuccess();
    }

    // 32795 查询频率配置【透传】
    public function FindFrequencySetAction(){
        return $this->PenetrateTransferToService('charging', 32795);
    }

    // 32792 新增频率配置【透传】
    public function AddFrequencySetAction(){
        return $this->PenetrateTransferToService('charging', 32792);
    }

    // 32793 更新频率配置【透传】
    public function UpdateFrequencySetAction(){
        return $this->PenetrateTransferToService('charging', 32793);
    }

    // 查询型号代码是否已存在
    private function hasModelCode($modelCode, $id=null){
        $where = [
            'model_code' => $modelCode,
            'is_delete' => 0,
        ];
        if ($id > 0){
            $where['id'] = ['!=', $id];
        }
        $deviceModel = DeviceModel::arrFindFirst($where);
        return false===$deviceModel ? false : true;
    }

    // 查询型号名称是否在同一机构下已存在
    private function hasModelNameInIns($modelName, $insId, $id=null){
        $where = [
            'model_name' => $modelName,
            'ins_id' => $insId,
            'is_delete' => 0,
        ];
        if ($id > 0){
            $where['id'] = ['!=', $id];
        }
        $deviceModel = DeviceModel::arrFindFirst($where);
        return false===$deviceModel ? false : true;
    }

    // 查询正则是否已存在
    private function hasQrcodeRule($QrcodeRule, $id=null){
        $where = [
            'qrcode_rule' => $QrcodeRule,
            'is_delete' => 0,
        ];
        if ($id > 0){
            $where['id'] = ['!=', $id];
        }
        $deviceModel = DeviceModel::arrFindFirst($where);
        return false===$deviceModel ? false : true;
    }

    // 正则转前缀
    private function regexToPrefix($regex){
        return stripslashes(ltrim(strchr($regex, '(\S{', true), '/^'));
    }
}
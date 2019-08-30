<?php
namespace app\modules\intelligent;

use app\modules\BaseController;

class SelectController extends BaseController
{
    // 下拉选择供应商
    public function SupplierAction()
    {
        // 获取当前机构关联的供应商
        $SupplierIdList = $this->userData->getSupplierIdsByInsId($this->authed->insId, $this->authed->userType);
        if (false!==$SupplierIdList && empty($SupplierIdList)){
            return $this->toEmptyList();
        }
        $model =  $this->modelsManager->createBuilder()
            // 查询供应商
            ->addfrom('app\models\users\Institution','i')
            ->where('i.type_id = :type:', [
                'type' => 5,
            ]);
        if (is_array($SupplierIdList)){
            $model = $model->andWhere('i.id IN ({SupplierIdList:array})', [
                'SupplierIdList' => $SupplierIdList,
            ]);
        }
        // 关联公司信息 && 管理者为启用状态
        $model = $model->join('app\models\users\UserInstitution', 'ui.ins_id = i.id AND ui.is_admin = 1','ui')
            ->join('app\models\users\User', 'u.id = ui.user_id AND u.user_status = 1','u')
            ->join('app\models\users\Company', 'c.ins_id = i.id','c');
        // 公司名称搜索
        if (!empty($_GET['companyName'])){
            $model = $model->andWhere('c.company_name like :companyName:', ['companyName'=>'%'.$_GET['companyName'].'%']);
        }
        // 公司类型搜索
        if (!empty($_GET['companyType'])){
            $model = $model->andWhere('c.company_type = :companyType:', ['companyType'=>$_GET['companyType']]);
        }
        $Supplier = $model->columns('i.id AS insId, c.company_name AS companyName')
            ->getQuery()
            ->execute()
            ->toArray();
        return $this->toSuccess($Supplier);
    }

    // 下拉选择站点
    public function SiteAction()
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 10001,
            'parameter' => $_GET
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $data = $result['content']['regions'] ?? [];
        $meta = $result['content']['pageInfo'] ?? [];
        return $this->toSuccess($data, $meta);
    }

    // 下拉选择智能设备型号
    public function DeviceModelAction()
    {
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\DeviceModel','dm')
            // ->leftJoin('app\models\users\Company', 'c.ins_id = dm.ins_id','c')
            ->andWhere('dm.is_delete = 0');
        if (isset($_GET['model_name'])){
            $model = $model->andWhere(
                'dm.model_name like :model_name:',
                ['model_name' => '%'.$_GET['model_name'].'%']
            );
        }
        $list = $model->columns('dm.id, dm.model_code, dm.model_name')
            ->orderBy('dm.id DESC')
            ->getQuery()
            ->execute()
            ->toArray();
        return $this->toSuccess($list);
    }
}
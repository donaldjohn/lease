<?php
namespace app\services\data;


class PostOfficeData extends BaseData
{
    // 实名实人认证
    const RealAuthentication = 'RealAuthentication';
    // 移动端考试
    const MobileExam = 'MobileExam';
    // 绑车校验驾照分
    const BindVehicleNeedDriversLicense = 'BindVehicleNeedDriversLicense';

    const expressPrintDriversLicense = 'expressPrintDriversLicense';


    // 获取城市邮管系统参数开关
    public function getPostOfficeSystemParam($cityId, $paramName)
    {
        $cityId = (int) $cityId;
        $systemParamValue =  $this->modelsManager->createBuilder()
            // 查询参数id
            ->addfrom('app\models\service\SystemParam','sp')
            // 关联城市参数值
            ->leftJoin('app\models\service\SystemParamValue',
                "spv.system_param_id = sp.id AND spv.city_id = {$cityId}",
                'spv')
            ->where('sp.name = :name:', ['name'=>$paramName])
            ->columns('sp.value AS defValue, spv.value')
            ->getQuery()
            ->getSingleResult();
        $value = $systemParamValue->value ?? $systemParamValue->defValue ?? 0;
        return $value ? true : false;
    }

    /***
     * 获取快递公司对应的邮管城市
     * @param $insId 快递公司机构id
     * @return null
     */
    public function getPostOfficeCityIdByExpressInsId($insId)
    {
        // 查询关联快递协会
        $association =  $this->modelsManager->createBuilder()
            // 查询快递公司
            ->addfrom('app\models\users\Institution','i')
            ->where('i.id = :insId:', [
                'insId' => $insId,
            ])
            // 查询快递协会信息
            ->join('app\models\users\Association', 'a.ins_id = i.parent_id','a')
            ->columns('a.*')
            ->getQuery()
            ->getSingleResult();
        if (!$association){
            return null;
        }
        $cityId = $association->city_id;
        return $cityId;
    }
}
<?php
namespace app\modules\rent;

use app\models\service\RentArea;
use app\modules\BaseController;

/**
 * Class RentareaController
 * @package app\modules\rent
 *
 * 迁移至order
 *
 */
// 租赁业务区域管理【目前只有市级】
class RentareaController extends BaseController
{
    /**
     * 租赁业务区域列表
     */
    public function ListAction()
    {
        // 查询租赁业务区域
        $rentAreas =  $this->modelsManager->createBuilder()
            ->columns('a.area_id  AS areaId, a.area_name AS areaName, a.area_parent_id AS areaParentId, a.area_deep AS areaDeep, ap.area_name AS areaParentName')
            ->addfrom('app\models\service\RentArea','ra')
            ->Join('app\models\service\Area', 'a.area_id=ra.area_id AND a.area_deep=2','a')
            ->leftJoin('app\models\service\Area', 'ap.area_id=a.area_parent_id','ap')
            ->getQuery()
            ->execute()
            ->toArray();
        return $this->toSuccess($rentAreas);
    }

    /**
     * 编辑业务区域
     */
    public function EditAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['areaIdList'])){
            return $this->toError(500, '参数错误');
        }
        // 开启事务
        $this->dw_service->begin();
        // 清空当前业务区域
        $RAs = RentArea::find();
        foreach ($RAs as $RA){
            $bol = $RA->delete();
            if (false===$bol){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 增加业务区域
        foreach ($request['areaIdList'] as $areaId){
            $RA = (new RentArea())->create([
                'area_id'=>$areaId
            ]);
            if (false===$RA){
                // 事务回滚
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 提交事务
        $this->dw_service->commit();
        return $this->toSuccess();
    }


}

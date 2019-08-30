<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/19
 * Time: 14:44
 */

namespace app\modules\rent;


use app\models\order\VehicleRentOrder;
use app\models\service\Qrcode;
use app\models\service\StoreVehicle;
use app\models\service\Vehicle;
use app\modules\BaseController;

class VehicleController extends BaseController
{
    // 租赁车辆列表
    public function ListAction()
    {
        $parameter = $_GET;
        switch ($this->authed->userType){
            case 9:
                $parameter['operatorInsId'] = $this->authed->insId;
                break;
            case 8:
                $parameter['storeInsId'] = $this->authed->insId;
                break;
        }
        $result = $this->CallService('vehicle', 12000, $parameter, true);
        return $this->toSuccess($result['content']['data'], $result['content']['pageInfo']);
    }

    // 导出租赁车辆列表
    public function ExportAction()
    {
        $parameter = $_GET;
        switch ($this->authed->userType){
            case 9:
                $parameter['operatorInsId'] = $this->authed->insId;
                break;
            case 8:
                $parameter['storeInsId'] = $this->authed->insId;
                break;
        }
        $result = $this->CallService('vehicle', 60018, $parameter, true);
        return $this->toSuccess($result['content']['data']);
    }

    // 租赁车辆详情
    public function InfoAction($vehicleId)
    {
        $parameter['vehicleId'] = $vehicleId;
        $result = $this->CallService('vehicle', 12001, $parameter, true);
        $vehicle = $result['content']['data'];
        // 查询租赁单数量
        $VRONum = VehicleRentOrder::count($this->arrToQuery([
            'vehicle_id' => $vehicleId,
            'pay_status' => 2,
        ]));
        $vehicle['rentNum'] = $VRONum;
        // 处理身份证号
        $vehicle['identify'] = $this->hideIDnumber($vehicle['identify']);
        return $this->toSuccess($vehicle);
    }


    // 解除门店
    public function UnBindStoreAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $vehicleId = $request['vehicleId'] ?? null;
        if (empty($vehicleId)){
            return $this->toError(500, '参数错误');
        }
        // 查询车辆门店关系
        $SV = StoreVehicle::arrFindFirst([
            'vehicle_id' => $vehicleId,
        ]);
        if (false == $SV){
            return $this->toError(500, '车辆未绑定门店，无需解绑');
        }
        if (StoreVehicle::UN_RENT != $SV->rent_status){
            return $this->toError(500, "车辆非未出租状态，不可解绑");
        }
        // 删除关系
        $bol = $SV->delete();
        if (false == $bol){
            return $this->toError(500, '操作失败');
        }
        return $this->toSuccess();
    }


    // 租赁编辑车辆
    public function EditVehicleAction($vehicleId)
    {
        $oldQrcode = null;
        if (!($vehicleId>0)){
            return $this->toError(500, '参数异常');
        }
        $fields = [
            'bianhao' => '车辆编号不能为空',
            'udid' => '设备编码不能为空',
            'plate_num' => [
                'need' => '车牌号不能为空',
                'as' => 'plateNum',
            ],
            'vehicle_model_id' => [
                'as' => 'vehicleModelId',
            ],
        ];
        // 获取请求参数
        $request = $this->request->getJsonRawBody(true);
        $param = $this->getArrPars($fields, $request);
        // 查询车辆信息
        $vehicle = Vehicle::arrFindFirst([
            'id' => $vehicleId,
        ]);
        if (false==$vehicle){
            return $this->toError(500, '未查询到车辆信息');
        }
        // 查询得威编号是否处于已发放的状态
        if ($param['bianhao'] != $vehicle->bianhao){
            $checkBianhao = Qrcode::arrFindFirst([
                'bianhao' => $param['bianhao'],
                'status' => ['IN', [2,3]],
            ]);
            if (false==$checkBianhao){
                return $this->toError(500, '该二维码无效');
            }
            $oldQrcode = $vehicle->bianhao;
        }
        // 验证是否有重复数据
        $checkFields = [
            'bianhao' => '车辆编号',
            'udid' => '设备编码',
            'plate_num' => '车牌号',
        ];
        foreach ($checkFields as $k => $name){
            $has = Vehicle::arrFindFirst([
                $k => $param[$k],
                'id' => ['!=', $vehicleId]
            ]);
            if ($has){
                return $this->toError(500, $name.'已存在，请重新输入。');
            }
        }
        $this->dw_service->begin();
        // 更新车辆数据
        try {
            $bol = $vehicle->update($param);
            if (false==$bol){
                $this->dw_service->rollback();
                return $this->toError(500, '系统繁忙');
            }
            if ($oldQrcode != null) {
                $sql1 = "update dw_qrcode set status =2 where bianhao = ".$oldQrcode;
                $this->dw_service->execute($sql1);
                $sql2 = "update dw_qrcode set status =3 where bianhao = ".$vehicle->bianhao;
                $this->dw_service->execute($sql2);
            }
        } catch (\Exception $e) {
            $this->dw_service->rollback();
            return $this->toError(500, '系统繁忙');
        }
        $this->dw_service->commit();
        return $this->toSuccess();
    }

}
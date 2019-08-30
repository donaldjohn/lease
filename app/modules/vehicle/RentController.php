<?php
namespace app\modules\vehicle;

use app\models\service\Qrcode;
use app\models\service\Vehicle;
use app\modules\BaseController;
// TODO:废弃
class RentController extends BaseController
{
    // 租赁编辑车辆
    /*public function EditVehicleAction($vehicleId)
    {
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
        // 更新车辆数据
        $bol = $vehicle->update($param);
        if (false==$bol){
            return $this->toError(500, '系统繁忙');
        }
        return $this->toSuccess();
    }*/
}
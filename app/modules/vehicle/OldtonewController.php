<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/16
 * Time: 19:33
 */
namespace app\modules\vehicle;

use app\models\service\Vehicle;

use app\models\service\VehicleUsage;
use app\modules\BaseController;

class OldtonewController extends BaseController
{
    /**
     * 查询老系统车辆信息
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function OldinfoAction()
    {
        // 定义请求字段
        $fields = [
            'id' => 0,
            'bianhao' => 0,
            'udid' => 0,
            'vin' => 0,
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        if (false === $parameter || empty($parameter)){
            return $this->toError(500, '参数不可全部为空');
        }
        // 如果没有设置id，先查询新系统是否存在车辆
        if (!isset($parameter['id'])){
            $vehicle = Vehicle::arrFindFirst($parameter);
            if ($vehicle){
                return $this->toError(500, '现有系统已有该车辆');
            }
        }
        $res = $this->curl->sendCurl($this->config->interface->oldSystem->erweimaInfo,$parameter,'POST');
        if(200 != $res['code']){
            return $this->toError(500, '系统异常：'.$res['message']);
        }
        if (is_null($res['data'])){
            return $this->toError(500, '未查询到车辆信息');
        }
        $data = $res['data'];
        // 接口可能未返回id字段
        if (!isset($data['id'])) $data['id']='';
        return $this->toSuccess($data);
    }

    // 车辆导入新系统
    public function MigrateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 定义请求数据
        $fields = [
            'bianhao' => '车辆编号不可为空',
            'udid' => '智能设备号不可为空',
            'vin' => '车架号不可为空',
//            'product_id' => '请选择商品',
//            'product_sku_relation_id' => '请选择商品规格',
        ];
        // 参数验证
        $parameter = $this->getArrPars($fields, $request);
        if (false===$parameter){
            return;
        }
        // 查询系统是否有该车辆
        $vehicle = Vehicle::arrFindFirst([
            'bianhao' => $parameter['bianhao'],
            'udid' => $parameter['udid'],
            'vin' => $parameter['vin'],
        ], 'or');
        if ($vehicle){
            return $this->toError(500, '现有系统已有该车辆');
        }
        $parameter['product_id'] = $this->config->oldSystemVehicle->product_id;
        $parameter['product_sku_relation_id'] = $this->config->oldSystemVehicle->product_sku_relation_id;
        // 默认值，防止数据库报错
        $parameter['driver_id'] = 0;
        $parameter['record_num'] = '';
        // 数据入库
        $VehicleModel = new Vehicle();
        $res = $VehicleModel->save($parameter);
        if (false===$res){
            return $this->toError(500, '保存失败');
        }
        // 默认移入车辆为门店属性
        (new VehicleUsage())->create([
            'vehicle_id' => $VehicleModel->id,
            'use_attribute' => 4,
            'create_time' => time(),
        ]);
        return $this->toSuccess();
    }
}
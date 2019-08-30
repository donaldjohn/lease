<?php
namespace app\modules\shrent;


use app\models\order\VehicleRentOrder;
use app\models\service\StoreVehicle;
use app\modules\BaseController;
use app\services\data\AlipayData;
use app\services\data\DriverData;
use app\services\data\RegionData;
use app\services\data\RentRepairData;
use app\services\data\VehicleData;
use app\services\data\WxpayData;
use app\services\data\PackageData;
use app\services\data\BillData;
use app\services\data\StoreData;
use app\services\data\CabinetData;
use app\services\data\RentWarrantyData;
use app\services\data\ServiceContractData;
use app\models\order\VehicleRepairOrder;
use app\common\errors\DataException;

//骑手APP模块
class DriverappController extends BaseController
{
    /**
     * 换电记录
     */
    public function ChargingrecAction()
    {
        $driverId = $this->authed->userId;
        $parameter['driverId'] = $driverId;
        if (isset($_GET['pageSize'])){
            $parameter['pageSize'] = $_GET['pageSize'];
        }
        if (isset($_GET['pageNum'])){
            $parameter['pageNum'] = $_GET['pageNum'];
        }
        // 获取换电记录
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10040",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['data'];
        $meta = $result['content']['pageInfo'] ?? null;
        // 换电柜ID 门店ID集合
        $cabinetIds = [];
        $storeIds = [];
        foreach ($list as $item){
            $cabinetIds[] = $item['cabinetId'];
            $storeIds[] = $item['storeId'];
        }
        $CabinetData = new CabinetData();
        // TODO:兼容二期上线前没有qrcode使用
        if (isset($list[0]) && !isset($list[0]['qrcode'])){
            // 获取换电柜信息
            $cabinets = $CabinetData->getCabinetByIds($cabinetIds, true);
        }
        // 获取门店信息
        $stores = (new StoreData())->getStoreByIds($storeIds, true);
        // 获取骑手姓名
        $driver = (new DriverData())->getDriverById($driverId);
        foreach ($list as $k => $v){
            // 处理姓名
            $list[$k]['driverName'] = $driver['realName'];
            // 处理金额
            $list[$k]['amount'] = round($v['price']/10000, 2);
            // 处理门店名称
            $list[$k]['storeName'] = isset($stores[(string)$v['storeId']]) ? $stores[(string)$v['storeId']]['storeName'] : '';
            // 换电柜编号
            // TODO:兼容二期上线前没有qrcode使用
            if (isset($cabinets)){
                $list[$k]['cabinetNo'] = isset($cabinets[$v['cabinetId']]) ? $cabinets[$v['cabinetId']]['qrcode'] : '';
            }else{
                $list[$k]['cabinetNo'] = $v['qrcode'];
            }

        }
        // 旧版返回信息
        if (!isset($_GET['new'])){
            return $this->toSuccess($list, $meta);
        }
        // 获取待支付信息
        $UnpaidInfo = $CabinetData->getUnpaidInfo($driverId);
        $data = [
            'list' => $list,
            'unpaidInfo' => $UnpaidInfo,
        ];
        return $this->toSuccess($data, $meta);
    }

}

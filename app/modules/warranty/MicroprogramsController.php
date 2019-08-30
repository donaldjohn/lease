<?php
namespace app\modules\warranty;

use app\models\service\VehicleArea;
use app\models\service\VehicleBom;
use app\modules\BaseController;

/**
 * Class MicroprogramsController
 * 门店车辆维修小程序接口
 * @author Lishiqin
 * @package app\modules\warranty
 */
class MicroprogramsController extends BaseController {

    // 维修订单状态
    const REPAIR_STATUS = [
        1 => '待接单',
        2 => '已接单',
        3 => '维修中',
        4 => '待支付',
        5 => '已完成',
        6 => '已取消'
    ];

    const SELF_PAY = 1;
    const WARRANTY_PAY = 2;

    /**
     * 获取车辆信息的维修记录 // TODO 接口服务异常
     * @param string bianhao 车辆编号信息
     * @return mixed
     */
    public function ScanAction()
    {
        // 判断车辆编号参数是否有效
        $vehicleBianhao = $this->request->get('bianhao', null);
        if (empty($vehicleBianhao)) {
            return $this->toError(500, '车辆编号不能为空');
        }

        $vehicleId[] = $vehicleBianhao;

        // 根据车辆ID查询维修订单信息
        $params = [
            'code' => 10055,
            'parameter' => [
                'vehicleId' => $vehicleId
            ]
        ];

        // 判断结果，并返回数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] == 200 && isset($result['content']['data'][0])) {
            $repairList = $result['content']['data'];
            $data = [];
            $count = 0;
            foreach ($repairList as $key => $value) {
               if ($value['repairStatus'] == 2 || $value['repairStatus'] == 3) {
                   $data[$count]['id']               = $value['id'];
                   $data[$count]['vehicleId']        = $value['vehicleId'];
                   $data[$count]['repairSn']         = $value['repairSn'];
                   $data[$count]['malfunctionUser']  = $value['malfunctionUser'];
                   $data[$count]['repairStatus']     = $value['repairStatus'];
                   $data[$count]['vehicleSku']       = $value['vehicleSku'];
                   $data[$count]['productName']      = !empty($value['productName']) ? $value['productName'] : ' - ';
                   $data[$count]['skuName']          = !empty($value['skuValues']) ? $value['skuValues'] : ' - ';
                   $data[$count]['malfunctionUser']  = !empty($value['malfunctionUser']) ? $value['malfunctionUser'] : ' - ';
                   $data[$count]['storeName']        = $this->userData->getStoreByInsId($this->authed->insId)['storeName'];
                   $data[$count]['status']           = $value['repairStatus'];
                   $data[$count]['image']            = !empty($value['imgUrl']) ? $value['imgUrl'] : '../../assets/images/product.png';
                   $count += 1;
               }
            }
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '暂无维修订单');
        }
    }


    /**
     * 获取维修记录详情
     * @param int id 维修订单
     * @return mixed
     */
    public function InfoAction()
    {
        // 请求参数有效性检测
        $repairId = $this->request->get('id', null);
        $type = $this->request->get('actionType', null);
        if (empty($repairId)) {
            return $this->toError(500, '维修订单ID不能为空');
        }

        // 根据维修订单ID查询订单详情
        $params = [
            'code' => 10058,
            'parameter' => [
                'id' => $repairId
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data']['id'])) {
            // 判断订单状态是否正确
            if ($type == 'change' && $result['content']['data']['repairStatus'] != 3) {
                return $this->toError(600, '订单状态已被改变，请刷新');
            }
            $repairInfo = $result['content']['data'];
            // 整理订单详情基础数据
            $data = [];
            $data['repairSn'] = $repairInfo['repairSn'];
            $data['time'] = date('Y-m-d H:i:s', $repairInfo['createAt']);
            $data['createAt'] = empty($repairInfo['createAt']) ? 0 : $repairInfo['createAt'];
            $data['updateAt'] = empty($repairInfo['updateAt']) ? 0 : $repairInfo['updateAt'];
            $data['receiveAt'] = empty($repairInfo['receiveAt']) ? 0 : $repairInfo['receiveAt'];
            $data['repairStartAt'] = empty($repairInfo['repairStartAt']) ? 0 : $repairInfo['repairStartAt'];
            $data['repairEndAt'] = empty($repairInfo['updateAt']) ? 0 : $repairInfo['repairEndAt'];
            $data['payAt'] = empty($repairInfo['updateAt']) ? 0 : $repairInfo['payAt'];
            $data['status'] = $repairInfo['repairStatus'];
            $data['malfunctionUser'] = $repairInfo['malfunctionUser'];
            $data['userSimpleName'] = $repairInfo['malfunctionUser'];
            $data['customerId'] = $repairInfo['customerId'];
            $data['customerName'] = $repairInfo['customerName'];
            $data['vehicleId'] = $repairInfo['vehicleId'];
            $data['vehicleInfo'] = $repairInfo['productName'].'-'.$repairInfo['skuValues'];
            $data['malfunctionMessage'] = $repairInfo['failureMessage'];
            $data['vehicleSku'] = $repairInfo['vehicleSku'];
            // 维修订单清单
            $warrantyPay  = [];
            $selfPay = [];
            $data['warrantyPayTotal'] = 0;
            $data['selfPayTotal'] = 0;
            $data['warrantyPayTotal'] = $repairInfo['productName'];
            if (isset($repairInfo['orderDetail']) && count($repairInfo['orderDetail']) > 0) {
                foreach ($repairInfo['orderDetail'] as $key => $value) {
                    $value['totalCount'] = $value['timeCost'] + $value['fittingCost'];
                    // TODO 费用字段修改下
                    switch ($value['payer']) {
                        case 1:
                            $selfPay[] = $value;
                            $data['selfPayTotal'] = (int)$data['selfPayTotal'] + (int)$value['totalCount'];
                            break;
                        case 2:
                            $warrantyPay[] = $value;
                            $data['warrantyPayTotal'] = (int)$data['warrantyPayTotal'] + (int)$value['totalCount'];
                            break;
                    }
                }
            }
            $data['warrantyPay'] = $warrantyPay;
            $data['selfPay'] = $selfPay;
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '获取详情失败');
        }
    }


    /**
     * 获取门店维修订单记录
     * @return mixed
     */
    public function ListAction()
    {
        $result = $this->userStore();
        if (!$result['status']) {
            return $this->toError(500, $result['msg']);
        }
        $store = $result['msg'];

        // 根据门店ID查询维修订单信息
        $params = [
            'code' => 10055,
            'parameter' => [
                'storeId' => $store['insId']
            ]
        ];

        // 判断结果，并返回数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data'][0])) {
            $repairList = $result['content']['data'];
            $data = [];
            foreach ($repairList as $key => $value) {
                $data[$key]['id']               = $value['id'];
                $data[$key]['vehicleId']        = $value['vehicleId'];
                $data[$key]['productName']      = $value['productName'];
                $data[$key]['skuName']          = $value['skuValues'];
                $data[$key]['malfunctionUser']  = $value['malfunctionUser'];
                $data[$key]['repairSn']         = $value['repairSn'];
                $data[$key]['repairStatus']     = $value['repairStatus'];
                $data[$key]['repairStatusName'] = self::REPAIR_STATUS[$value['repairStatus']];
                $data[$key]['productImg']       = $value['imgUrl'];
                $data[$key]['vehicleSku']       = $value['vehicleSku'];
            }
            return $this->toSuccess($data);
        } else {
            return $this->toError(500, '暂无维修订单');
        }
    }

    /**
     * 获取车辆bom区域列表
     * @param string vehicleSku
     * @return mixed
     */
    public function AreaAction() {
        // 请求参数验证
        $request = $this->request->getJsonRawBody();
        $vehicleSku = isset($request->vehicleSku) ? $request->vehicleSku : 0;

        if ($vehicleSku == 0) {
            return $this->toError(500, 'vehicleSku不能为空');
        }

        // 获取车辆vehicelSku对应的vehicleType参数
        $vehicleBom = VehicleBom::query()->where('vehicle_sku = :vehicle_sku:', array('vehicle_sku' => $vehicleSku))->execute()->toArray();
        if (!isset($vehicleBom[0]['vehicle_type']) && $vehicleBom[0]['vehicle_type'] > 0) {
            return $this->toError(500, '获取车辆BOM信息失败');
        }

        // 通过vheicleType获取对应的部位列表
        $vehicleArea = VehicleArea::query()->where('vehicle_type_id = :vehicle_type_id: AND area_status = :area_status:', array('vehicle_type_id' => $vehicleBom[0]['vehicle_type'], 'area_status' => 1))->orderBy('area_order asc')->execute()->toArray();
        if (!isset($vehicleArea[0])) {
            return $this->toError(500, '获取车辆BOM区域失败');
        }

        $data = [];
        foreach ($vehicleArea as $key => $value) {
            $data[$key]['id'] = $value['id'];
            $data[$key]['area_name'] = $value['area_name'];
        }
        return $this->toSuccess($data);
    }


    /**
     * 根据维修订单ID获取商品BOM清单
     * @return mixed
     */
    public function BomAction()
    {
        // 对请求参数进行验证
        $request    = $this->request->getJsonRawBody();
        $vehicleId  = isset($request->vehicleId) ? $request->vehicleId : 0;
        $customerId = isset($request->customerId) ? $request->customerId : 0;
        $repairId = isset($request->repairId) ? $request->repairId : 0;
        $areaId     = $this->userData->getStoreByInsId($this->authed->insId)['areaId'];

        if ($repairId == 0) {
            return $this->toError(500, '维修单ID不能为空');
        }

        if ($vehicleId == 0) {
            return $this->toError(500, '车辆ID不能为空');
        }

        if ($customerId == 0) {
            return $this->toError(500, '客户ID不能为空');
        }

        // 获取车辆联保信息
        $params = [
            'code' => 11024,
            'parameter' => [
                'vehicleId'  => $vehicleId,
                'customerId' => $customerId,
                'status'     => 2
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] != 200 || !isset($result['content']['data'][0])) {
            return $this->toError(500, '获取车辆联保信息失败');
        }

        $params = [
            'code' => 11025,
            'parameter' => [
                'id'  => $result['content']['data'][0]['id']
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        $list = $result['content']['data']['orderDetail'];
        $warrantyList = [];
        foreach ($list as $key => $value) {
            $warrantyList[$value['bomElementId']] = $value;
            $warrantyList[$value['bomElementId']]['warrantyType'] = $result['content']['data']['warrantyType'];
            $warrantyList[$value['bomElementId']]['warrantySn'] = $result['content']['data']['warrantySn'];
        }

        // 请求微服务接口获取车辆的区域价格方案
        $params = [
            'code' => 11007,
            'parameter' => [
                'vehicleId'  => $vehicleId,
                'customerId' => $customerId,
                'areaId'     => $areaId
            ]
        ];

        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] != 200 && !isset($result['content']['data']['schemeDetails'])) {
            return $this->toError(500, '获取价格方案失败');
        }

        if ($result['content']['data']['schemeStatus'] != 1) {
            return $this->toError(500, '维修方案不可用');
        }

        $priceList = $result['content']['data']['schemeDetails'];

        // 获取车辆BOM详情
        $vehicleSku  = isset($request->vehicleSku) ? $request->vehicleSku : 0;
        if ($vehicleSku == 0) {
            return $this->toError(500, '查询车辆BOM详情失败，没有vehicleSku');
        }
        $params = [
            'code' => 10052,
            'parameter' => [
                'vehicleSku'  => $vehicleSku
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] != 200 && !isset($result['content']['data']['bomElement'])) {
            return $this->toError(500, '获取价格方案失败');
        }
        $list = $result['content']['data']['bomElement'];
        $elementList = [];

        foreach ($list as $key => $value) {
            $elementList[$value['id']] = $value;
        }

        $data = [];
        $count = 0;

        foreach ($priceList as $key => $value) {
            if (isset($elementList[$value['bomElementId']])) {
                $bomInfo = $elementList[$value['bomElementId']];
                $data[$count] = $value;
                $data[$count]['bomElementId'] = $value['bomElementId'];
                $data[$count]['skuValues'] = $bomInfo['skuValues'];
                $data[$count]['number'] = $count;
                $data[$count]['repairId'] = $repairId;
                $data[$count]['image'] = '../../assets/images/sub-product.png';
                $data[$count]['productName'] = $bomInfo['productName'];
                $data[$count]['elementName'] = $bomInfo['elementName'];
                $data[$count]['place'] = $bomInfo['elementArea'];
                $data[$count]['electric'] = $bomInfo['isElectric'] == 1 ? 1 : 2;

                // 插入联保订单信息
                if (isset($warrantyList[$value['bomElementId']])) {
                    $data[$count]['warrantyOrderId'] = $warrantyList[$value['bomElementId']]['warrantyOrderId'];
                    $data[$count]['elementExp'] = $warrantyList[$value['bomElementId']]['elementExp'];
                    $data[$count]['effectAt'] = $warrantyList[$value['bomElementId']]['effectAt'];
                    $data[$count]['warrantyType'] = $warrantyList[$value['bomElementId']]['warrantyType'];
                    $data[$count]['warrantySn'] = $warrantyList[$value['bomElementId']]['warrantySn'];
                }

                $data[$count]['areaName'] = $bomInfo['areaName'];
                $data[$count]['elementOrder'] = $bomInfo['elementOrder'];
                $data[$count]['elementStatus'] = $bomInfo['elementStatus'];
                $data[$count]['select'] = 0;
                $data[$count]['brokenType'] = 0;
                if ($value['enableRepair'] == 1 && $value['enableChange'] == 1) {
                    $data[$count]['repairType'] = 0;
                }
                if ($value['enableRepair'] == 1 && $value['enableChange'] != 1) {
                    $data[$count]['repairType'] = -1;
                }
                if ($value['enableRepair'] != 1 && $value['enableChange'] == 1) {
                    $data[$count]['repairType'] = -2;
                }
                $count += 1;
            }
        }
        return $this->toSuccess($data);
    }

    /**
     * 计算维修单价格
     * @return mixed
     */
    public function CountAction()
    {
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['list']) || count($request['list']) <= 0) {
            return $this->toError(500, '清单内容无效');
        }
        $id = isset($request['id']) ? $request['id'] : 0;
        if ($id == 0) {
            return $this->toError(500, '订单ID不能为空');
        }

        $list = $request['list'];
        $selfPayTotal = 0; // 自费项目总价
        $warrantyPayTotal = 0; // 联保项目总价
        $warrantyCount = 0; // 联保项目计数器
        $selfList = []; // 自费项目清单
        $selfCount = 0; // 自费项目计数器
        $warrantyList = []; // 联保项目清单
        foreach ($list as $key => $value) {
            // 第一步：基本数据填充
            if ($value['repairType'] == -1) {
                $value['repairType'] = 1;
            }

            if ($value['repairType'] == -2) {
                $value['repairType'] = 2;
            }

            if ($value['repairType'] == 2 || $value['repairType'] == -2) {
                $fittingCost = $this->getPrice($value['elementSku']);
                if (!$fittingCost) {
                    return $this->toError(505, '商品价格不可用');
                }
            } else {
                $fittingCost = 0;
            }

            // 第二部：判断费用分配
            $limitTime = $value['effectAt'] + $value['elementExp'] * 86400; // 联保到期时间

            // 获取订单的创建时间
            $params = [
                'code' => '10058',
                'parameter' => [
                    'id' => $id
                ]
            ];
            $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
            if ($result['statusCode'] != 200 || !isset($result['content']['data']['createAt'])) {
                return $this->toError(500, "获取订单详情失败");
            }
            $createAt = $result['content']['data']['createAt'];

            if ($limitTime > $createAt) {
                if ($value['warrantyType'] == 2) {
                    // 未过期联保
                    $repairId = $value['repairId'];
                    $warrantyList[$warrantyCount]['repairId'] = $value['repairId'];
                    $warrantyList[$warrantyCount]['elementSku'] = $value['elementSku'];
                    $warrantyList[$warrantyCount]['productName'] = $value['productName'];
                    $warrantyList[$warrantyCount]['repairType'] = $value['repairType'];
                    $warrantyList[$warrantyCount]['productSku'] = $value['skuValues'];
                    $warrantyList[$warrantyCount]['damagedType'] = $value['brokenType'];
                    $warrantyList[$warrantyCount]['fittingCost'] = $value['repairType'] == 2 ? $fittingCost : 0;
                    $warrantyList[$warrantyCount]['timeCost'] = $value['repairType'] == 2 ? $value['changeCost'] : $value['repairCost'];
                    $warrantyPayTotal += ($warrantyList[$warrantyCount]['fittingCost'] + $warrantyList[$warrantyCount]['timeCost']);
                    $warrantyList[$warrantyCount]['payer'] = self::WARRANTY_PAY;
                    $warrantyCount++;
                } else {
                    if ($value['brokenType'] == 2) {
                        // 未过期联保
                        $repairId = $value['repairId'];
                        $warrantyList[$warrantyCount]['repairId'] = $value['repairId'];
                        $warrantyList[$warrantyCount]['elementSku'] = $value['elementSku'];
                        $warrantyList[$warrantyCount]['productName'] = $value['productName'];
                        $warrantyList[$warrantyCount]['repairType'] = $value['repairType'];
                        $warrantyList[$warrantyCount]['productSku'] = $value['skuValues'];
                        $warrantyList[$warrantyCount]['damagedType'] = $value['brokenType'];
                        $warrantyList[$warrantyCount]['fittingCost'] = $value['repairType'] == 2 ? $fittingCost : 0;
                        $warrantyList[$warrantyCount]['timeCost'] = $value['repairType'] == 2 ? $value['changeCost'] : $value['repairCost'];
                        $warrantyPayTotal += ($warrantyList[$warrantyCount]['fittingCost'] + $warrantyList[$warrantyCount]['timeCost']);
                        $warrantyList[$warrantyCount]['payer'] = self::WARRANTY_PAY;
                        $warrantyCount++;
                    } else {
                        $repairId = $value['repairId'];
                        $selfList[$selfCount]['repairId'] = $value['repairId'];
                        $selfList[$selfCount]['elementSku'] = $value['elementSku'];
                        $selfList[$selfCount]['productName'] = $value['productName'];
                        $selfList[$selfCount]['repairType'] = $value['repairType'];
                        $selfList[$selfCount]['productSku'] = $value['skuValues'];
                        $selfList[$selfCount]['damagedType'] = $value['brokenType'];
                        $selfList[$selfCount]['fittingCost'] = $value['repairType'] == 2 ? $fittingCost : 0;
                        $selfList[$selfCount]['timeCost'] = $value['repairType'] == 2 ? $value['changeCost'] : $value['repairCost'];
                        $selfPayTotal += ($selfList[$selfCount]['fittingCost'] + $selfList[$selfCount]['timeCost']);
                        $selfList[$selfCount]['payer'] = self::SELF_PAY;
                        $selfCount++;
                    }
                }
            } else {
                // 已过期联保
                $repairId = $value['repairId'];
                $selfList[$selfCount]['repairId'] = $value['repairId'];
                $selfList[$selfCount]['elementSku'] = $value['elementSku'];
                $selfList[$selfCount]['productName'] = $value['productName'];
                $selfList[$selfCount]['repairType'] = $value['repairType'];
                $selfList[$selfCount]['productSku'] = $value['skuValues'];
                $selfList[$selfCount]['damagedType'] = $value['brokenType'];
                $selfList[$selfCount]['fittingCost'] = $value['repairType'] == 2 ? $fittingCost : 0;
                $selfList[$selfCount]['timeCost'] = $value['repairType'] == 2 ? $value['changeCost'] : $value['repairCost'];
                $selfPayTotal += ($selfList[$selfCount]['fittingCost'] + $selfList[$selfCount]['timeCost']);
                $selfList[$selfCount]['payer'] = self::SELF_PAY;
                $selfCount++;
            }
        }

        // 第三步：封装返回数据
        $data['selfPayTotal'] = $selfPayTotal;
        $data['warrantyPayTotal'] = $warrantyPayTotal;
        $data['selfList'] = $selfList;
        $data['warrantyList'] = $warrantyList;
        $data['repairId'] = $repairId;
        return $this->toSuccess($data);
    }

    /**
     * 提交维修清单
     * @return mixed
     */
    public function SendAction()
    {
        $request = $this->request->getJsonRawBody(true);

        if (!isset($request['list'])) {
            return $this->toError(500, '清单内容无效');
        }

        $list = $request['list'];
        $id = 0;
        $detail = [];
        $count = 0;
        foreach ($list['warrantyList'] as $key => $value) {
            $id = $value['repairId'];
            $detail[$count]['productSku'] = $value['elementSku'];
            $detail[$count]['damagedType'] = $value['damagedType'];
            $detail[$count]['repairType'] = $value['repairType'];
            $detail[$count]['fittingCost'] = $value['fittingCost'];
            $detail[$count]['timeCost'] = $value['timeCost'];
            $detail[$count]['payer'] = $value['payer'];
            $count++;
        }

        foreach ($list['selfList'] as $key => $value) {
            $id = $value['repairId'];
            $detail[$count]['productSku'] = $value['elementSku'];
            $detail[$count]['damagedType'] = $value['damagedType'];
            $detail[$count]['repairType'] = $value['repairType'];
            $detail[$count]['fittingCost'] = $value['fittingCost'];
            $detail[$count]['timeCost'] = $value['timeCost'];
            $detail[$count]['payer'] = $value['payer'];
            $count++;
        }


        // 获取车辆联保信息
        $params = [
            'code' => 10057,
            'parameter' => [
                'id'  => $id,
                'detail' => $detail
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] == 200) {
            // 推送维修订单最新信息
            $this->changeOrderInfo($id);

            return $this->toSuccess();
        } else {
            return $this->toError(500, '维修订单提交失败');
        }
    }


    /**
     * 修改维修单状态【完成】
     * @return mixed
     */
    public function ChangeAction()
    {
        // 对请求参数进行验证
        $request = $this->request->getJsonRawBody();
        $id = isset($request->id) ? $request->id : 0;
        if ($id == 0) {
            return $this->toError(500, '维修单ID不能为空');
        }

        $repairStatus = isset($request->status) ? $request->status : 0;
        if ($repairStatus == 0) {
            return $this->toError(500, '维修单状态不能为空');
        }

        $result = $this->getRepairStatus($id);
        if ($result != 2) {
            return $this->toError(600, '订单状态已被更新，请刷新');
        }

        // 请求车辆信息查询接口
        $params = [
            'code' => 10054,
            'parameter' => [
                'id'           => $id,
                'repairStatus' => $repairStatus
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");

        if ($result['statusCode'] == 200) {
            // 推送维修订单最新信息
            $this->changeOrderInfo($id);

            return $this->toSuccess();
        } else {
            return $this->toError(500, '修改订单状态失败');
        }
    }


    /**
     * 辅助方法：根据车辆编号获取车辆信息
     * @param string $bianhao 车辆编号
     * @return mixed
     */
    public function searchVehicle($bianhao = '')
    {
        // 判断参数车辆编号有效性
        if (empty($bianhao)) {
            return ['status' => false, 'msg' => '车辆编号不能为空'];
        }

        // 请求车辆信息查询接口
        $bianhaoList[] = $bianhao;
        $params = [
            'code' => 60012,
            'parameter' => [
                'bianhaoList' => $bianhaoList
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $params, "post");

        // 查询结果进行判断并返回信息
        if ($result['statusCode'] == 200 && isset($result['content']['vehicleDOS'][0])) {
            return ['status' => true, 'msg' => $result['content']['vehicleDOS'][0]];
        } else {
            return ['status' => false, 'msg' => '车辆信息查询失败'];
        }
    }


    /**
     * 辅助方法：通过用户ID获取门店信息
     * @return mixed
     */
    public function userStore()
    {
        $store = $this->userData->getStoreByInsId($this->authed->insId);
        $store['userName'] = $this->authed->userName;
        return ['status' => true, 'msg' => $store];
    }

    /**
     * 辅助方法：获取配件价格
     */
    public function getPrice($skuId)
    {
        // 请求车辆信息查询接口
        $params = [
            'code' => 10031,
            'parameter' => [
                'skuId' => $skuId
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->product, $params, "post");
        // 查询结果进行判断并返回信息
        if ($result['statusCode'] == 200 && isset($result['content'])) {
            return $result['content']['fittingPrice'];
        } else {
            return false;
        }
    }

    /**
     * 辅助方法：向租赁端推送维修订单状态返回
     * @param int $repairId 维修订单ID
     * @return mixed
     */
    public function changeOrderInfo($repairId = null)
    {
        // 判断订单编号是否有效
        if (empty($repairId)) {
            return ['status' => false, 'msg' => '订单编号不能为空'];
        }

        // 请求微服务，获取订单详情
        $params = [
            'code' => 10058,
            'parameter' => [
                'id' => $repairId
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] != 200 || !isset($result['content']['data'])) {
            return ['status' => false, 'msg' => '推送的订单详情信息获取失败'];
        }

        // 对订单详情进行数据拼装
        $info = $result['content']['data'];
        $data['repairSn'] = $info['repairSn'];
        $data['status'] = $info['repairStatus'];
        if (count($info['orderDetail']) > 0) {
            $warrantyPayTotal = 0;
            $selfPayTotal = 0;
            $repairInfo = '';
            foreach ($info['orderDetail'] as $key => $value) {
                if ($value['payer'] ==1) {
                    $selfPayTotal = $selfPayTotal + $value['fittingCost'] + $value['timeCost'];
                } else {
                    $warrantyPayTotal = $selfPayTotal + $value['fittingCost'] + $value['timeCost'];
                }
                $repairInfo = $repairInfo.$value['productName'].'*1|';
            }
            $data['repairInfo'] = $repairInfo;
            $data['cost']['warrantyPayTotal'] = $warrantyPayTotal;
            $data['cost']['selfPayTotal'] = $selfPayTotal;
        } else {
            $data['repairInfo'] = '';
            $data['cost']['warrantyPayTotal'] = 0;
            $data['cost']['selfPayTotal'] = 0;
        }
        $result = $this->curl->sendCurl($this->config->baseUrl.$this->config->interface->warranty->sendRepair->url, $data, "POST", []);
        if ($result['statusCode'] == 200) {
            $this->logger->info("维修订单：".$info['repairSn']."推送消息成功");
        } else {
            $this->logger->error("维修订单：".$info['repairSn']."推送消息失败");
        }
        return ['status' => true, 'msg' => $data];
    }

    /**
     * 获取订单的状态
     * @param $id int 维修订单ID
     * @return int
     */
    public function getRepairStatus($id) {
        $params = [
            'code' => '10058',
            'parameter' => [
                'id' => $id
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['data']['repairStatus'])) {
            return $result['content']['data']['repairStatus'];
        } else {
            return 0;
        }
    }
}

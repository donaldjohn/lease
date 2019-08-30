<?php
namespace app\modules\cabinet;

use app\models\Cabinet;
use app\models\CabinetAllRoom;
use app\modules\BaseController;

/**
 * Class AdminController
 * 换电柜管理后台API类
 * @Author Lishiqin
 * @package app\modules\microprograms
 */
class AdminController extends BaseController {

    // 换电柜异常类型
    public static $abnormal = [
        1 => '充电板',
        2 => '物理锁',
        3 => '不通电'
    ];

    /**
     * 换电柜列表及搜索
     * @method GET
     * @param string cabinetId  主板编号
     * @param string qrcode     换电柜编号
     * @param string storeName  网点名称
     * @param int    pageNum    页码
     * @param int    pageSize   每页数量
     * @return mixed
     */
    public function BoardAction()
    {
        try {
            // 对请求参数进行过滤
            $request = $this->request->get();
            $cabinetId = isset($request['cabinetId']) ? $request['cabinetId'] : "";
            $qrcode = isset($request['qrcode']) ? $request['qrcode'] : "";
            $storeName = isset($request['storeName']) ? $request['storeName'] : "";
            $pageSize = isset($request['pageSize']) ? $request['pageSize'] : 20;
            $pageNum = isset($request['pageNum']) ? $request['pageNum'] : 1;

            // API请求所需参数封装
            $params['code']     = 30004;
            $params['parameter']['pageSize'] = $pageSize;
            $params['parameter']['pageNum']  = $pageNum;

            if (!empty($cabinetId)) {
                $params['parameter']['cabinetId'] = $cabinetId;
            }
            if (!empty($qrcode)) {
                $params['parameter']['qrcode']    = $qrcode;
            }
            if (!empty($storeName)) {
                // 通过门店名称获取门店ID
                $result = $this->searchStore($storeName);
                $list = [];

                if (!$result) {
                    return $this->toError(500, '门店不存在');
                }

                if (is_array($result)) {
                    foreach ($result as $key => $value) {
                        $list[] = $key;
                    }
                    $params['parameter']['storeId'] = $list;
                } else {
                    $params['parameter']['storeId'][] = 0;
                }
            }

            // 请求微服务获取换电柜列表
            $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");

            // 判断结果，并返回
            if ($result['statusCode'] == 200 && isset($result['content']['cabinetList'][0])) {
                // 获取所有门店信息
                $allStore = $this->allStore();
                // 定义返回结果数组
                $data = [];
                foreach ($result['content']['cabinetList'] as $key => $value) {
                    $data[$key]['id']           = $value['id'];
                    $data[$key]['cabinetId']    = $value['cabinetId'];
                    $data[$key]['storeId']      = $value['storeId'];
                    $data[$key]['qrcode']       = $value['qrcode'];
                    $data[$key]['maxNum']       = empty($value['maxNum']) ? 0 : $value['maxNum'];
                    $data[$key]['createTime']   = date('Y-m-d H:i', $value['createTime']);
                    $data[$key]['lastTime']   = date('Y-m-d H:i', $value['lastTime']);
                    $data[$key]['heartbeatTime']   = date('Y-m-d H:i', $value['heartbeatTime']);
                    // 判断该充电会是否绑定门店
                    $store = isset($allStore[$value['storeId']]) ? $allStore[$value['storeId']] : '';
                    $data[$key]['storeName']    = isset($store['storeName']) ? $store['storeName'] : '-';
                    $data[$key]['storeAddress'] = isset($store['address']) ? $store['address'] : '-';
                    $data[$key]['storePhone']   = isset($store['linkPhone']) ? $store['linkPhone'] : '-';
                }
                $pageInfo = $result['content']['pageInfo'];
                return $this->toSuccess($data, $pageInfo);
            } else {
                $pageInfo = [
                    'total'    => 0,
                    'pageNum'  => 1,
                    'pageSize' => 10
                ];
                return $this->toSuccess([], $pageInfo);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 换电柜异常列表及搜索
     * @method GET
     * @param int    abnormalType  异常类型
     * @param string qrcode        换电柜编号
     * @param string storeName     网点名称
     * @return mixed
     */
    public function ErrorAction()
    {
        try {
            // 对请求参数进行过滤
            $request = $this->request->get();
            $abnormalType = isset($request['abnormalType']) ? $request['abnormalType'] : 0;
            $qrcode = isset($request['qrcode']) ? $request['qrcode'] : "";
            $storeName = isset($request['storeName']) ? $request['storeName'] : "";
            $pageSize = isset($request['pageSize']) ? $request['pageSize'] : 20;
            $pageNum = isset($request['pageNum']) ? $request['pageNum'] : 1;

            // API请求所需参数封装
            $params['code'] = 30007;
            $params['parameter']['pageSize'] = $pageSize;
            $params['parameter']['pageNum']  = $pageNum;
            if ($abnormalType) {
                $params['parameter']['abnormalType'] = self::$abnormal[$abnormalType];
            }
            if ($qrcode) {
                $params['parameter']['qrcode'] = $qrcode;
            }
            if ($storeName) {
                // 通过门店名称获取门店ID
                $result = $this->searchStore($storeName);
                if (!$result) {
                    return $this->toError(500, '门店不存在');
                }

                $list = [];
                if (is_array($result)) {
                    foreach ($result as $key => $value) {
                        $list[] = $key;
                    }
                    $params['parameter']['storeId'] = $list;
                } else {
                    $params['parameter']['storeId'][] = 0;
                }
            }

            // 请求微服务获取换电柜异常列表
            $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");

            // 判断结果，并返回
            if ($result['statusCode'] == 200 && isset($result['content']['abnormalList'][0])) {
                // 获取所有门店信息
                $allStore = $this->allStore();
                // 定义返回结果数组
                $data = [];
                foreach ($result['content']['abnormalList'] as $key => $value) {
                    $data[$key]['id']           = $value['id'];
                    $data[$key]['qrcode']       = isset($value['qrcode']) ? $value['qrcode'] : ' - ';
                    $data[$key]['abnormalType'] = $value['abnormalType'];
                    $data[$key]['abnormalTime'] = date('Y-m-d H:i', $value['createTime']);
                    // 判断该充电会是否绑定门店
                    $store = isset($allStore[$value['storeId']]) ? $allStore[$value['storeId']] : '';
                    $data[$key]['storeName']    = isset($store['storeName']) ? $store['storeName'] : '-';
                    $data[$key]['storePhone']   = isset($store['linkPhone']) ? $store['linkPhone'] : '-';
                    $data[$key]['storeAddress'] = isset($store['address']) ? $store['address'] : '-';
                }
                $pageInfo = $result['content']['pageInfo'];
                return $this->toSuccess($data, $pageInfo);
            } else {
                $pageInfo = [
                    'total'    => 0,
                    'pageNum'  => 1,
                    'pageSize' => 10
                ];
                return $this->toSuccess([], $pageInfo);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 获取所有换电柜的柜组状态
     * @return mixed
     */
    public function roomAction() {
        $request = $this->request->get();
        if (!isset($request['id'])) {
            return $this->toError(500, '换电柜ID不能为空');
        }

        $rooms = CabinetAllRoom::query()->where('cabinet_num = :cabinet_num:', $params = ['cabinet_num' => $request['id']])->execute()->toArray();

        if (count($rooms) != 6) {
            return $this->toError(500, '获取柜子列表状态异常');
        }

        $data = [];
        foreach ($rooms as $key => $value) {
            $data[$key]['room_num'] = $value['room_num'];

            if ($value['door_status'] < 0) {
                $data[$key]['door_status'] = '异常';
            } else {
                $data[$key]['door_status'] = $value['door_status'] == 0 ? '开启' : '关闭';
            }

            if ($value['battery_status'] < 0) {
                $data[$key]['battery_status'] = '异常';
            } else {
                $data[$key]['battery_status'] = $value['battery_status'] == 0 ? '充满' : '充电中';
            }
            $data[$key]['is_empty'] = $value['is_empty'] == 1 ? '空柜' : '满柜';
            $data[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
        }
        return $this->toSuccess($data);
    }

    /**
     * 换电柜柜子操作命令
     */
    public function operationAction()
    {
        $request = $this->request->getJsonRawBody();
        if (!isset($request->qrcode)) {
            return $this->toError(500, '换电柜二维码不能为空');
        }

        $request = $this->request->getJsonRawBody();
        if (!isset($request->roomNum)) {
            return $this->toError(500, '仓门编号不能为空');
        }

        $request = $this->request->getJsonRawBody();
        if (!isset($request->operation)) {
            return $this->toError(500, '操作指令不能为空');
        }

        $params = [
            'code' => 30006,
            'parameter' => [
                'roomNum'   => $request->roomNum,
                'qrcode'    => $request->qrcode,
                'operation' => $request->operation,
                'action'    => 3
            ]
        ];

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");

        if ($result['statusCode'] == 200) {
            return $this->toSuccess('操作成功');
        } else {
           return $this->toError(500, '操作失败');
        }
    }

    /**
     * 换电柜解除门店
     */
    public function cancelAction() {
        $request = $this->request->getJsonRawBody();
        if (!isset($request->qrcode)) {
            return $this->toError(500, '换电柜编号不能为空');
        }

        $cabinet = Cabinet::query()->where('qrcode = :qrcode:', $params = ['qrcode' => $request->qrcode])->execute();
        $tmp = clone $cabinet;
        if (count($tmp->toArray()) != 1) {
            return $this->toError(500, '换电柜不存在');
        }

        $result = $cabinet->update(['store_id' => null]);
        if ($result) {
            return $this->toSuccess();
        } else {
            return $this->toError(500, '换电柜解除门店失败');
        }
    }

    /**
     * 换电柜换电记录查询
     * @method GET
     * @param int    abnormalType  异常类型
     * @param string qrcode        换电柜编号
     * @param string storeName     网点名称
     * @return mixed
     */
    public function RecordAction()
    {
        try {
            // 对请求参数进行过滤
            $request = $this->request->get();
            $pageSize = isset($request['pageSize']) ? $request['pageSize'] : 20;
            $pageNum = isset($request['pageNum']) ? $request['pageNum'] : 1;

            // 筛选条件参数
            $serviceSn = isset($request['serviceSn']) ? $request['serviceSn'] : ''; // 换电订单号条件搜索
            $businessSn = isset($request['businessSn']) ? $request['businessSn'] : ''; // 换电订单号条件搜索
            $startTime = isset($request['startTime']) ? $request['startTime'] : ''; // 换电订单开始时间
            $endTime = isset($request['endTime']) ? $request['endTime'] : ''; // 换电订单结束时间
            $storeId = isset($request['storeId']) ? $request['storeId'] : 0; // 门店ID
            $qrcode = isset($request['qrcode']) ? $request['qrcode'] : ''; // 换电柜编号
            $driverId = isset($request['driverId']) ? $request['driverId'] : 0; // 骑手ID
            $areaId = isset($request['areaId']) ? $request['areaId'] : 0; // 区域ID
            $areaDeep = isset($request['areaDeep']) ? $request['areaDeep'] : 0; // 区域深度
            $operatorInsId = isset($request['operatorInsId']) ? $request['operatorInsId'] : null;
            $parentOperatorInsId = isset($request['parentOperatorInsId']) ? $request['parentOperatorInsId'] : null;

            // API请求所需参数封装
            $params['code'] = 10040;
            $params['parameter']['pageSize'] = $pageSize;
            $params['parameter']['pageNum']  = $pageNum;

            // 区域下门店集合
            if ($areaId > 0 && $areaDeep > 0) {
                if (count($this->storeAreaSearch($areaId, $areaDeep)) > 0) {
                    $params['parameter']['storeIds']  = $this->storeAreaSearch($areaId, $areaDeep);
                } else {
                    $params['parameter']['storeIds'][0] = -1;
                }
            }

            // 换电柜编号筛选
            if (!empty($qrcode)) {
                // TODO:暂不删除世钦cabinetId代码，兼容二期上线前使用
                $cabinetId = $this->searchCabinetId($qrcode);
                if ($cabinetId > 0){
                    $params['parameter']['cabinetId']  = $cabinetId;
                }
                $params['parameter']['qrcode']  = $qrcode;
            }

            // 换电订单筛选
            if (!empty($serviceSn)) {
                $params['parameter']['serviceSn']  = $serviceSn;
            }

            // 支付订单筛选
            if (!empty($businessSn)) {
                $params['parameter']['businessSn']  = $businessSn;
            }

            // 换电单开始时间
            if (!empty($startTime)) {
                $params['parameter']['startTime']  = $startTime;
            }

            // 换电单结束时间
            if (!empty($endTime)) {
                $params['parameter']['endTime']  = $endTime;
            }

            // 骑手ID筛选
            if (!empty($driverId)) {
                $params['parameter']['driverId']  = $driverId;
            }

            // 门店ID筛选
            if (!empty($storeId)) {
                $params['parameter']['storeIds'][]  = $storeId > 0 ? $storeId : -1;
            }
            $params['parameter']['operatorInsId']  = $operatorInsId;
            $params['parameter']['parentOperatorInsId']  = $parentOperatorInsId;
            if ($this->authed->userType == 9) {
                $params['parameter']['operatorInsId'] = $this->authed->insId;
            } else if ($this->authed->userType == 11) {
                $params['parameter']['parentOperatorInsId'] = $this->authed->insId;
            }
            // 请求微服务获取换电柜异常列表
            $result = $this->curl->httpRequest($this->Zuul->order, $params, "POST");

            // 判断结果，并返回
            if ($result['statusCode'] == 200 && isset($result['content']['data']) && count($result['content']['data']) > 0) {
                $list = $result['content']['data'];
                $driversId = [];
                // 获取对应的骑手ID集合
                foreach ($list as $key => $value) {
                    $driversId[] = $value['driverId'];
                }

                $drivers = $this->getDriversInfo($driversId);
                $stores = $this->allStore();

                if (is_array($drivers) || isset($drivers)) {
                    // 获取所有门店信息
                    $allRecord = $result['content']['data'];
                    // 定义返回结果数组
                    $data = [];

                    foreach ($allRecord as $key => $value) {
                        $driver = isset($drivers[$value['driverId']]) ? $drivers[$value['driverId']] : [];
                        $store = isset($stores[$value['storeId']]) ? $stores[$value['storeId']] : [];
                        // 记录的骑手信息
                        $data[$key]['driverId'] = count($driver) > 0 ? $driver['id'] : ' - ';
                        $data[$key]['driverName'] = count($driver) > 0 ? $driver['userName'] : ' - ';
                        $data[$key]['realName'] = count($driver) > 0 ? $driver['realName'] : ' - ';
                        $data[$key]['roleId'] = count($driver) > 0 ? $driver['roleId'] : ' - ';
                        $data[$key]['phone'] = count($driver) > 0 ? $driver['phone'] : ' - ';
                        $data[$key]['jobNo'] = count($driver) > 0 ? $driver['jobNo'] : ' - ';

                        // 记录的门店信息
                        $data[$key]['storeName'] = count($store) > 0 ? $store['storeName'] : ' - ';
                        $data[$key]['storeAddress'] = count($store) > 0 ? $store['address'] : ' - ';
                        $data[$key]['linkPhone'] = count($store) > 0 ? $store['linkPhone'] : ' - ';
                        $data[$key]['linkMan'] = count($store) > 0 ? $store['linkMan'] : ' - ';
                        // TODO:暂不删除世钦searchCabinetQrcode代码，兼容二期上线前使用
                        $data[$key]['qrcode'] = $value['qrcode'] ?? $this->searchCabinetQrcode($value['cabinetId']);
                        $data[$key]['serviceSn'] = $value['serviceSn'];
                        $data[$key]['chargingSn'] = $value['chargingSn'];
                        $data[$key]['cabinetId'] = $value['cabinetId'];
                        $data[$key]['price']     = $value['price'] / 10000;
                        $data[$key]['payStatus'] = $value['payStatus'] == 2 ? '已支付' : '待支付';
                        $data[$key]['businessSn'] = $value['businessSn'];
                        $data[$key]['time'] = date('Y-m-d H:i', $value['chargingTime']);
                        $data[$key]['payTime'] = date('Y-m-d H:i', $value['payTime']);
                        $data[$key]['insName'] = isset($value['insName']) ? $value['insName'] : '-' ;
                        $data[$key]['parentInsName'] = isset($value['parentInsName']) ? $value['parentInsName'] : '-' ;
                    }
                    return $this->toSuccess($data, $result['content']['pageInfo']);
                } else {
                    return $this->toSuccess([], $result['content']['pageInfo']);
                }
            } else {
                return $this->toSuccess([], $result['content']['pageInfo']);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 换电柜绑定门店及重置换电柜API
     * @method POST
     * @param int    storeId    门店ID（二选一）
     * @param string qrcode     换电柜编号（如有门店ID则必填）
     * @param string cabinetId  主板编号（如有门店ID则必填）
     * @param int    reset      重置换电柜 参数只能为 1（二选一）
     * @return mixed
     */
    public function StoreAction()
    {
        $request   = $this->request->getJsonRawBody();
        $storeId   = isset($request->storeId) ? $request->storeId : 0;
        $qrcode    = isset($request->qrcode) ? $request->qrcode : '';
        $cabinetId = isset($request->cabinetId) ? $request->cabinetId : '';
        $reset     = isset($request->reset) ? $request->reset : 0;

        // 如果门店ID，换电柜编号，主板编号有效则处理门店绑定的请求
        if ($storeId > 0 && !empty($qrcode) && !empty($cabinetId)) {
            $params = [
                'code' => 30002,
                'parameter' => [
                    'cabinetId' => $cabinetId,
                    'qrcode'    => $qrcode,
                    'storeId'   => $storeId
                ]
            ];

            // 请求微服务修改换电柜绑定的门店
            $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");

            // 判断状态，返回结果
            if ($result['statusCode'] == 200) {
                return $this->toSuccess();
            } else {
                return $this->toError(500, '门店绑定失败');
            }
        }

        // 如果reset值为1，处理初始化该换电柜的请求
        if ($reset == 1 && !empty($qrcode)) {
            // 设置初始化数据
            $rooms = [];
            for ($i = 0; $i < 6; $i++) {
                $rooms[$i]['roomNum']       = $i + 1;            // 柜门编号从1-6
                $rooms[$i]['doorStatus']    = 1;                 // 柜门状态都为关门状态（1）
                $rooms[$i]['batteryStatus'] = 0;                 // 电池状态都为充满状态（0）
                $rooms[$i]['batteryId']     = '';                // 电池ID默认为空
                $rooms[$i]['batteryEnergy'] = 0;                 // 电池电量默认为0
                $rooms[$i]['isEmpty']       = ($i == 0) ? 1 : 0; // 默认1号柜子为空柜（1），其它都为有电池（0）
                $rooms[$i]['putTime']       = 0;                 // 放入电池时间为0
                $rooms[$i]['getTime']       = 0;                 // 取出电池时间为0
                $rooms[$i]['openTime']      = 0;                 // 最后一次开门时间为0
                $rooms[$i]['closeTime']     = 0;                 // 最后一次关门时间为0
            }

            $params = [
                'code' => 30005,
                'parameter' => [
                    'qrcode' => $qrcode,
                    'data'   => $rooms
                ]
            ];

            // 请求微服务重置换电柜的所有柜子状态
            $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");

            // 判断状态，返回结果
            if ($result['statusCode'] == 200) {
                return $this->toSuccess();
            } else {
                return $this->toError(500, '换电柜重置失败');
            }
        }

        return $this->toError(500, '未匹配请求参数');

    }

    /**
     * 辅助方法：获取所有门店信息
     * @return mixed store 门店信息数组
     */
    private function allStore()
    {
        // 调用微服务接口获取所有的门店信息
        $params = [
            'code' => 10057,
            'parameter' => [
                '123123' => '123123'
            ]
        ];

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == 200 && $result['content']['stores']) {
            $stores = [];

            foreach ($result['content']['stores'] as $key => $value) {
                $stores[$value['id']]['id'] = $value['id'];
                $stores[$value['id']]['storeName'] = $value['storeName'];
                $stores[$value['id']]['address']   = $value['address'];
                $stores[$value['id']]['linkPhone']   = $value['linkPhone'];
                $stores[$value['id']]['linkMan']   = $value['linkMan'];
            }
            return $stores;
        } else {
            return $this->toError(500, '获取门店信息失败');
        }

    }


    /**
     * 辅助方法：获取选定区域下的门店信息
     * @return mixed store 门店信息数组
     */
    private function storeAreaSearch($areaId, $areaDeep)
    {
        // 调用微服务接口获取所有的门店信息
        $params['code'] = 10057;

        if ($areaDeep == 1) {
            $params['parameter']['provinceId'] = $areaId;
        } else {
            $params['parameter']['cityId'] = $areaId;
        }

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if ($result['statusCode'] == 200 && isset($result['content']['stores'][0])) {
            $stores = [];

            foreach ($result['content']['stores'] as $key => $value) {
                $stores[] = $value['id'];
            }
            return $stores;
        } else {
            return [];
        }

    }


    /**
     * 辅助方法：获取骑手ID集合的对应骑手信息
     * @return mixed
     */
    private function getDriversInfo($driversId)
    {
        // 调用微服务接口获取所有的门店信息
        $params = [
            'code' => 60014,
            'parameter' => [
                'idList' => $driversId
            ]
        ];

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->dispatch, $params, "post");

        if ($result['statusCode'] == 200 && isset($result['content']['driversDOS'][0])) {
            $list = $result['content']['driversDOS'];
            $drivers = [];
            foreach ($list as $key => $value) {
                $drivers[$value['id']]['id']       = $value['id'];
                $drivers[$value['id']]['userName'] = $value['userName'];
                $drivers[$value['id']]['realName'] = $value['realName'];
                $drivers[$value['id']]['roleId']   = $value['roleId'];
                $drivers[$value['id']]['phone']    = $value['phone'];
                $drivers[$value['id']]['jobNo']    = $value['jobNo'];
            }
            return $drivers;
        } else {
            $this->logger->error('获取门店信息失败'.json_encode($result,JSON_UNESCAPED_UNICODE));
            return null;
        }
    }

    /**
     * 辅助方法：通过门店名称获取门店信息
     * @param string storeId 门店名称
     * @return mixed store 门店信息
     */
    private function searchStore($storeName)
    {
        // 调用微服务接口获取所有的门店信息
        $params = [
            'code' => 10057,
            'parameter' => [
                'storeName' => $storeName,
                'pageSize'  => 50,
                'pageNum'   => 1,
            ]
        ];

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");

        if ($result['statusCode'] == 200 && $result['content']['stores']) {
            $stores = [];
            foreach ($result['content']['stores'] as $key => $value) {
                $stores[$value['id']]['storeName'] = $value['storeName'];
                $stores[$value['id']]['address']   = $value['address'];
                $stores[$value['id']]['linkPhone']   = $value['linkPhone'];
                $stores[$value['id']]['linkMan']   = $value['linkMan'];
            }
            return $stores;
        } else {
            $this->logger->error('获取门店信息失败'.json_encode($result,JSON_UNESCAPED_UNICODE));
            return null;
        }

    }

    /**
     * 辅助方法：模糊搜索门店信息
     * @param string storeName 门店名称
     * @return mixed
     */
    public function storeSearchAction()
    {
        $request = $this->request->get();
        $storeName = isset($request['storeName']) ? $request['storeName'] : '';
        $areaId    = isset($request['areaId']) ? $request['areaId'] : '';
        $areaDeep = isset($request['areaDeep']) ? $request['areaDeep'] : '';
        if (empty($storeName)) {
            return $this->toError(500, '门店名称不能为空');
        }

        // 调用微服务接口获取所有的门店信息
        $params['code'] = 10057;
        $params['parameter']['storeName'] = $storeName;
        $params['parameter']['pageSize'] = 50;
        $params['parameter']['pageNum'] = 1;

        if (!empty($areaId) && !empty($areaDeep)) {
            switch ($areaDeep) {
                case 1:
                    $params['parameter']['provinceId'] = $areaId;
                    break;
                case 2:
                    $params['parameter']['cityId'] = $areaId;
                    break;
                default:
                    $params['parameter']['areaId'] = $areaId;
            }
        }

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        // 异常返回
        if (200 != $result['statusCode']){
            return $this->toError(500, '获取门店信息失败');
        }

        $stores = [];
        foreach ($result['content']['stores'] as $key => $value) {
            $stores[$value['id']]['id'] = $value['id'];
            $stores[$value['id']]['storeName']   = $value['storeName'];
        }
        return $this->toSuccess($stores);

    }


    /**
     * 辅助方法：模糊搜索骑手信息
     * @param string driverName 骑手姓名
     * @return mixed
     */
    public function driverSearchAction()
    {
        $request = $this->request->get();
        $driverName = isset($request['driverName']) ? $request['driverName'] : '';

        if (empty($driverName)) {
            return $this->toError(500, '骑手名称不能为空');
        }

        // 调用微服务接口获取所有的门店信息
        $params = [
            'code' => 60014,
            'parameter' => [
                'realName' => $driverName,
                'pageSize'  => 50,
                'pageNum'   => 1,
            ]
        ];

        // 判断状态，返回结果
        $result = $this->curl->httpRequest($this->Zuul->dispatch, $params, "post");

        if ($result['statusCode'] == 200 && isset($result['content']['driversDOS'][0])) {
            $stores = [];
            foreach ($result['content']['driversDOS'] as $key => $value) {
                $stores[$value['id']]['id'] = $value['id'];
                $stores[$value['id']]['userName']   = $value['userName'];
                $stores[$value['id']]['realName']   = $value['realName'];
            }
            return $this->toSuccess($stores);
        } else {
            return $this->toSuccess();
        }

    }

    /**
     * 辅助方法：模糊搜索区域信息
     * @param string areaName 区域名称
     * @return mixed
     */
    public function areaSearchAction()
    {
        $request = $this->request->get();
        $areaName = isset($request['areaName']) ? $request['areaName'] : '';

        $params = [
            'code' => 10022,
            'parameter' => [
                'areaName'   => $areaName,
                'fuzzySearch' => 1
            ]
        ];

        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");
        if ($result['statusCode'] == 200 && isset($result['content']['data'][0])) {
            $data = [];
            foreach ($result['content']['data'] as $key => $value) {
                if ($value['areaDeep'] <= 2) {
                    $data[$key]['areaId']   = $value['areaId'];
                    $data[$key]['areaName'] = $value['areaName'];
                    $data[$key]['areaDeep'] = $value['areaDeep'];
                }
            }
            return $this->toSuccess($data);
        } else {
            return $this->toSuccess();
        }

    }

    /**
     * 辅助方法：通过换电柜编号获取换电柜ID
     * @param string areaName 区域名称
     * @return mixed
     */
    public function searchCabinetId($qrcode)
    {
        $cabinetId = 0;
        $params = [
            'code' => 30004,
            'parameter' => [
                'qrcode'   => $qrcode
            ]
        ];

        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");

        if ($result['statusCode'] == 200 && isset($result['content']['cabinetList'][0])) {
            $cabinetId = isset($result['content']['cabinetList'][0]['id']) ? $result['content']['cabinetList'][0]['id'] : 0;
            return $cabinetId;
        } else {
            return $cabinetId;
        }

    }

    /**
     * 辅助方法：通过换电柜ID获取换换电柜编号
     * @param string areaName 区域名称
     * @return mixed
     */
    public function searchCabinetQrcode($cabinetId)
    {
        $qrcode = '';
        $params = [
            'code' => 30004,
            'parameter' => [
                'id'   => $cabinetId
            ]
        ];

        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "POST");

        if ($result['statusCode'] == 200 && isset($result['content']['cabinetList'][0])) {
            $qrcode = isset($result['content']['cabinetList'][0]['id']) ? $result['content']['cabinetList'][0]['qrcode'] : '';
            return $qrcode;
        } else {
            return $qrcode;
        }

    }

    /**
     * 设置默认柜子
     * @param string cabinetId
     * @param int roomNum
     * @return mixed
     */
    public function setEmptyAction()
    {
        $request = $this->request->getJsonRawBody();
        $cabinetId = isset($request->cabinetId) ? $request->cabinetId : null;
        $roomNum = isset($request->roomNum) ? $request->roomNum : 0;

        if (empty($cabinetId) || $roomNum == 0) {
            return $this->toError(500, '参数缺失');
        }

        $params = [
            'code' => 10003,
            'parameter' => [
                'cabinetId' => $cabinetId,
                'roomNum' => $roomNum
            ]
        ];
        $result = $this->curl->httpRequest($this->Zuul->charging, $params, "post");

        if ($result['statusCode'] ==200) {
            return $this->toSuccess('更换成功');
        } else {
            return $this->toError(500, '更换失败');
        }
    }
}
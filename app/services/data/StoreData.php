<?php
namespace app\services\data;

use app\common\errors\DataException;
use app\models\service\StoreVehicle;
use app\modules\BaseController;

class StoreData extends BaseData
{
    const RepairService = 1; // 维修服务
    const RentService = 2; // 租赁服务
    const BatteryService = 3; // 充换电服务
    const LocationService = 4; // 定位设备安装服务

    const NotRented = 1; // 未出租
    const Rented = 2; // 已出租
    const Recover = 3; // 未还车

    public static $types = [2 => 'rent',3 => 'battery',1 => 'repair',4 => 'location'];

    // 定义APP门店地图侧边栏内容模版
    public $StoreBar = [
        'rent' => [
            'type' => StoreData::RentService,
            'name' => '租车',
            'objName' => 'RentPoint',
            'action' => true,
        ],
        'battery' => [
            'type' => StoreData::BatteryService,
            'name' => '充电',
            'objName' => 'BatteryPoint',
            'action' => false,
        ],
        'repair' => [
            'type' => StoreData::RepairService,
            'name' => '维修',
            'objName' => 'RepairPoint',
            'action' => false,
        ],
        'location' => [
            'type' => StoreData::LocationService,
            'name' => '定位设备安装',
            'objName' => 'LocationPoint',
            'action' => false,
        ],
    ];

    // 获取多条门店信息 通过idlist
    public function getStoreByIds($ids, $Convert=false)
    {
        // 去除0值、异常值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0,'',null])));
        if (empty($ids)){
            return [];
        }
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => 10079,
            'parameter' => [
                'idList' => $ids,
            ]
        ],"post");
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            throw new DataException([500, '服务异常']);
        }
        $stores = $result['content']['stores'];
        if ($Convert){
            $tmp = [];
            foreach ($stores as $store){
                $tmp[(string)$store['id']] = $store;
            }
            $stores = $tmp;
        }
        return $stores;
    }

    // 获取单条门店信息 通过id
    public function getStoreById($id)
    {
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => 10057,
            'parameter' => [
                'id' => $id,
            ]
        ],"post");
        if (!isset($result['statusCode']) || $result['statusCode'] != '200' || !isset($result['content']['stores'][0])) {
            throw new DataException([500, '未获取到门店信息']);
        }
        $store = $result['content']['stores'][0];
        return $store;
    }

    /**
     * 获取门店指定租赁状态车辆数量 通过门店idlist
     * @param $ids 门店idlist
     * @param null $type 租赁状态 null为全部
     * @return array
     */
    public function getVehicleNumByStoreIds($ids, $type=null)
    {
        if(empty($ids)){
            return [];
        }
        if(is_null($type)){
            $conditions = 'store_id IN ({storeIdList:array})';
        }else{
            $conditions = 'rent_status = '.$type.' and store_id IN ({storeIdList:array})';
        }
        // 查询门店可用车辆
        $storeUseVehicle = [];
        $StoreVehicleS = StoreVehicle::find([
            'columns' => 'store_id, COUNT(id) AS count',
            'conditions' => $conditions,
            'bind' => [
                'storeIdList' => $ids,
            ],
            'group' => 'store_id',
        ]);
        foreach ($StoreVehicleS as $StoreVehicle){
            $storeUseVehicle[$StoreVehicle->store_id] = $StoreVehicle->count;
        }
        return $storeUseVehicle;
    }

    // APP门店地图获取
    public function getAPPStoreMap($condition, $EnableBar=true, $rentAPP=false, $needTypeNum=true)
    {
        // 可用条件字段
        $fields = ['cityId', 'lng', 'lat', 'storeName','parentOperatorInsId','insUserType'];
        $par = [];
        foreach ($fields as $field){
            if (!empty($condition[$field])){
                $par[$field] = $condition[$field];
            }
        }
        if (empty($par)){
            throw new DataException([500, '检索条件不可为空']);
        }
        // 状态： 1启用 2禁用
        $par['userStatus'] = 1;
        // 没传城市，默认距离 KM
        if (!isset($par['cityId']) && !isset($par['distance'])){
            $par['distance'] = 10;
        }
        //调用微服务接口查询
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10055',
            'parameter' => $par
        ],"post");
        if (200!=$result['statusCode']){
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        $storeList = $result['content']['stores'] ?? [];
        return $this->ProcessStoreListToAppDataStructure($storeList, $condition, $EnableBar, $rentAPP, $needTypeNum);
    }

    // 处理门店列表到APP数据结构
    private function ProcessStoreListToAppDataStructure($storeList, $condition, $EnableBar, $rentAPP=false, $needTypeNum=true)
    {
        // 门店idList
        $storeIdList = array_map(function ($v){
            return $v['id'];
        }, $storeList);
        // 定义侧边栏内容模版
        $bar = $this->StoreBar;
        // 租赁APP不看定位设备
        if ($rentAPP){
            unset($bar['location']);
        }
        // 过滤需求门店类型
        $needTypeValues = [];
        if (isset($condition['types'])){
            foreach ($condition['types'] as $type){
                if (!isset($bar[$type])) continue;
                $needTypeValues[] = $bar[$type]['type'];
            }
            foreach ($bar as $k => $v){
                if (!in_array($k, $condition['types'])) unset($bar[$k]);
            }
        }

        /**
         * 如果没有active true 则 默认第一个为true
         */
        if (count($bar) == 1) {
            if (isset($bar['rent'])) {
                $bar['rent']['action'] = true;
            } else if (isset($bar['battery'])) {
                $bar['battery']['action'] = true;
            } else if (isset($bar['repair'])) {
                $bar['repair']['action'] = true;
            } else if (isset($bar['location'])) {
                $bar['location']['action'] = true;
            }
        }


        // 是否需要展示服务类型的商品数量
        if ($needTypeNum){
            // 查询门店可用车辆
            if (isset($bar['rent'])){
                $storeUseVehicle = $this->getVehicleNumByStoreIds($storeIdList, StoreData::NotRented);
            }
            // 查询门店满电柜数量
            if (isset($bar['battery'])){
                $storeFullBattery = $this->getFullBatteryNumByStoreIds($storeIdList);
            }
        }
        // 给APP数组
        $bar = array_values($bar);
        $list = [];
        // 定义返回数据结构,根据是否启用bar有2种【为了便于多处return使用了引用，修改时注意】
        if ($EnableBar){
            $data = [
                'bar' => $bar,
                'list' => &$list,
            ];
            // 为侧边栏属性创建空数组
            foreach ($bar as $value){
                $list[$value['objName']] = [];
            }
        }else{
            $data = &$list;
        }
        // 如果没有可用数据
        if (empty($storeList) || empty($bar)){
            return $data;
        }
        // 门店经营类型 从bar中动态获取可用范围
        $businessScopeMap = [];
        foreach ($bar as $item){
            $businessScopeMap[(string)$item['type']] = $item['name'];
        }
        // 定义字段
        $fields = ['id','storeName','address','lng','lat','imgUrl','linkMan','linkPhone','linkTel','storeType','startAt','endAt','phone'];
        // 处理门店数据
        foreach ($storeList as $value){
            // 如果门店没有类型 过
            if (!isset($value['storeType'])) continue;
            // 如果过滤类型 && 门店无所需类型
            if ($needTypeValues && empty(array_intersect($needTypeValues,$value['storeType']))) continue;
            $tmp = [];
            // 存储预定义字段
            foreach ($fields as $field){
                $tmp[$field] = $value[$field];
            }
            // 经营时间暂无
            $tmp['bussinessHours'] = $tmp['startAt'] . ' - ' . $tmp['endAt'];

            // 处理门店经营范围 及 换电租车信息
            $ls = [];
            foreach ($value['storeType'] as $j) {
                if (isset($businessScopeMap[(string)$j])){
                    $ls[] = $businessScopeMap[(string)$j];
                }
            }
            // 经营范围描述
            $tmp['businessScope'] = implode(',', $ls);
            // 是否需要展示服务类型的商品数量
            if ($needTypeNum){
                // 维修服务
                if (in_array(StoreData::RepairService, $value['storeType'])){
                    $tmp['repair'] = 0; // 兼容APP
                }
                // 租赁服务
                if (isset($storeUseVehicle) && in_array(StoreData::RentService, $value['storeType'])){
                    $tmp['vehicle'] = $storeUseVehicle[$value['id']] ?? 0;
                }
                // 换电服务
                if (isset($storeFullBattery) && in_array(StoreData::BatteryService, $value['storeType'])){
                    $tmp['battery'] = $storeFullBattery[$value['id']] ?? 0;
                }
            }
            if ($EnableBar){
                // 放到对应门店对象
                foreach ($bar as $item){
                    if (in_array($item['type'], $value['storeType'])){
                        $list[$item['objName']][] = $tmp;
                    }
                }
            }else{
                $list[]  = $tmp;
            }
        }
        return $data;
    }



    // 获取门店可用电池数量
    public function getFullBatteryNumByStoreIds($storeIdList)
    {
        //调用微服务接口查询
        $result = $this->curl->httpRequest($this->Zuul->charging,[
            'code' => '32826',
            'parameter' => [
                'storeIdList' => $storeIdList
            ]
        ],"post");
        if (200 != $result['statusCode']){
            throw new DataException([500,$result['msg']]);
        }
        // TODO:兼容二期上线前的一期接口数据格式
        $list = $result['content']['data'] ??
            array_merge($result['content']['new'] ?? [], $result['content']['old'] ?? []);
        $data = [];
        foreach ($list as $item){
            $data[$item['storeId']] = $item['maxNum'] + ($data[$item['storeId']] ?? 0);
        }
        return $data;
    }
}

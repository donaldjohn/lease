<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\order\ProductPackageRelation;
use app\models\order\ServicePackageRelation;


class PackageData extends BaseData
{
    private $ServicesOfPackage;
    // 租赁服务类型
    public $RentServiceType = 1;
    // 换电服务类型
    public $ChangeBatteryServiceType = 2;
    // 联保服务类型
    public $WarrantyServiceType = 3;
    // 套餐查询缓存
    private $PackagCache = [];
    // 租赁价格缓存【车辆、电池】
    private $RentPrice = [];
    // 商品整车类别
    const VehicleCategoryType = 1;
    // 商品电池类别
    const BatteryCategoryType = 2;

    // 套餐车辆商品类型
    const ProductTypeVehicle = 1;
    // 套餐电池商品类型
    const ProductTypeBattery = 2;

    // 默认价格字段集
    public $PriceFields = ['packageDeposit', 'packageRent', 'vehicleRent', 'batteryRent', 'productRent', 'totalAmount', 'unPayAmount', 'payAmount', 'amount'];

    // 获取单条套餐详情
    public function getPackageById($id, $cache=true)
    {
        // 是否使用缓存
        if ($cache && isset($this->PackagCache[$id])){
            return $this->PackagCache[$id];
        }
        $packages = $this->getPackageByIds([(int)$id]);
        $package = $packages[0] ?? null;
        if (is_null($package))
            throw new DataException([500, "未查询到套餐信息"]);
        // 存入缓存
        $this->PackagCache[$id] = $package;
        return $package;
    }

    /**获取多条套餐详情 通过idlist
     * @param $ids 套餐idlist
     * @param bool $convert 是否转换为id关系数组
     * @return array 套餐列表
     * @throws DataException 获取数据有误
     */
    public function getPackageByIds($ids, $convert=false)
    {
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0,'',null])));
        if (0==count($ids)){
            return [];
        }
        // 获取套餐详情列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10008",
            'parameter' => [
                'packageIds' => $ids,
            ]
        ],"post");
        // 结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        $packages = $result['content']['productPackageDetails'];
        if ($convert){
            $tmp = [];
            foreach ($packages as $package){
                $tmp[(string)$package['productPackage']['packageId']] = $package;
            }
            $packages = $tmp;
        }
        return $packages;
    }

    /**判断套餐是否包含指定服务
     * 使用示例：
     * $this->PackageData->isContainServiceByPackageId($this->PackageData->RentServiceType, 16);
     * @param $ServiceType 服务类型，类中已定义属性
     * @param $PackageId 套餐ID
     * @return bool 是否包含指定服务
     * @throws DataException 套餐查询出错
     */
    public function isContainServiceByPackageId($ServiceType, $PackageId)
    {
        // 如果内存中没有，查询
        if (!isset($this->ServicesOfPackage[(string)$PackageId])){
            // 查询套餐信息
            $package = $this->getPackageById($PackageId);
            $tmp = [];
            foreach ($package['serviceItems'] as $item){
                $tmp[] = $item['serviceItemType'];
            }
            // 缓存结果，以便生命周期内对套餐多次服务判断
            $this->ServicesOfPackage[(string)$PackageId] = $tmp;
        }
        // 返回结果
        return in_array($ServiceType, $this->ServicesOfPackage[(string)$PackageId]);
    }

    /**
     * 判断套餐是否在有效期
     * @param $PackageId 套餐ID
     * @return bool
     * @throws DataException
     */
    public function isValidityPeriodByPackageId($PackageId)
    {
        // 查询套餐信息
        $package = $this->getPackageById($PackageId);
        $startTime = $package['productPackage']['startTime'];
        $endTime = $package['productPackage']['endTime'];
        $time = time();
        if ($time>$startTime && $time<$endTime){
            return true;
        }
        return false;
    }

    /**
     * 获取套餐中车辆租赁价格
     * @param $packagId 套餐id
     * @return mixed 车辆租金
     * @throws DataException
     */
    public function getRentVehiclePriceByPackagId($packagId)
    {
        if (!isset($this->RentPrice[$packagId])){
            $this->getRentPrice($packagId);
        }
        return $this->RentPrice[$packagId]['vehicleRent'];
    }

    /**
     * 获取套餐中车辆租赁价格
     * @param $packagId 套餐id
     * @return mixed 电池租金
     * @throws DataException
     */
    public function getRentBatteryPriceByPackagId($packagId)
    {
        if (!isset($this->RentPrice[$packagId])){
            $this->getRentPrice($packagId);
        }
        return $this->RentPrice[$packagId]['batteryRent'];
    }
    /**
     * 获取套餐租赁价格
     * @param $packagId 套餐id
     * @return mixed 租赁明细数组
     * @throws DataException
     */
    /*private function getRentPrice($packagId)
    {
        // 获取套餐信息
        $Package = $this->getPackageById($packagId);
        // 套餐sku集合
        $skuids = [];
        $productSkus = [];
        foreach ($Package['productSkus'] as $productSku){
            $productSkus[(string)$productSku['productSkuRelationId']] = $productSku;
            $skuids[] = $productSku['productSkuRelationId'];
        }
        // 查询套餐商品中为整车的sku
        $result = $this->curl->httpRequest($this->Zuul->product,[
            'code' => "10012",
            'parameter' => [
                'productSkuRelationIds' => $skuids,
                // 类型 1 整车 2 电池 3 配件 4 其它
                'type' => 1,
            ]
        ],"post");
        // 失败 返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'车辆查询失败');
        }
        // 整车租金
        $vehicleRent = 0;
        foreach ($result['content']['productSkuRelationIds'] as $skuId){
            $vehicleRent += $productSkus[(string)$skuId]['productRent'];
        }
        // 处理车辆租赁金额
        $vehicleRent = round($vehicleRent/10000, 2);
        // 查询套餐商品中为电池的sku
        $result = $this->curl->httpRequest($this->Zuul->product,[
            'code' => "10012",
            'parameter' => [
                'productSkuRelationIds' => $skuids,
                // 类型 1 整车 2 电池 3 配件 4 其它
                'type' => 2,
            ]
        ],"post");
        // 失败 返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'电池查询失败');
        }
        // 电池租金
        $batteryRent = 0;
        foreach ($result['content']['productSkuRelationIds'] as $skuId){
            $batteryRent += $productSkus[(string)$skuId]['productRent'];
        }
        // 处理电池租赁金额
        $batteryRent = round($batteryRent/10000, 2);
        $this->RentPrice[$packagId] = [
            'vehicleRent' => $vehicleRent,
            'batteryRent' => $batteryRent,
        ];
        return $this->RentPrice[$packagId];
    }*/

    // 获取套餐列表的电池车辆租金
    /*public function getRentPriceInfoByPackagIds($packagIds)
    {
        if (empty($packagIds)){
            return [];
        }
        $PackageSKUs = ProductPackageRelation::find([
            'package_id IN ({packagIds:array})',
            'bind' => [
                'packagIds' => $packagIds
            ]
        ])->toArray();
        // 获取skuids
        $SkuIds =[];
        foreach ($PackageSKUs as $PackageSKU){
            $SkuIds[] = $PackageSKU['product_sku_relation_id'];
        }
        $SkuType = $this->getVehicleBatteryBySkuIds($SkuIds);
        // 计算套餐的租金信息
        $PackagPriceInfo = [];
        foreach ($PackageSKUs as $PackageSKU){
            $skuid = $PackageSKU['product_sku_relation_id'];
            // 没有sku信息，过
            if (!isset($SkuType[$skuid])) continue;
            $packagId = $PackageSKU['package_id'];
            // 初始化数组
            if (!isset($PackagPriceInfo[$packagId])){
                $PackagPriceInfo[$packagId] = [];
            }
            // 初始金额
            if (!isset($PackagPriceInfo[$packagId][$SkuType[$skuid]])){
                $PackagPriceInfo[$packagId][$SkuType[$skuid]] = 0;
            }
            $PackagPriceInfo[$packagId][$SkuType[$skuid]] += round($PackageSKU['product_rent'] / 10000, 2);
        }
        return $PackagPriceInfo;
    }*/

    // 获取SKUList中属于车辆电池的SKU
    /*public function getVehicleBatteryBySkuIds($SkuIds)
    {
        $SkuType = [];
        if (empty($SkuIds)){
            return $SkuType;
        }
        $SKUs =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\product\ProductSkuRelation','sr')
            ->where('sr.id IN ({SkuIds:array})', ['SkuIds' => $SkuIds])
            ->join('app\models\product\Product', 'p.product_id = sr.product_id', 'p')
            ->join('app\models\product\ProductCategory', 'c.id = p.category_id', 'c')
            ->andWhere('c.type IN ({types:array})', ['types'=>[self::VehicleCategoryType, self::BatteryCategoryType]])
            ->columns('sr.id AS skuId,  c.type')
            ->getQuery()
            ->execute()
            ->toArray();
        foreach ($SKUs as $sku){
            $SkuType[$sku['skuId']] = $sku['type'];
        }
        return $SkuType;
    }*/

    // 获取我的套餐(服务单)列表
    public function getMyServiceContractList($driverId, $status,$parentOperatorInsId)
    {
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10046,
            'parameter' => [
                'driverId' => $driverId,
                'status' => $status,
                'parentOperatorInsId' => $parentOperatorInsId
            ]
        ],"post");
        if (200 != $result['statusCode']){
            return $this->toError($result['statusCode'], $result['msg']);
        }
        $serviceList = $result['content']['data'];
        // 处理基础价格
        $priceFields = ['packageDeposit', 'packageRent', 'productRent', 'totalAmount', 'unPayAmount', 'payAmount'];
        $this->HandlePrice($serviceList, $priceFields, true);
        // 车辆/电池
        $serviceProductIds = [];
        $productIds = [];
        foreach ($serviceList as $service){
            // 初始化套餐商品关系
            $serviceProductIds[$service['id']] = [
                self::ProductTypeVehicle => [],
                self::ProductTypeBattery => [],
            ];
            if (empty($service['productPackageRelationList'])){
                continue;
            }
            foreach ($service['productPackageRelationList'] as $productPackageRelation) {
                $productType = $productPackageRelation['productType'];
                $productId = $productPackageRelation['productId'];
                // 维护待查商品列表
                if (!isset($productIds[$productType])){
                    $productIds[$productType] = [];
                }
                $productIds[$productType][] = $productId;
                // 维护套餐商品关系
                if (!isset($serviceProductIds[$service['id']][$productType])){
                    $serviceProductIds[$service['id']][$productType] = [];
                }
                $serviceProductIds[$service['id']][$productType][] = $productId;
            }
        }
        $vehicleIds = $productIds[self::ProductTypeVehicle] ?? [];
        $batteryIds = $productIds[self::ProductTypeBattery] ?? [];
        // 查询车辆
        $productData = new ProductData();
        $vehicleModelDetails = $productData->getVehicleProductsByVehicleModelIds($vehicleIds);
        // 查询电池
        $batteryModelDetails = $productData->getBatteryProductsByBatteryModelIds($batteryIds);
        // 存入车辆电池名称
        foreach ($serviceList as $k => $service){
            $vehicleNames = [];
            $batteryNames = [];
            foreach ($serviceProductIds[$service['id']][self::ProductTypeVehicle] as $productId){
                if (!empty($vehicleModelDetails[$productId])){
                    $vehicleNames[] = $vehicleModelDetails[$productId]['vehicleName'];
                }
            }
            foreach ($serviceProductIds[$service['id']][self::ProductTypeBattery] as $productId){
                if (!empty($batteryModelDetails[$productId])){
                    $batteryNames[] = $batteryModelDetails[$productId]['batteryName'];
                }
            }
            $serviceList[$k]['vehicleName'] = implode('、', $vehicleNames);
            $serviceList[$k]['batteryName'] = implode('、', $batteryNames);
        }
        return $serviceList;
    }

    /**
     * 处理价格 除以10000取2位小数
     * @param $arr 待处理数组
     * @param $fields 待处理字段
     * @param bool $recursive 是否递归
     * @return array
     */
    public function HandlePrice(&$arr, $fields=null, $recursive=true){
        if (is_null($fields)){
            $fields = $this->PriceFields;
        }
        if (!is_array($arr)){
            return $arr;
        }
        foreach ($arr as $k => $value){
            if (in_array($k, $fields, true) && is_numeric($value)){
                $arr[$k] = (string) round($value/10000, 2);
            }
            if ($recursive && is_array($value)){
                $this->HandlePrice($arr[$k], $fields, $recursive);
            }
        }
        return $arr;
    }

    // 查询套餐是否被使用过
    public function isUsed($packageId)
    {
        $hasUsed = ServicePackageRelation::arrFindFirst([
            'package_id' => $packageId
        ]);
        return $hasUsed ? true : false;
    }

    // 查询已被使用的套餐id
    public function getUsedPackageIds($packageIds)
    {
        $usedPackageIds = [];
        if (empty($packageIds)){
            return $usedPackageIds;
        }
        $SPRs = ServicePackageRelation::arrFind([
            'package_id' => ['IN', $packageIds]
        ]);
        foreach ($SPRs AS $SPR){
            $usedPackageIds[] = $SPR->package_id;
        }
        return $usedPackageIds;
    }

    // 获取套餐包含商品信息,不处理金额
    public function getPackagesAndProductInfoByPackageIds($packageIds)
    {
        $packages = $this->getPackageByIds($packageIds);
        // 车辆/电池
        $packageProductIds = [];
        $productIds = [];
        $packageProductRelations = [];
        foreach ($packages as $k => $package){
            // 初始化套餐商品关系
            $packageProductIds[$package['packageId']] = [
                self::ProductTypeVehicle => [],
                self::ProductTypeBattery => [],
            ];
            if (empty($package['productPackageRelationList'])){
                continue;
            }
            foreach ($package['productPackageRelationList'] as $productPackageRelation) {
                $productType = $productPackageRelation['productType'];
                $productId = $productPackageRelation['productId'];
                // 维护类型-待查商品列表
                if (!isset($productIds[$productType])){
                    $productIds[$productType] = [];
                }
                $productIds[$productType][] = $productId;
                // 维护套餐-类型-商品关系
                if (!isset($packageProductIds[$package['packageId']][$productType])){
                    $packageProductIds[$package['packageId']][$productType] = [];
                }
                $packageProductIds[$package['packageId']][$productType][] = $productId;
                // 维护套餐-类型-【商品套餐配置】
                if (!isset($packageProductRelations[$productType])){
                    $packageProductRelations[$productType] = [];
                }
                $packageProductRelations[$productType][$productId] = $productPackageRelation;
            }
            unset($packages[$k]['productPackageRelationList']);
        }
        $vehicleIds = $productIds[self::ProductTypeVehicle] ?? [];
        $batteryIds = $productIds[self::ProductTypeBattery] ?? [];
        // 查询车辆
        $productData = new ProductData();
        $products[self::ProductTypeVehicle] = $productData->getVehicleProductsByVehicleModelIds($vehicleIds);
        // 查询电池
        $products[self::ProductTypeBattery] = $productData->getBatteryProductsByBatteryModelIds($batteryIds);
        $productFields = [
            'vehicleList' => self::ProductTypeVehicle,
            'batteryList' => self::ProductTypeBattery,
        ];
        // 遍历套餐
        foreach ($packages as $k => $package){
            // 遍历商品类型
            foreach ($productFields as $field => $type){
                $package[$field] = [];
                // 遍历类型插入商品到套餐
                foreach ($packageProductIds[$package['packageId']][$type] as $productId){
                    $product = $products[$type][$productId];
                    $product['productPackageRelation'] = $packageProductRelations[$type][$productId];
                    $package[$field][] = $product;
                }
            }
            $packages[$k] = $package;
        }
        return $packages;
    }
}

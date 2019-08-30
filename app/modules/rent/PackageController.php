<?php
namespace app\modules\rent;


use app\common\errors\AppException;
use app\models\order\ServicePackageRelation;
use app\models\service\Area;
use app\models\service\AreaPowerExchangePrice;
use app\modules\BaseController;
use app\services\data\PackageData;
use app\services\data\ProductData;

//套餐模块
class PackageController extends BaseController
{
    /**
     * 新增套餐
     */
    public function CreateAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'productPackage' => '未收到商品套餐数据',
//            'serviceItemIds' => '请选择服务项目',
            'serviceItems' => '请选择服务项目',
            'servicePrice' => 0,
            'areas' => '请选择区域',
            'vehicleList' => [
                'def' => [],
            ],
            'batteryList' => [
                'def' => [],
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 定义商品套餐对象字段
        $fields = [
            'productPackageName' => '请填写套餐名称',
            'productPackageCode' => '请填写套餐编码',
            'packageDeposit' => [
                'need' => '请填写套餐押金',
                'name' => '押金',
                'min' => 0.01,
            ],
            'status' => '请选择套餐状态',
            'startTime' => '请选择套餐生效日期',
            'endTime' => '请选择套餐截止日期',
            'imgUrl' => '请上传套餐图片',
            'packageDescribe' => [
                'name' => '描述',
                'maxl' => 200,
            ],
            'rentPeriod' => [
                'def' => 30,
            ],
         //   'maxRenew' => '请填写循环次数'
        ];
        // 过滤商品套餐参数
        $parameter['productPackage'] = $this->getArrPars($fields, $parameter['productPackage']);
        $parameter['productPackage']['operatorInsId'] = $this->authed->insId;
        $parameter['productPackage']['createUserId'] = $this->authed->userId;
        // 处理套餐押金
        $parameter['productPackage']['packageDeposit'] = (int) ($parameter['productPackage']['packageDeposit']*10000);
        // 服务项重复判断
        $tmpTypes = [];
        foreach ($parameter['serviceItems'] as $k => $serviceItem){
            if (in_array($serviceItem['serviceItemType'], $tmpTypes)){
                return $this->toError(500, '同一类型服务不可多选');
            }
            $tmpTypes[] = $serviceItem['serviceItemType'];
        }
        // 如果有租赁
        if (in_array(1, $tmpTypes)){
            if (empty($parameter['vehicleList']) || count($parameter['vehicleList'])>1 || 1!=$parameter['vehicleList'][0]['num']){
                return $this->toError(500, '租赁服务需要有且只能有1辆车');
            }
            if (count($parameter['batteryList'])>1){
                return $this->toError(500, '仅可选择一种电池');
            }
        }
        $parentInsId = $this->appData->getParentInsId($this->authed->insId,$this->authed->userType);

        if (!$parentInsId) {
            return $this->toError(500,'当前用户无权限');
        }

        // 校验区域价格设置
        $this->checkAreaPrice($parentInsId,$parameter['areas'], $parameter['productPackage']['packageDeposit'], in_array(2, $tmpTypes));
        $productList = [];
        // 处理商品
        $productFields = [
            'vehicleList' => [
                'productType' => 1,
                'rentToPackage' => 'vehicleRent',
            ],
            'batteryList' => [
                'productType' => 2,
                'rentToPackage' => 'batteryRent',
            ],
        ];
        foreach ($productFields as $productField => $fieldAttributes){
            $productType = $fieldAttributes['productType'];
            $rentToPackage = $fieldAttributes['rentToPackage'];
            if (!isset($parameter['productPackage'][$rentToPackage])){
                $parameter['productPackage'][$rentToPackage] = 0;
            }
            foreach ($parameter[$productField] as $k => $v){
                $v['productRent'] = (int) ($v['productRent']*10000);
                $v['productType'] = $productType;
                $parameter['productPackage'][$rentToPackage] += $v['productRent'];
                $productList[] = $v;
            }
            unset($parameter[$productField]);
        }
        $parameter['productSkuList'] = $productList;
        // 调用微服务接口新增套餐
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10006,
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess($result['content']);
    }

    /**
     * 套餐列表
     */
    public function ListAction()
    {
        $fields = [
            'productPackageCode' => 0,
            'productPackageName' => 0,
            // 区域可传空，不传微服务会报异常
            'areaId' => [
                'def' => '',
            ],
            'areaDeep' => [
                'def' => '',
            ],
            'pageNum' => [
                'def' => 1,
            ],
            'pageSize' => [
                'def' => 20,
            ],
            'operatorInsId' => [
                'def' => null,
            ]
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $_GET);
        if ($this->authed->userType == 9) {
            $parameter['operatorInsId'] = $this->authed->insId;
        }
        // 获取套餐列表
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10007",
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $list = $result['content']['productPackages'];
        $meta = $result['content']['pageInfo'];
        // 查询使用情况
        $packageIds = [];
        foreach ($list as $package) {
            $packageIds[] = $package['packageId'];
        }
        $packageData = new PackageData();
        $usedPackageIds = $packageData->getUsedPackageIds($packageIds);
        foreach ($list as $k => $package){
            $list[$k]['used'] = in_array($package['packageId'], $usedPackageIds);
        }
        // 处理租金押金单位
        $packageData->HandlePrice($list);
        // 成功返回
        return $this->toSuccess($list, $meta);
    }

    // 套餐详情
    public function OneAction($id)
    {
        $packageData = new PackageData();
        $package = $packageData->getPackageById($id);
        // 查询套餐是否被使用过
        $package['used'] = $packageData->isUsed($id);
        // 获取车辆/电池
        $productPackageRelationList = $package['productPackageRelationList'];
        $packageProductIds = [
            PackageData::ProductTypeVehicle => [],
            PackageData::ProductTypeBattery => [],
        ];
        foreach ($productPackageRelationList as $productPackageRelation) {
            $productId = $productPackageRelation['productId'];
            $productType = $productPackageRelation['productType'];
            $packageProductIds[$productType][$productId] = $productPackageRelation;
        }
        unset($package['productPackageRelationList']);
        $productData = new ProductData();
        // 查询车辆商品 array_keys
        $package['vehicleList'] = $productData->getVehicleProductsByVehicleModelIds(array_keys($packageProductIds[PackageData::ProductTypeVehicle]), false);
        // 查询电池商品
        $package['batteryList'] = $productData->getBatteryProductsByBatteryModelIds(array_keys($packageProductIds[PackageData::ProductTypeBattery]), false);
        $productFields = [
            'vehicleList' => PackageData::ProductTypeVehicle,
            'batteryList' => PackageData::ProductTypeBattery,
        ];
        foreach ($productFields as $field => $type){
            foreach ($package[$field] as $k => $item){
                unset($package[$field][$k]['associations']);
                $package[$field][$k]['productPackageRelation'] = $packageProductIds[$type][$item['id']];
            }
        }
        // 处理价格
        $packageData->HandlePrice($package, ['packageDeposit', 'packageRent', 'productRent', 'vehicleRent', 'batteryRent'], true);

        // 处理已选区域树
        // $package['areaTree'] = $this->getRegionedTree($package['areaIds']);
        // 成功返回
        return $this->toSuccess($package);
    }

    // 编辑套餐
    public function UpdateAction($id)
    {
        if ((new PackageData())->isUsed($id)){
            return $this->toError(500, '套餐已被使用，不可编辑');
        }
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'productPackage' => '未收到商品套餐数据',
//            'serviceItemIds' => '请选择服务项目',
            'serviceItems' => '请选择服务项目',
            'servicePrice' => 0,
            'areas' => '请选择区域',
            'vehicleList' => [
                'def' => [],
            ],
            'batteryList' => [
                'def' => [],
            ],
        ];
        // 过滤参数
        $parameter = $this->getArrPars($fields, $request);
        // 定义商品套餐对象字段
        $fields = [
            'productPackageName' => '请填写套餐名称',
            'productPackageCode' => '请填写套餐编码',
            'packageDeposit' => [
                'need' => '请填写套餐押金',
                'name' => '押金',
                'min' => 0.01,
            ],
            'status' => '请选择套餐状态',
            'startTime' => '请选择套餐生效日期',
            'endTime' => '请选择套餐截止日期',
            'imgUrl' => '请上传套餐图片',
            'packageDescribe' => [
                'name' => '描述',
                'maxl' => 200,
            ],
            'rentPeriod' => [
                'def' => 30,
            ],
           // 'maxRenew' => '请填写循环次数'
        ];
        // 过滤商品套餐参数
        $parameter['productPackage'] = $this->getArrPars($fields, $parameter['productPackage']);
        $parameter['productPackage']['packageId'] = $id;
        $parameter['productPackage']['operatorInsId'] = $this->authed->insId;
        // 处理套餐押金
        $parameter['productPackage']['packageDeposit'] = (int) ($parameter['productPackage']['packageDeposit']*10000);
        // 服务项重复判断
        $tmpTypes = [];
        foreach ($parameter['serviceItems'] as $k => $serviceItem){
            if (in_array($serviceItem['serviceItemType'], $tmpTypes)){
                return $this->toError(500, '同一类型服务不可多选');
            }
            $tmpTypes[] = $serviceItem['serviceItemType'];
        }
        // 如果有租赁
        if (in_array(1, $tmpTypes)){
            if (empty($parameter['vehicleList']) || count($parameter['vehicleList'])>1 || 1!=$parameter['vehicleList'][0]['num']){
                return $this->toError(500, '租赁服务需要有且只能有1辆车');
            }
            if (count($parameter['batteryList'])>1){
                return $this->toError(500, '仅可选择一种电池');
            }
        }
        $parentInsId = $this->appData->getParentInsId($this->authed->insId,$this->authed->userType);

        if (!$parentInsId) {
            return $this->toError(500,'当前用户无权限');
        }
        // 校验区域价格设置
        $this->checkAreaPrice($parentInsId,$parameter['areas'], $parameter['productPackage']['packageDeposit'], in_array(2, $tmpTypes));
        $productList = [];
        // 处理商品
        $productFields = [
            'vehicleList' => [
                'productType' => 1,
                'rentToPackage' => 'vehicleRent',
            ],
            'batteryList' => [
                'productType' => 2,
                'rentToPackage' => 'batteryRent',
            ],
        ];
        foreach ($productFields as $productField => $fieldAttributes){
            $productType = $fieldAttributes['productType'];
            $rentToPackage = $fieldAttributes['rentToPackage'];
            if (!isset($parameter['productPackage'][$rentToPackage])){
                $parameter['productPackage'][$rentToPackage] = 0;
            }
            foreach ($parameter[$productField] as $k => $v){
                $v['productRent'] = (int) ($v['productRent']*10000);
                $v['productType'] = $productType;
                $parameter['productPackage'][$rentToPackage] += $v['productRent'];
                $productList[] = $v;
            }
            unset($parameter[$productField]);
        }
        $parameter['productSkuList'] = $productList;
        // 调用微服务接口
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10011,
            'parameter' => $parameter
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    /**
     * 删除套餐
     */
    public function DelAction($id)
    {
//        if ((new PackageData())->getPackageById($id)){
//            return $this->toError(500, '套餐已被使用，不可编辑');
//        }
        // 删除套餐
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10009",
            'parameter' => [
                'packageId' => $id,
            ],
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }

    /**
     * 批量更新套餐状态
     */
    public function UpstatusAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        //
        if (!isset($request['packageIds']) || []==$request['packageIds']){
            return $this->toError(500, '请选择要变更的套餐');
        }
        if (!isset($request['status'])){
            return $this->toError(500, '请指定变更状态');
        }
        // 1:启用，2:禁用 如果启用动作判断是否在有效期
        if (1==$request['status']){
            foreach ($request['packageIds'] as $id){
                $bol = (new PackageData())->isValidityPeriodByPackageId($id);
                // 如果不在有效期
                if(!$bol) return $this->toError(500, "当前套餐不在有效期，不可启用");
            }
        }
        $ids = implode(',', $request['packageIds']);
        // 变更状态
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => "10010",
            'parameter' => [
                'packageIds' => $ids,
                'status' => $request['status'],
            ],
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 成功返回
        return $this->toSuccess();
    }


    /**
     * 获取区域树
     */
    public function RegiontreeAction()
    {
        $result = $this->curl->httpRequest($this->Zuul->biz,["code" => "10022"],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        if (!isset($result['content']['data']))
            return $this->toError(500, "数据不存在");
        $data = $result['content']['data'];
        $tree = $this->RegionTreeRecursive($data);
        return $this->toSuccess($tree);
    }

    /**
     * 生成区域树
     * @param $data 区域数据
     * @param null $tree 递归使用，外部不用传
     * @return mixed  区域树
     */
    private function RegionTreeRecursive(&$data, $tree=null){
        // 首次进入 初始数据
        if (is_null($tree)){
            // 处理数据结构，方便递归
            $tmp = [];
            foreach ($data as $val){
                // 前端需求
                $val['title'] = $val['areaName'];
                // 强制字符串关联数组
                $tmp[(string)$val['areaParentId']][] = $val;
            }
            $data = $tmp;
            unset($tmp);
            // 获取一级区域
            $tree = $data['0'];
            unset($data['0']);
        }
        // 递归处理区域树
        foreach ($tree as $k => $v){
            // 目前不要三级区域
            if ($tree[$k]['areaDeep'] > 1){
                continue;
            }
            if (isset($data[(string)$v['areaId']])){
                $tree[$k]['children'] = $data[(string)$v['areaId']];
                unset($data[(string)$v['areaId']]);
                // 前端需求
                $tree[$k]['expand'] = true;
                $tree[$k]['children'] = $this->RegionTreeRecursive($data, $tree[$k]['children']);
            }
        }
        return $tree;
    }
//
//
//    /**
//     * 获取已选区域树
//     */
//    private function getRegionedTree($ids)
//    {
//        // 获取全部区域
//        $result = $this->curl->httpRequest($this->Zuul->biz,["code" => "10022"],"post");
//        //结果处理返回
//        if ($result['statusCode'] != '200') {
//            return $this->toError($result['statusCode'], $result['msg']);
//        }
//        if (!isset($result['content']['data']))
//            return $this->toError(500, "数据不存在");
//        $data = $result['content']['data'];
//        // 处理数据结构，方便递归
//        $tmpParent = [];
//        $tmpSelf = [];
//        foreach ($data as $val){
//            // 强制字符串关联数组
//            // 父子级关系
//            $tmpParent[(string)$val['areaParentId']][] = [
//                'areaId' => $val['areaId']
//            ];
//            // 前端需求
//            $val['title'] = $val['areaName'];
//            $tmpSelf[(string)$val['areaId']] = $val;
//        }
//        unset($data);
//        // 初始化数据
//        $data = [];
//        foreach ($ids as $id){
//            $data[] = ['areaId' => $id];
//        }
//        $this->RegionedTreeRecursive($tmpParent, $tmpSelf, $data);
//        return $data;
//    }
//
//    /**
//     * 生成已选区域树
//     */
//    private function RegionedTreeRecursive(&$tmpParent, &$tmpSelf, &$data){
//        // 递归处理区域树
//        foreach ($data as $k => $v){
//            $data[$k] = $tmpSelf[(string)$v['areaId']];
//            // 目前不要三级区域
//            if ($data[$k]['areaDeep'] > 1){
//                continue;
//            }
//            if (isset($tmpParent[(string)$v['areaId']])){
//                // 前端需求
//                $data[$k]['expand'] = true;
//                // 如有子级区域，加入
//                $data[$k]['children'] = $tmpParent[(string)$v['areaId']];
//                $this->RegionedTreeRecursive($tmpParent, $tmpSelf, $data[$k]['children']);
//            }
//        }
//    }

    private function checkAreaPrice($parentInsId,$areaList, $packageDeposit, $hasCharging)
    {
//        if (empty($areaList)){
//            return true;
//        }
//        $areaIds = [];
//        $areaNames = [];
//        foreach ($areaList as $area){
//            $areaIds[] = $area['areaId'];
//            $areaNames[$area['areaId']] = $area['areaName'];
//        }
//        $areaIds = array_unique($areaIds);
//        $APEPs = AreaPowerExchangePrice::arrFind([
//            'area_id' => ['IN', $areaIds]
//        ]);
//        if ($APEPs){
//            $APEPs = $APEPs->toArray();
//            foreach ($APEPs as $APEP){
//                unset($areaNames[$APEP['area_id']]);
//            }
//        }
//        if (empty($areaNames)){
//            return true;
//        }
        $areaIds = [];
        foreach ($areaList as $area){
            $areaIds[] = $area['areaId'];
        }
        $areaIds = array_unique($areaIds);
        if (empty($areaIds)){
            return true;
        }
        $APEPs = AreaPowerExchangePrice::arrFind([
            'ins_id' => $parentInsId,
            'area_id' => ['IN', $areaIds]
        ])->toArray();
        $setAreaIds = [];
        $maxPrice = 0;
        if ($APEPs){
            foreach ($APEPs as $APEP){
                $setAreaIds[] = $APEP['area_id'];
                $maxPrice = max($maxPrice, $APEP['price']);
            }
        }
        if ($maxPrice > $packageDeposit){
            throw new AppException([500, '押金不可低于所选区域的最高换电价格：'.round($maxPrice/10000, 2)]);
        }
        $unSetAreaIds = array_diff($areaIds, $setAreaIds);
        if (empty($unSetAreaIds)){
            return true;
        }
        $unSetAreas = Area::arrFind([
            'area_id' => ['IN', $unSetAreaIds]
        ])->toArray();
        if (empty($unSetAreas)){
            return true;
        }
        $areaNames = [];
        foreach ($unSetAreas as $area){
            $areaNames[] = $area['area_name'];
        }
        throw new AppException([500, '以下区域未设置换电价格：' . implode('、', $areaNames)]);
    }
}

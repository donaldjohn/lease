<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\library\ReturnCodeService;
use app\modules\BaseController;

//价格方案
class SchemesController extends BaseController {

    public function ListAction()
    {
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!is_null($indexText))
            $json['indexText'] = $indexText;
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $this->logger->info('查询车辆方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_SCHEMES);
        $pageInfo =$result['pageInfo'];
        $result = $result['data'];
        foreach ($result as $key =>  $item) {
            $result[$key]['createAt'] = isset($result[$key]['createAt']) ? date('Y-m-d H:i:s',$result[$key]['createAt']) : '-';
            $result[$key]['updateAt'] = isset($result[$key]['updateAt']) ? date('Y-m-d H:i:s',$result[$key]['updateAt']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);
    }

    public function OneAction($id)
    {
        $json['id'] = $id;
        $this->logger->info('查询车辆价格方案:'.json_encode($json));
        $result_scheme = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_SCHEMES_ID);
        $result_scheme = $result_scheme['data'];
        if (!isset($result_scheme['vehicleBomId'])) {
            return $this->toError(500,'该价格方案没有对应的车辆BOM');
        }
        //scheme 详情
        $schemes = [];
        $schemes['id'] = $result_scheme['id'];
        $schemes['schemeName'] = $result_scheme['schemeName'];
        $schemes['schemeCode'] = $result_scheme['schemeCode'];
        $schemes['schemeStatus'] = $result_scheme['schemeStatus'];
        $schemes['productName'] = $result_scheme['productName'];
        $schemes['skuValues'] = $result_scheme['skuValues'];
        $schemes['customerId'] = $result_scheme['customerId'];
        $schemes['userSimpleName'] = $result_scheme['userSimpleName'];
        $schemes['productName'] = $result_scheme['productName'];
        $schemes['skuInfo'] = $result_scheme['skuInfo'];



        $bom_id = $result_scheme['vehicleBomId'];  //价格方案对应的车辆BOMID

        $json['id'] = $bom_id;
        $this->logger->info('查询车辆BOMS:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_BOMS_ID);
        $bom = $result['data'];
        if ($result == null) {
            $result = ['scheme' => $schemes,'vehicleElement' => '','region' => '','areas' => ''];
            return $this->toSuccess($result);
        }

        //存在的bomelementID数组
        $elements = [];
        if (isset($bom['bomElement'])) {
            foreach ($bom['bomElement'] as $item) {
                array_push($elements,$item['elementId']);
            }
        }

        $keys  = [];
        $trees = [];
        if (isset($bom['vehicleType'])) {
            //根据车辆类型获取区域
            $vehicleType = $bom['vehicleType'];
            $area['areaStatus'] = 1;
            //绑定类型ID
            $area['vehicleTypeId'] = $vehicleType;
            $this->logger->info('查询车辆区域:'.json_encode($area));
            $result = $this->userData->common($area,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_AREA);
            $result = $result['data'];
            foreach ($result as $item) {
                $list = [];
                $list['id'] = $item['id'];
                $list['name'] = $item['areaName'];
                $list['title'] = $item['areaName'];
                $list['type'] = 'area';
                $list['expand'] = true;
                $list['areaOrder'] = $item['areaOrder'];
                $trees[$item['id']] = $list;
            }
            $json = [];
            $json['vehicleType'] = $vehicleType;
            $json['elementStatus'] = 1;
            $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_ELEMENT);
            foreach($result['data'] as $item) {
                if (in_array($item['id'],$elements)) {
                    $list = [];
                    $list['title'] = $item['elementName'];
                    $list['name'] = $item['elementName'];
                    $list['code'] = $item['elementCode'];
                    $list['id'] = $item['id'];
                    $list['type'] = 'vehicleElement';
                    $list['expand'] = true;
                    $list['children'] = [];
                    $trees[$item['elementArea']]['children'][$item['id']] = $list;
                    $keys[$item['id']] = $item['elementArea'];
                }
            }
        } else {
            return $this->toError(500,'该车辆BOM暂未指定车辆类型');
        }

        if (isset($bom['bomElement'])) {
            foreach ($bom['bomElement'] as $item) {
                $list = [];
                $list['title'] = $item['productName'].'##'.$item['skuValues'];
                $list['name'] = $item['productName'].'##'.$item['skuValues'];
                $list['id'] = $item['id'];
                $list['type'] = 'product';
                $list['elementId'] = $item['elementId'];
                $list['elementSku'] = $item['elementSku'];
                $list['id'] = $item['id'];
                $list['bomId'] = $item['bomId'];
                if (isset($result_scheme['schemeDetails'])) {
                    foreach ($result_scheme['schemeDetails'] as $key =>  $scheme) {
                        if ($list['id'] == $scheme['bomElementId']) {
                            $list['enableChange'] = $scheme['enableChange'];
                            $list['changeCost'] = $scheme['changeCost'];
                            $list['enableRepair'] = $scheme['enableRepair'];
                            $list['repairCost'] = $scheme['repairCost'];
                            //$list['bomElementSkuInfo'] = $scheme['bomElementSkuInfo'];
                            //$list['detailsId'] = $scheme['detailsId'];
                            $list['detailId'] = $scheme['id'];
                            unset($result_scheme['schemeDetails'][$key]);
                        }
                    }
                }
                if (isset($keys[$item['elementId']])) {
                    $trees[$keys[$item['elementId']]]['children'][$item['elementId']]['children'][] = $list;
                }
//                $trees[$keys[$item['elementId']]]['children'][$item['elementId']][] = $list;
            }
        } else {
            return $this->toError(500,'该车辆BOM暂未指定配件商品!');
        }

        $region = [];
        $areas = [];
        if (isset($result_scheme['schemeAreas'])) {
            foreach ($result_scheme['schemeAreas'] as $area) {
                    array_push($areas,$area['fitAreaId']);
            }
//            $params = ["code" => "10022","parameter" => (object)[]];
//            $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                return $this->toError($result['statusCode'], $result['msg']);
//            }
//            $result = $result['content']['data'];
//            $res_areas = [];
//            foreach ($result as $item) {
//                if (in_array($item['areaId'],$areas)) {
//                    $res_areas[] = $item;
//                }
//            }
            if (count($areas) >  0 ) {
//                $ids = [];
//                foreach ($res_areas as $key=>$item) {
//                    $list = [];
//                    $list['title'] = $item['areaName'];
//                    $list['name'] = $item['areaName'];
//                    $list['areaName'] = $item['areaName'];
//                    $list['areaParentId'] = $item['areaParentId'];
//                    $list['areaDeep'] = $item['areaDeep'];
//                    $list['areaId'] = $item['areaId'];
//                    $res_areas[$key] = $list;
//                    $ids[] = $item['areaId'];
//                }
                //$region = $this->warrantyData->res_choose_tree($res_areas);
                $region = $this->warrantyData->getTreeUse($areas);
            } else {
                $region = [];
            }
        }
        //序列化,保证json格式为数组
        foreach($trees as $key => $item) {
            if (isset($trees[$key]['children'])) {
                $k = $trees[$key]['children'];
                $trees[$key]['children'] = array_values($k);
            } else {
                unset($trees[$key]);
            }
        }
        $trees = array_values($trees);
        $result = ['scheme' => $schemes,'vehicleElement' => $trees,'region' => $region,'areas' => $areas];
        //$result = ['scheme' => $schemes,'vehicleElement' => $trees,'region' => $region];

        return $this->toSuccess($result);

    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['createAt'] = time();
        $json['updateAt'] = $json['createAt'];
        $this->logger->info('新增车辆方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_CREATE_SCHEMES);
        return $this->toSuccess(null,null,200,$result);

    }

    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        $json['updateAt'] = time();
        unset($json['createAt']);
        $this->logger->info('新增车辆方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_UPDATE_SCHEMES);
        return $this->toSuccess(null,null,200,$result);
    }

    public function StatusAction($id)
    {
        $data = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        $json['schemeStatus'] = isset($data['schemeStatus']) ? $data['schemeStatus'] : 1; //不传默认为1
        //$json['updateAt'] = date('Y-m-d H:i:s',time());
        $this->logger->info('更新车辆方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_STATUS_SCHEMES);
        return $this->toSuccess(null,null,200,$result);
    }

    public function DeleteAction($id)
    {
        $json['id'] = $id;
        $this->logger->info('删除车辆方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_DELETE_SCHEMES);
        return $this->toSuccess(null,null,200,$result);

    }


    // 方案导出
    public function LeadOutAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('价格方案导出:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_OUT_SCHEMES);
        return $this->toSuccess('http://'.$result['data']['data']['url'],null,200,$result['msg']);
    }

    // 方案导入
    public function LeadInAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('价格方案导入:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_IN_SCHEMES);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

    /**
     * 根据价格方案里的skuId获取可用区域树
     */
    public function RegionAction()
    {
        $skuId = $this->request->getQuery('skuId','int',null,true);
        if (is_null($skuId)) {
            return $this->toError(500,'商品SKU编号不能为空!');
        }
        $json = [];
        $json['skuId'] = $skuId;
        $json['status'] = 1;
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_SCHEMES_REGION);
        if (empty($result['data'])) {
            return $this->toSuccess('');
        }
        $areas = $result['data'];
        $region = $this->warrantyData->getTreeUse($areas);
        return $this->toSuccess($region);



    }

}
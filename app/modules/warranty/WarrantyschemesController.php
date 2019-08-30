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

class WarrantyschemesController extends BaseController {


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     * 获取联保方案列表
     */
    public function ListAction()
    {
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!is_null($indexText))
            $json['indexText'] = $indexText;
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $this->logger->info('查询联保方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_ORDERS_SCHEMES);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        foreach ($result as $key =>  $item) {
            $result[$key]['createAt'] = isset($item['createAt']) ? date('Y-m-d H:i:s',$item['createAt']) : '-';
            $result[$key]['updateAt'] = isset($item['updateAt']) ? date('Y-m-d H:i:s',$item['updateAt']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);
    }


    public function OneAction($id)
    {
        //通过ID查询联保订单
        $json['id'] = $id;
        $this->logger->info('查询联保方案:'.json_encode($json));
        $resultOrder = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_ORDERS_SCHEMES_ID);
        $resultOrder = $resultOrder['data'];
        if (!isset($resultOrder['vehicleSku'])) {
            return $this->toError(500,'该价格方案没有对应的车辆SKU');
        }

        $schemes = [];
        $schemes['customerId'] = $resultOrder['customerId'];
        $schemes['warrantyType'] = $resultOrder['warrantyType'];
        $schemes['warrantyStatus'] = $resultOrder['warrantyStatus'];
        $schemes['vehicleSku'] = $resultOrder['vehicleSku'];
        $schemes['vehicleInfo'] = $resultOrder['vehicleInfo'];
        $schemes['warrantySchemeCode'] = $resultOrder['warrantySchemeCode'];
        $schemes['id'] = $resultOrder['id'];
        $schemes['bizCode'] = $resultOrder['bizCode'];
        $schemes['userSimpleName'] = $resultOrder['userSimpleName'];

        $vehicleSku = $resultOrder['vehicleSku'];  //价格方案对应的车辆BOMID

        $json = [];
        $json['vehicleSku'] = $vehicleSku;
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
                $list['bomId'] = $item['bomId'];
                if (isset($resultOrder['schemeDetail'])) {
                    foreach ($resultOrder['schemeDetail'] as $key =>  $scheme) {
                        if ($list['id'] == $scheme['bomElementId']) {
                            //$list['warrantyOrderId'] = $scheme['warrantyOrderId'];
                            $list['detailId'] = $scheme['id'];
                            $list['elementExp'] = $scheme['elementExp'];
                            $list['effectAt'] = isset($scheme['effectAt']) ? date('Y-m-d H:i:s',$scheme['effectAt']) : '-';
                            $list['createAt'] = isset($scheme['createAt']) ? date('Y-m-d H:i:s',$scheme['createAt']) : '-';
                            $list['updateAt'] = isset($scheme['updateAt']) ? date('Y-m-d H:i:s',$scheme['updateAt']) : '-';
                            unset($resultOrder['schemeDetail'][$key]);
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
        if (isset($resultOrder['schemeAreas'])) {
            foreach ($resultOrder['schemeAreas'] as $area) {
                array_push($areas,$area['fitAreaId']);
            }
            //$areas = $resultOrder['fitAreaId'];
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
 //                   $ids[] = $item['areaId'];
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

        return $this->toSuccess($result);

    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['createAt'] = time();
        $json['updateAt'] = $json['createAt'];
        $this->logger->info('新增联保方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_CREATE_ORDERS_SCHEMES);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('修改联保方案:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_UPDATE_ORDERS_SCHEMES);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

    public function StatusAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('更新联保方案状态:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_STATUS_ORDERS_SCHEMES);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

    public function DeleteAction($id)
    {
        $json = ['id' => $id];
        $this->logger->info('删除联保方案:'.json_encode($id));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_DELETE_ORDERS_SCHEMES);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

}
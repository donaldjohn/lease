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

/**
 * Class BomsController
 * @package app\modules\warranty
 * 车辆架构
 */
class BomsController extends BaseController {


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     * 获取车辆BOM列表
     */
    public function ListAction()
    {
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!is_null($indexText))
            $json['indexText'] = $indexText;
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $this->logger->info('查询车辆BOM:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_BOMS);
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($result == null)
            return $this->toSuccess($result,$pageInfo);
        foreach ($result as $key =>  $item) {
            $result[$key]['createAt'] = !empty($result[$key]['createAt']) ? date('Y-m-d H:i:s',$result[$key]['createAt']) : '-';
            $result[$key]['updateAt'] = !empty($result[$key]['updateAt']) ? date('Y-m-d H:i:s',$result[$key]['updateAt']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);
    }

    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     * 获取车辆详情
     */
    public function OneAction($id)
    {
        $filter = $this->request->getQuery('filter','string','true');

        //获取车辆bom信息
        $json['id'] = $id;
        $this->logger->info('查询车辆BOM:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_BOMS_ID);
        $bom = $result['data'];

        //判断车辆bom是否存在
        if (empty($bom)) {
            return $this->toError(500,'车辆BOM不存在!');
        }

        //存在的elementID数组
        $elements = [];
        if (isset($bom['bomElement'])) {
            foreach ($bom['bomElement'] as $item) {
                array_push($elements,$item['elementId']);
            }
        }

        $keys  = [];
        $trees = [];
        if (isset($bom['vehicleType'])) {
            $vehicleType = $bom['vehicleType'];
            //根据$vehicleType获取$vehicleTypeName
            $json = [];
            $json['id'] = $vehicleType;
            $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_TYPE);
            $bom['vehicleTypeName'] = isset($result['data'][0]['typeName']) ? $result['data'][0]['typeName'] : '-';
            //根据车辆类型获取区域
            $area['areaStatus'] = 1;
            //绑定类型ID
            $area['vehicleTypeId'] = $vehicleType;
            $this->logger->info('查询车辆区域:'.json_encode($area));
            $result = $this->userData->common($area,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_AREA);

            foreach ($result['data'] as $item) {
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
                if ($filter == 'false' || in_array($item['id'],$elements)) {
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
                $list['elementId'] = $item['elementId'];
                $list['elementSku'] = $item['elementSku'];
                $list['bomId'] = $item['bomId'];
                $list['type'] = 'product';
                if (isset($keys[$item['elementId']])) {
                    $trees[$keys[$item['elementId']]]['children'][$item['elementId']]['children'][] = $list;
                }

            }
            unset($bom['bomElement']);
        }
        foreach($trees as $key => $item) {
            if (isset($trees[$key]['children'])) {
                $k = $trees[$key]['children'];
                $trees[$key]['children'] = array_values($k);
            } else {
                if ($filter == 'true') {
                    unset($trees[$key]);
                }
            }
        }
        $trees = array_values($trees);
        $result = ['bom' => $bom,'vehicleElement' => $trees];

        return $this->toSuccess($result);

    }

    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['createAt'] = time();
        $json['updateAt'] = $json['createAt'];
        $this->logger->info('新增车辆BOM:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_CREATE_BOMS);
        return $this->toSuccess(null,null,200,$result['msg']);

    }

    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('新增车辆BOM:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_UPDATE_BOMS);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

    public function StatusAction($id)
    {
        $data = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        $json['bomStatus'] = isset($data['bomStatus']) ? $data['bomStatus'] : 1; //不传默认为1
        //$json['updateAt'] = time();
        $this->logger->info('更新车辆BOMS:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_STATUS_BOMS);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

    public function DeleteAction($id)
    {
        $json['id'] = $id;
        $this->logger->info('删除车辆Bom:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_DELETE_BOMS);
        return $this->toSuccess(null,null,200,$result['msg']);

    }


    /**
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     * 获取车辆详情
     */
    public function SkuBomAction()
    {
        //获取车辆bom信息
        $skuId = $this->request->getQuery('skuId','string',null);
        if (empty($skuId)) {
            return $this->toError(500,"SKUID不能为空!");
        }
        $json['vehicleSku'] = $skuId;
        $this->logger->info('查询车辆BOM:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_BOMS_ID);
        $bom = $result['data'];
        //判断车辆bom是否存在
        if (empty($bom)) {
            return $this->toError(500,'车辆BOM不存在!');
        }
        //判断bom状态是否启用
        if (isset($bom['bomStatus']) && $bom['bomStatus'] != 1) {
            return $this->toError(500,'车辆BOM状态不可用!');
        }
        //存在的bomelementID数组
        $bomElements = [];
        if (isset($bom['bomElement'])) {
            foreach ($bom['bomElement'] as $item) {
                array_push($bomElements,$item['elementId']);
            }
        }
        $keys  = [];
        $trees = [];
        if (isset($bom['vehicleType'])) {
            $vehicleType = $bom['vehicleType'];
            //根据$vehicleType获取$vehicleTypeName
            $json = [];
            $json['id'] = $vehicleType;
            $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_TYPE);
            $bom['vehicleTypeName'] = isset($result['data'][0]['typeName']) ? $result['data'][0]['typeName'] : '-';
            //根据车辆类型获取区域
            $area['areaStatus'] = 1;
            //绑定类型ID
            $area['vehicleTypeId'] = $vehicleType;
            $this->logger->info('查询车辆区域:'.json_encode($area));
            $result = $this->userData->common($area,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_AREA);

            foreach ($result['data'] as $item) {
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
                if (in_array($item['id'],$bomElements)) {
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
                $list['elementId'] = $item['elementId'];
                $list['elementSku'] = $item['elementSku'];
                $list['bomId'] = $item['bomId'];
                $list['type'] = 'product';
                if (isset($keys[$item['elementId']])) {
                    $trees[$keys[$item['elementId']]]['children'][$item['elementId']]['children'][] = $list;
                }

            }
            unset($bom['bomElement']);
        }
        foreach($trees as $key => $item) {
            if (isset($trees[$key]['children'])) {
                $k = $trees[$key]['children'];
                $trees[$key]['children'] = array_values($k);
            } else {
                unset($trees[$key]);
            }
        }
        $trees = array_values($trees);
        $result = ['bom' => $bom,'vehicleElement' => $trees];

        return $this->toSuccess($result);

    }



    public function LeadInAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_IN_BOMS);
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }

    public function LeadOutAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('BOM导出:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_OUT_BOMS);
        return $this->toSuccess('http://'.$result['data']['data']['url'],null,200,$result['msg']);
    }

}
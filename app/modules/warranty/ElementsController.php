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

class ElementsController extends BaseController {


    public function ListAction()
    {
        //获取url int参数status,不传status默认为1
        $indexText = $this->request->getQuery('indexText','string',null);
        if (!empty($indexText)) {
            $json['indexText'] = $indexText;
        }
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $this->logger->info('查询车辆类型:'.json_encode($json));
        $elementStatus = $this->request->getQuery('elementStatus','int',null);
        if (!is_null($elementStatus)) {
            $json['elementStatus'] = $elementStatus;
        }
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_ELEMENT);
        $meta = $result['pageInfo'];
        $result = $result['data'];
        if ($result == null)
            return $this->toSuccess($result,$meta);
        foreach ($result as $key =>  $item) {
            $result[$key]['createAt'] =  isset($result[$key]['createAt']) ? date('Y-m-d H:i:s',$result[$key]['createAt']) : null;
            $result[$key]['updateAt'] =  isset($result[$key]['updateAt']) ? date('Y-m-d H:i:s',$result[$key]['updateAt']) : null;
        }
        return $this->toSuccess($result,$meta);
    }


    public function OneAction($id)
    {
        $json['id'] = $id;
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_VEHICLE_ELEMENT);
        $result = $result['data'];
        if (isset($result[0])) {
            $result[0]['createAt'] = isset($result[0]['createAt']) ? date('Y-m-d H:i:s',$result[0]['createAt']) : '-';
            $result[0]['updateAt'] = isset($result[0]['updateAt']) ? date('Y-m-d H:i:s',$result[0]['updateAt']) : '-';
            return $this->toSuccess($result[0]);
        } else {
            return $this->toSuccess(null);
        }
    }


    public function CreateAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $json['createAt'] = time();
        $json['updateAt'] = $json['createAt'];
        $json['elementStatus'] = isset($json['elementStatus']) ? $json['elementStatus'] : 1;
        $this->logger->info('新增车辆架构:'.json_encode($json));
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_CREATE_VEHICLE_ELEMENT);
        return $this->toSuccess(null,null,200,$result['msg']);
    }


    public function UpdateAction($id)
    {
        $json = $this->request->getJsonRawBody(true);
        $json['id'] = $id;
        unset($json['createAt']);
        $json['updateAt'] = time();
        $this->logger->info('更新车辆架构:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_UPDATE_VEHICLE_ELEMENT);
        return $this->toSuccess(null,null,200,$result['msg']);
    }

    public function StatusAction($id)
    {
        $json['id'] = $id;
        $data = $this->request->getJsonRawBody(true);
        if (!isset($data['elementStatus']))
            return $this->toError(500,'状态必填!');
        $key = [1,2];
        if(!in_array($data['elementStatus'],$key)) {
            return $this->toError(500,'状态必需为1或2!');
        }
        $json['elementStatus'] = $data['elementStatus'];
        //$json['updateAt'] = time();
        $this->logger->info('更新车辆架构:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_STATUS_VEHICLE_ELEMENT);
        return $this->toSuccess(null,null,200,$result['msg']);
    }


    public function DeleteAction($id)
    {
        $json['id'] = $id;
        $this->logger->info('删除车辆架构:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_DELETE_VEHICLE_ELEMENT);
        return $this->toSuccess(null,null,200,$result['msg']);
    }


    public function TreeAction()
    {
        $trees = [];
        $vehicleType = $this->request->getQuery('vehicleType','int',null);
        if (is_null($vehicleType)) {
            return  $this->toError(500,'类型不能为空!');
        }
        //根据车辆类型获取区域
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
        $result = $result['data'];
        foreach($result as $item) {
            $list = [];
            $list['title'] = $item['elementName'];
            $list['name'] = $item['elementName'];
            $list['code'] = $item['elementCode'];
            $list['id'] = $item['id'];
            $list['type'] = 'vehicleElement';
            $list['expand'] = true;
            $list['children'] = [];
            if (isset($trees[$item['elementArea']]))
                $trees[$item['elementArea']]['children'][] = $list;
        }
        $result = [];
        $result['vehicleElement'] = array_values($trees);

        return $this->toSuccess($result);
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\DataException
     * @throws \app\common\errors\MicroException
     */
    public function OrderAction()
    {
        $elementArea = $this->request->getQuery('elementArea','int',null);
        if (is_null($elementArea))
            return  $this->toError(500,'部件区域ID不能为空!');
        $json['elementArea'] = $elementArea;
        $vehicleType = $this->request->getQuery('vehicleType','int',null);
        if (is_null($vehicleType))
            return  $this->toError(500,'车辆类型ID不能为空!');
        $json['vehicleType'] = $vehicleType;
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_ORDER_VEHICLE_ELEMENT);
        return $this->toSuccess($result['data']);
    }


    // 车辆架构导出
    public function LeadOutAction()
    {
        $json = $this->request->getJsonRawBody(true);
        $this->logger->info('车辆架构导出:'.json_encode($json));
        $result = $this->userData->postCommon($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_OUT_VEHICLE_ELEMENT);
        return $this->toSuccess('http://'.$result['data']['data']['url'],null,200,$result['msg']);
    }

}
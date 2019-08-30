<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/9/17
 * Time: 17:58
 * 交警执法界面
 */
namespace app\modules\traffic;

use app\models\charging\BoxSocketChongdian;
use app\models\charging\BoxSocketNewchongdian;
use app\models\service\Vehicle;
use app\modules\BaseController;

class PoliceController extends BaseController
{

    /**
     * 获取车辆列表
     */
    public function ListAction()
    {
        $cityId  = $this->request->getQuery('cityId','string',null);
        $provinceId  = $this->request->getQuery('provinceId','string',null,true);
        $isOnline  = $this->request->get('isOnline');
        $plateNum  = $this->request->getQuery('plateNum','string',null,true);
        $pageNum = $this->request->getQuery('pageNum','int',0);
        $insIds = $this->request->getQuery('insIds','int',0);
        $pageSize = $this->request->getQuery('pageSize','int',0);
        $params = [];
        if ($pageSize != 0) {
            $params['pageSize'] = (int)$pageSize;
        }
        if ($pageNum != 0) {
            $params['pageNum'] = (int)$pageNum;
        }
        if ($isOnline != null) {
            $params['isOnline'] = $isOnline;
        }
        if ($plateNum != null) {
            $params['plateNum'] = $plateNum;
        }
        if ($provinceId != null && $provinceId > 0) {
            $params['provinceId'] = $provinceId;
        }
        if ($cityId != null && $cityId > 0) {
            $params['cityId'] = $cityId;
        }
        if ($insIds > 0) {
            $params['insIds'] = [$insIds];
        }
        $params['insId'] = $this->authed->insId;

        $data = [
            'parameter' => $params,
            'code' => '60030',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $this->toSuccess($result['content']['data'], $result['content']['pageInfo']);
    }

    /**
     * 获取电子围栏
     */
    public function RailAction()
    {
        $cityId  = $this->request->getQuery('cityId','string',null);
        $provinceId  = $this->request->getQuery('provinceId','string',null);
        $params = [
            "cityId" => $cityId,
            "provinceId" => $provinceId
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '16001',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->biz, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $this->toSuccess($result['content']);
    }

    /**
     *
     */
    public function DetailAction()
    {
        $vehicleId  = $this->request->getQuery('vehicleId','int',0);
        $peccancyTime  = $this->request->getQuery('peccancyTime','string',null);
        $params = [
            "vehicleId" => $vehicleId,
            "peccancyTime" => time()-$peccancyTime
        ];
        $params = array_filter($params);
        $data = [
            'parameter' => $params,
            'code' => '60019',
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $this->toSuccess($result['content']);
    }
}
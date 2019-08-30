<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27 0027
 * Time: 17:17
 */
namespace app\modules\vehicle;

use app\modules\BaseController;
use phpDocumentor\Reflection\Types\Object_;

class StorecountController extends BaseController{

    /**
     * 获取列表信息
     */
    public function IndexAction()
    {
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $json['cityId'] = $this->request->getQuery('cityId','int',0);
        $json['storeId'] = $this->request->getQuery('storeId','int',0);

        $json = array_filter($json);
        //TODO
        $data = [
            'parameter' => $json,
            'code' => 11058,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : [] ,
            isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : []);
    }
    /**
     * 获取详细信息
     */
    public function DetailAction()
    {
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $json['endTime'] = (int)$this->request->getQuery('endTime','int',0);
        $json['startTime'] = $this->request->getQuery('startTime','int',0);
        $json['storeId'] = $this->request->getQuery('storeId','int',0);
        $json['querySource'] = $this->request->getQuery('querySource','int',2);
        $json = array_filter($json);
        $data = [
            'parameter' => $json,
            'code' => 11059,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess(isset($result['content']['data']) ? $result['content']['data'] : [] ,
            isset($result['content']['pageInfo']) ? $result['content']['pageInfo'] : []);
    }

    /**
     * 获取门店列表
     */
    public function StoreAction()
    {
        $cityId = $this->request->getQuery('cityId','int',"");
        $data = [
            'parameter' => ["cityId"=> $cityId],
            'code' => 11063,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }
    /**
     * 门店后装列表导出
     */
    public function ExportAction()
    {
        $json['cityId'] = $this->request->getQuery('cityId','int',"");
        $json['storeId'] = $this->request->getQuery('storeId','int',"");
        $json = array_filter($json);

        if (empty($json)) {
            $json = new Object_();
        }
        //todo:未提供接口
        $data = [
            'parameter' => $json,
            'code' => 11061,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }

    /**
     * 门店安装日报表
     */
    public function ExportDayAction()
    {
        $json['endTime'] = (int)$this->request->getQuery('endTime','int',0);
        $json['startTime'] = $this->request->getQuery('startTime','int',0);
        $json['storeId'] = $this->request->getQuery('storeId','int',0);
        $json['querySource'] = $this->request->getQuery('querySource','int',2);
        if (!$json['storeId']) {
            return $this->toError('500', '请选择门店');
        }
        $json = array_filter($json);
        //todo:未提供接口
        $data = [
            'parameter' => $json,
            'code' => 11062,
        ];
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $data, "POST");
        if ($result['statusCode'] <> 200) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess($result['content']['data']);
    }
}
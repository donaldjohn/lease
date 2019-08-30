<?php
namespace app\modules\microprograms;


use app\modules\BaseController;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

/**
 * Class BatchController
 * 四码合一批次类：批次新增、批次查询、批次提交与车辆插入数据
 * @author  Lishiqin
 * @package app\modules\microprograms
 */
class BatchController extends BaseController
{
    const BATCH_ABLE    = 1;
    const BATCH_DISABLE = 2;

    /**
     * 新增四码合一批次
     * @param int productId 商品ID（必填）
     * @param int productSkuId 规格ID（必填）
     * @return mixed
     */
    public function AddAction()
    {
        try {
            $request = $this->request->getJsonRawBody();
            $productId    = isset($request->productId) ? $request->productId : 0;
            $productSkuId = isset($request->productSkuId) ? $request->productSkuId : 0;

            // 商品ID、规格ID有效性判断
            if ($productId <= 0 || $productSkuId <= 0) {
                return $this->toError(500, '商品或规格ID不能为空');
            }

            // 新增四码合一批次参数
            $params = [
                'code' => 10009,
                'parameter' => [
                    'userId'        => $this->authed->userId,
                    'insId'         => isset($this->authed->insId) ? $this->authed->insId : 0,
                    'productId'     => $productId,
                    'productSkuId'  => $productSkuId,
                    'batchNum'      => "DW".time(),
                    'status'        => self::BATCH_ABLE,
                ]
            ];

            // 调用接口，新增四码合一批次并对返回结果进行判断
            $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");

            if ($result['statusCode'] != 200 && isset($result['content'][0]['id'])) {
                return $this->toError(500, '新增批次失败');
            }

            return $this->toSuccess($result['content']);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 获取四码合一批次信息
     * @param int batchId 批次ID（必填）
     * @param int status 批次状态
     * @return mixed
     */
    public function ListAction()
    {
        // 前端数据过滤
        $batchId = intval($this->request->get("batchId"));
        $status = intval($this->request->get("status"));

        // 获取所有品牌名称
        $params["code"] = 10002;
        $productAll = $this->curl->httpRequest($this->Zuul->product, $params, "POST");
        $productName = [];
        foreach ($productAll["content"]["productList"] as $item) {
            $productName[$item['productId']] = $item['productName'];
        }

        // 获取所有型号名称
        $params["code"] = 10004;
        $skuAll = $this->curl->httpRequest($this->Zuul->product, $params, "POST");
        $skuName = [];
        foreach ($skuAll["content"]["productSkuRelationList"] as $item) {
            $skuName[$item['id']] = $item['skuValues'];
        }

        // 拼装获取目录列表API接口所需参数
        $params["code"] = 10011;
        $params["parameter"]["userId"] = $this->authed->userId;
        if ($batchId) {
            $params["parameter"]["id"] = $batchId ? $batchId : "";
        } else {
            $params["parameter"]["status"] = $status == 1 ? 1 : 2;
        }
        // 请求微服务接口
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");
        // 处理返回数据
        if ($result["statusCode"] == 200) {
            $data = $result["content"]["codeMergeBatchDOS"];
            // 如果查批次详情则获取批次下记录的列表
            foreach ($data as $key => $value) {
                $data[$key]['productName'] = $productName[$value["productId"]];
                $data[$key]['productSkuName'] = $skuName[$value["productSkuId"]];
                $data[$key]['createAt'] = date('Y-m-d H:i', $value['createAt']);

                // 拼装获取批次下记录列表
                $_params["code"] = 10013;
                $_params["parameter"]["batchId"] = $value['id'];

                // 请求微服务接口
                $_result = $this->curl->httpRequest($this->Zuul->biz, $_params, "POST");
                if ($batchId) {
                    $data[$key]['recordList'] = $_result['content']['codeMergeListDOS'];
                }
                $data[$key]['amount'] = count($_result['content']['codeMergeListDOS']);
                if ($data[$key]['amount'] == 0) {
                    unset($data[$key]);
                }
            }
            $this->toSuccess($data);
        } else {
            $this->toError("400", $result["msg"]);
        }
    }

    /**
     * 批次信息修改
     */
    public function UpdateAction()
    {
        // 前端数据过滤
        $request = $this->request->getJsonRawBody();
        $batchId = isset($request->batchId) ? $request->batchId : "";
        $productId = isset($request->productId) ? $request->productId : "";
        $productSkuId = isset($request->productSkuId) ? $request->productSkuId : "";

        // 拼装获取目录列表API接口所需参数
        $params["code"] = 10010;
        $params["parameter"]["id"] = $batchId ? $batchId : "";
        $params["parameter"]["status"] = 2;
        $params["parameter"]["updateAt"] = time();

        // 请求微服务接口
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");
        // 处理返回数据
        if ($result["statusCode"] == 200 && $productId && $productSkuId) {
            // 获取该批次下的所有绑定记录信息
            $_params["code"] = 10013;
            $_params["parameter"]["batchId"] = $batchId;

            // 请求微服务接口
            $_result = $this->curl->httpRequest($this->Zuul->biz, $_params, "POST");

            $vehicleList = [];
            foreach ($_result['content']['codeMergeListDOS'] as $key => $value) {
                $bianhaoList[] = $value['bianhao'];
                $vehicleList[$key]["vin"] = $value["vin"];
                $vehicleList[$key]["udid"] = $value["udid"];
                $vehicleList[$key]["bianhao"] = $value["bianhao"];
                $vehicleList[$key]["plateNum"] = $value["plateNum"];
                $vehicleList[$key]["productId"] = $productId;
                $vehicleList[$key]["productSkuId"] = $productSkuId;
            }


            // 修改二维码状态
            $vehicle_params["code"] = 60021;
            $vehicle_params["parameter"]["bianhaoList"] = $bianhaoList;
            // 请求微服务接口

            $result = $this->curl->httpRequest($this->Zuul->vehicle, $vehicle_params, "POST");

            if ($result['statusCode'] != 200) {
                return $this->toError(400, "二维码操作失败");
            }

            if ($this->addVehicle($vehicleList)) {
                $this->toSuccess($result["msg"]);
            } else {
                $this->toError("400", "车辆插入失败");
            }
        } else {
            $this->toError("400", $result["msg"]);
        }
    }

    /**
     * 四码合一批次提交后同步新增车辆信息
     * @param $vehicleList
     * @return bool
     */
    private function AddVehicle($vehicleList)
    {
        $vehicle = [];
        // 参数封装
        foreach ($vehicleList as $key => $value) {
            $vehicle[$key]["vin"] = $value["vin"];
            $vehicle[$key]["udid"] = $value["udid"];
            $vehicle[$key]["bianhao"] = $value["bianhao"];
            $vehicle[$key]["plateNum"] = $value["plateNum"];
            $vehicle[$key]["productId"] = $value["productId"];
            $vehicle[$key]["productSkuRelationId"] = $value["productSkuId"];
        }

        // 拼装获取目录列表API接口所需参数
        $params["code"] = 60006;
        $params["parameter"]["vehicleList"] = $vehicle;

        // 请求微服务接口
        $result = $this->curl->httpRequest($this->Zuul->vehicle, $params, "POST");

        // 处理返回数据
        if ($result["statusCode"] == 200) {
            return $result["content"]["count"];
        } else {
            return false;
        }
    }

}
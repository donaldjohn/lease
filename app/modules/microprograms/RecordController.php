<?php
namespace app\modules\microprograms;


use app\modules\BaseController;

/**
 * Class RecordController
 * 四码合一记录类：对四码合一记录进行新增、搜索、删除、修改操作
 * @author Lishiqin
 * @package app\modules\microprograms
 */
class RecordController extends BaseController
{

    /**
     * 四码合一记录搜索，通过批次ID可以搜索出批次下的所有记录，通过记录ID查询单条记录详情
     * @param int batchId 四码合一批次ID（可选）
     * @param int id 四码合一记录ID（可选）
     * @return mixed
     */
    public function ListAction()
    {
        try {
            // 请求中批次batchId与四码合一记录id过滤
            $request    = $this->request->get();
            $batchId    = isset($request['batchId']) ? $request['batchId'] : 0;
            $recordId   = isset($request['id']) ? $request['id'] : 0;
            if ($batchId <= 0 && $recordId <= 0) {
                return $this->toError(500, "参数不能为空");
            }

            // 根据批次batchId与记录recordId判断是查询批次下的所有记录还是单条记录信息
            if ($batchId > 0) {
                $id['batchId'] = $batchId;
            }
            if ($recordId > 0) {
                $id['id'] = $recordId;
            }

            // 调用查询记录的公共方法
            $result = $this->searchRecord($id);

            if ($result['status']) {
                return $this->toSuccess($result['msg']);
            } else {
                return $this->toError(500, $result['msg']);
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 新增四码合一记录
     * @param batchId int 新增四码合一记录所属批次ID
     * @param vin string 车架号
     * @param udid string 设备编号
     * @param bianhao string 得威二维码
     * @param plateNum string 车牌号
     * @return mixed
     */
    public function AddAction()
    {
        // 对请求中的批次ID、车架号、设备号、得威二维码、车牌号进行有效性验证
        $request    = $this->request->getJsonRawBody();
        $batchId    = isset($request->batchId) ? $request->batchId : 0;
        $vin        = isset($request->vin) ? $request->vin : "";
        $udid       = isset($request->udid) ? $request->udid : "";
        $bianhao    = isset($request->bianhao) ? $request->bianhao : "";
        $plateNum   = isset($request->plateNum) ? $request->plateNum : "";
        if ($batchId <= 0) {
            return $this->toError(500, '批次ID不合法');
        }
        if (!$vin && !$udid && !$bianhao && !$plateNum) {
            return $this->toError(500, '四码合一记录参数不完整');
        }

        $params = [
            "code" => 10014,
            "parameter" => [
                "batchId"   => $batchId,
                "vin"       => $vin,
                "udid"      => $udid,
                "bianhao"   => $bianhao,
                "plateNum"  => $plateNum
            ]
        ];

        // 请求微服务接口新增四码合一记录
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");

        // 判断结果，并返回
        if ($result["statusCode"] == 200) {
            $this->toSuccess('新增成功');
        } else {
            $this->toError(500, '新增失败');
        }
    }

    /**
     * 修改四码合一记录
     * @param id int 四码合一记录ID
     * @param vin string 车架号
     * @param udid string 设备编号
     * @param bianhao string 得威二维码
     * @param plateNum string 车牌号
     * @return mixed
     */
    public function UpdateAction()
    {
        // 对请求中的记录ID、车架号、设备号、得威二维码、车牌号进行有效性验证
        $request  = $this->request->getJsonRawBody();
        $id       = isset($request->id) ? $request->id : 0;
        $vin      = isset($request->vin) ? $request->vin : "";
        $udid     = isset($request->udid) ? $request->udid : "";
        $bianhao  = isset($request->bianhao) ? $request->bianhao : "";
        $plateNum = isset($request->plateNum) ? $request->plateNum : "";

        if ($id <= 0) {
            return $this->toError(500, '记录ID不合法');
        }

        // 判断记录是否存在
        if (!$this->searchRecord(['id' => $id])['status']) {
            return $this->toError(500, '修改的记录不存在');
        }

        // 判断修改的四码合一记录参数是否完整
        if (!$vin && !$udid && !$bianhao && !$plateNum) {
            return $this->toError(500, '四码合一记录参数不完整');
        }

        $params = [
            "code" => 10016,
            "parameter" => [
                "id"        => $id,
                "vin"       => $vin,
                "udid"      => $udid,
                "bianhao"   => $bianhao,
                "plateNum"  => $plateNum,
            ]
        ];

        // 请求微服务接口修改四码合一记录
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");

        // 判断结果，并返回
        if ($result["statusCode"] == 200) {
            $this->toSuccess('修改成功');
        } else {
            $this->toError("400", '修改失败');
        }
    }

    /**
     * 删除四码合一记录
     * @param id int 四码合一记录ID
     * @return mixed
     */
    public function DeleteAction()
    {
        // 请求中四码合一记录id过滤
        $request  = $this->request->getJsonRawBody();
        $id       = isset($request->id) ? $request->id : 0;

        if ($id <= 0) {
            return $this->toError("500", '记录ID不合法');
        }

        // 判断记录是否存在
        if (!$this->searchRecord(['id' => $id])['status']) {
            return $this->toError(500, '删除的记录不存在');
        }

        $params = [
            "code" => 10015,
            "parameter" => [
                "id" => $id
            ]
        ];

        // 请求微服务接口删除四码合一记录
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");

        // 处理返回数据
        if ($result["statusCode"] == 200) {
            $this->toSuccess('删除记录成功');
        } else {
            $this->toError(500, '删除记录失败');
        }
    }

    /**
     * 四码合一记录查询公共方法
     * @param $params array 查询批次的参数
     * @return mixed
     */
    private function SearchRecord($params) {

        // 判断所传参数是否包含批次ID或者记录ID
        if (!isset($params['batchId']) && !isset($params['id'])) {
            return ['status' => false, 'msg' => '缺少参数'];
        }

        $params = [
            'code' => 10013,
            'parameter' => $params
        ];

        // 请求微服务接口查询四码合一记录信息
        $result = $this->curl->httpRequest($this->Zuul->biz, $params, "POST");

        // 判断结果，并返回
        if ($result["statusCode"] == 200 && count($result['content']['codeMergeListDOS']) > 0) {
            return ['status' => true, 'msg' => $result["content"]['codeMergeListDOS']];
        } else {
            return ['status' => false, 'msg' => '查询不到相关记录'];
        }
    }

}

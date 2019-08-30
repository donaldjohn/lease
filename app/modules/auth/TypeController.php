<?php
namespace app\modules\auth;


use app\modules\BaseController;


class TypeController extends BaseController
{

    /**
     * 获取系统类型
     */
    public function listAction()
    {
        $params = ["code" => "10099"];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            $result = $result['content']['types'];
            if (count($result) > 1) {
                foreach ($result as $item) {
                    $api = [];
                    $api["id"] = $item["id"];
                    $api["typeName"] = $item["typeName"];
                    $list[] = $api;
                }
                return $this->toSuccess($list);
            }
        }
        return $this->toError(500,"获取数据失败");

    }

}
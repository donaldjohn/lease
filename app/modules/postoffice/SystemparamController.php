<?php
namespace app\modules\postoffice;

use app\common\errors\DataException;
use app\common\library\ReturnCodeService;
use app\common\library\ZuulApiService;
use app\models\service\RegionVehicle;
use app\models\service\Vehicle;
use app\models\users\User;
use app\modules\BaseController;
use app\services\data\UserData;

// 邮管系统参数
class SystemparamController extends BaseController
{
    // 获取邮管系统参数
    public function ListAction()
    {
        $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);
        $cityId = $area['cityId'] ?? 0;
        if (!($cityId > 0)){
            return $this->toError(500, '非市级机构，不可操作');
        }
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15022',
            'parameter' => [
                'cityId' => $cityId,
            ]
        ],"post");
        //结果处理返回
        if (200 != $result['statusCode']) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $data = $result['content']['data'];
        return $this->toSuccess($data);
    }

    // 编辑邮管系统参数
    public function EditAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $area = $this->userData->getAreaByInsId($this->authed->insId, $this->authed->userType);
        $cityId = $area['cityId'] ?? 0;
        if (!($cityId > 0)){
            return $this->toError(500, '非市级机构，不可操作');
        }
        $parameter['insId'] = $this->authed->insId;
        $parameter['cityId'] = $cityId;
        $parameter['list'] = $request;
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => '15023',
            'parameter' => $parameter
        ],"post");
        //结果处理返回
        if (200 != $result['statusCode']) {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        return $this->toSuccess();
    }

}

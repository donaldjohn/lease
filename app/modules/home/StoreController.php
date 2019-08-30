<?php
namespace app\modules\home;


use app\modules\BaseController;
use app\services\data\StoreData;

class StoreController extends BaseController
{
    // APP门店地图
    public function APPStoreMapAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $EnableBar = $request['EnableBar'] ?? false;
        $needTypeNum = $request['needTypeNum'] ?? false;
        $rentAPP = $request['rentAPP'] ?? false;
        if (empty($request['cityId']) && (empty($request['lng']) || empty($request['lat']))){
            return $this->toError(500, '未获取到位置条件');
        }
        $data = (new StoreData())->getAPPStoreMap($request, $EnableBar, $rentAPP, $needTypeNum);
        return $this->toSuccess($data);
    }
}
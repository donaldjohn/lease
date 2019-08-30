<?php
namespace app\modules\postofficeapp;

use app\models\dispatch\Drivers;
use app\models\dispatch\RegionDrivers;
use app\models\service\Area;
use app\modules\BaseController;
use app\services\data\CommonData;
use app\services\data\PostOfficeData;
use app\services\data\StoreData;
use app\services\data\UserData;
use app\services\auth\Authentication;
use app\services\data\MessagePushData;

class StoreController extends PostofficebaseController
{
    // 小哥助手APP获取附近门店
    public function NearbyStoreAction()
    {
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        // 获取门店数据
        $data = (new StoreData())->getAPPStoreMap($request, false);
        return $this->toSuccess($data);
    }

    /**
     *
     */
    public function CityAction()
    {
        $cityList = $this->config->postoffice->city_list;
        $data = [];
        foreach ($cityList as $key => $val) {
            $areaName = Area::findFirst($val);
            $data[] = ['cityId' => $val, 'cityName'=> $areaName->area_name];
        }
        return $this->toSuccess($data);
    }

    /**
     * 是否需要验证
     */
    public function NeedAuthAction()
    {
        $cityId = $this->request->getQuery('cityId','int',1,true);
        $needAuth = (new PostOfficeData())->getPostOfficeSystemParam($cityId, PostOfficeData::RealAuthentication);
        return $this->toSuccess(['needAuth' => $needAuth]);
    }

    /**
     * 获取城市是否需要显示考试信息
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function DisplayAction()
    {
//        $cityId = $this->request->getQuery('cityId','int',1,true);
        $RD = RegionDrivers::arrFindFirst([
            'driver_id' => $this->authed->userId,
        ]);
        $display = false;
        if ($RD) {
            $cityId = (new PostOfficeData())->getPostOfficeCityIdByExpressInsId($RD->ins_id);
            if ($cityId) {
                $display = (new PostOfficeData())->getPostOfficeSystemParam($cityId, PostOfficeData::MobileExam);
            }
        }
        return $this->toSuccess(['display' => $display]);
    }
}

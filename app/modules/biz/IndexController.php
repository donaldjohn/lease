<?php
namespace app\modules\biz;


use app\models\service\Area;
use app\models\users\Institution;
use app\modules\BaseController;
use app\services\data\PostOfficeData;

class IndexController extends BaseController
{

    public function getInsExpressPrintDriversLicenseStatusAction()
    {
        $insId = 0;
        if ($this->authed->userType == 7) {
            $ins = Institution::findFirst($this->authed->insId);
            $insId = $ins->getParentId();
        } else {
            $insId = $this->authed->insId;
        }


        //根据机构ID获取城市ID
        $areas = $this->userData->getAreaByInsId($insId);

        if (!isset($areas['cityId'])) {
            return $this->toSuccess(false);
        }
        $result = (new PostOfficeData())->getPostOfficeSystemParam($areas['cityId'], PostOfficeData::expressPrintDriversLicense);

        if ($this->authed->userType == 3) {
            $result =  $result == true ? false : true;
        }
        return $this->toSuccess($result);
    }

}
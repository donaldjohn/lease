<?php
namespace app\services\data;



use app\common\errors\AppException;
use app\models\service\AppList;
use app\models\service\AppUmeng;
use app\models\users\Institution;
use app\models\users\OperatorApp;

class AppData extends \Phalcon\Di\Injectable
{

    /**
     * 设备类型表
     * @var array
     */
    private static $APP_TYPES = ['android' => 1, 'ios' => 2];

    public function getParentOperatorInsId() {
        // 获取包名
        $packageName = $this->request->getHeader('packageName');
        // 设备类型
        $type = $this->request->getHeader('type');
        $deviceType = self::$APP_TYPES[strtolower($type)];
        $appUmeng = AppUmeng::findFirst(['conditions' => 'package_name = :package_name: and app_type = :app_type: and is_delete = 1','bind' => ['package_name' => $packageName,'app_type' => $deviceType]]);
        if ($appUmeng == false) {
            throw new AppException([500,'当前APP服务开小差了，请耐心等待']);
        }
        $appId = $appUmeng->getAppId();
        $operatorApp = OperatorApp::find(['conditions' => 'app_id = :appId:','bind' => ['appId' => $appId]])->getFirst();
        if (isset($operatorApp->ins_id) && $operatorApp->ins_id > 0) {
            return $operatorApp->ins_id;
        } else {
            throw new AppException([500,'当前APP服务开小差了，请耐心等待']);
        }

    }


    /**
     * @param $insId
     * @param $userType
     * 运营商获取上级ID （加盟商）
     */
    public function getParentInsId($insId,$userType) {
        if ($insId > 0 && $userType == 9) {
            $ins = Institution::findFirst($insId);
            if ($ins) {
                return $ins->getParentId();
            }
        }
        return null;
    }


    /**
     * @param $insId
     */
    public function getAppCodeByStoreInsId($insId) {
        $model = $this->modelsManager->createBuilder()
            ->columns('oa.app_id')
            ->addfrom('app\models\users\Institution','i')
            ->leftJoin('app\models\users\Institution',"i1.id =i.parent_id",'i1')
            ->leftJoin('app\models\users\Institution',"i2.id =i1.parent_id",'i2')
            ->join('app\models\users\OperatorApp',"oa.ins_id = i2.id",'oa')
            ->where('i.id = :id:',['id' => $insId])
            ->getQuery()
            ->getSingleResult();
        if ($model && $model->app_id > 0 ) {
            $app = AppList::findFirst($model->app_id);
            if ($app) {
                return $app->getAppCode();
            }
        }
        throw new AppException([500,'当前门店所属加盟商未绑定APP']);
    }


}
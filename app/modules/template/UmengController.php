<?php
namespace app\modules\template;

use app\models\service\AppUmeng;
use app\modules\BaseController;

class UmengController extends BaseController {

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * umeng列表
     */
   public function PageAction()
   {
       $app_name = $this->request->getQuery('app_name','string',null,true);
       $app_code = $this->request->getQuery('app_code','string',null,true);
       $app_status = $this->request->getQuery('app_status','int',null,true);
       $pageNum = $this->request->getQuery('pageNum','int',1,true);
       $pageSize = $this->request->getQuery('pageSize','int',20,true);
       $umeng = $this->templateData->getUmengPage($pageNum,$pageSize,$app_name,$app_code,$app_status);
       return  $this->toSuccess($umeng['data'],$umeng['meta']);
   }

   public function OneAction($id)
   {
       $umeng = AppUmeng::query()
           ->columns('app\models\service\AppUmeng.id,umeng_name,app_id,package_name,app\models\service\AppUmeng.app_type,appkey,mastersecret,app\models\service\AppUmeng.app_status,a.app_name,a.app_code')
           ->leftJoin('app\models\service\AppList','app_id = a.id','a')
           ->andWhere('app\models\service\AppUmeng.is_delete = 1 and app\models\service\AppUmeng.id = :id:',['id' => $id])
           ->execute()->getFirst();
       return $this->toSuccess($umeng);
   }

   public function CreateAction()
   {
       $json = $this->request->getJsonRawBody(true);
       $umeng = new AppUmeng();
       $json['is_delete'] = 1;
       $umeng->assign($json);

       if ($umeng->save() == false) {
           return $this->toError(500,$umeng->getMessages()[0]->getMessage());
       }
       return $this->toSuccess($umeng);
   }


   public function UpdateAction($id)
   {
       $json = $this->request->getJsonRawBody(true);
       $umeng = AppUmeng::findFirst($id);
       unset($json['is_delete']);
       unset($json['create_at']);
       $umeng->assign($json);
       if ($umeng->update() == false) {
           return $this->toError(500,$umeng->getMessages()[0]->getMessage());
       }
       return $this->toSuccess($umeng);

   }


   public function DeleteAction($id)
   {
       $umeng = AppUmeng::findFirst($id);
       if ($umeng == false) {
           return $this->toError(500,'暂无此数据!');
       }
       if ($umeng->getAppStatus() != 2) {
           return $this->toError(500,'当前数据状态无法删除!');
       }

       $umeng->setIsDelete(2);

       if ($umeng->update() == false) {
           return $this->toError(500,'删除失败!');
       }
       return $this->toSuccess(true);

   }

}
<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/6/15
 * Time: 14:00
 */
namespace app\modules\home;

use app\common\errors\DataException;
use app\models\service\Edition;
use app\modules\BaseController;
use Phalcon\Config;

class AppController extends BaseController
{
    /**
     * 检查更新版本
     */
    public function CheckverAction()
    {
        $ver = $this->request->getHeader('ver');
        $packageName = $this->request->getHeader('packageName');
        $type = Edition::GetEquipmentTypeCode($this->request->getHeader('type'));
        $verRes = Edition::findFirst([
            'edition_num = :edition_num: and equipment = :equipment: and package_name = :package_name:',
            'bind' => ['edition_num'=>$ver, 'equipment'=>$type, 'package_name'=>$packageName],
        ]);
        // 默认不提醒
        $data = [
            'tip' => false,
            'update' => false,
        ];
        if (false===$verRes){
            $data['msg'] = '未认可的版本请求';
            return $this->toSuccess($data);
        }
        $verRes = $verRes->toArray();
        if (1 == $verRes['edition_type']){
            return $this->toSuccess($data);
        }
        $newRes = Edition::findFirst([
            'equipment = :equipment: and package_name = :package_name:',
            'bind' => ['equipment'=>$type, 'package_name'=>$packageName],
            'order' => 'create_time DESC',
        ]);
        $data['tip'] = true;
        // 新版本信息对象
        $data['newVer'] = $newRes->toArray();
        $data['newVer']['apkName'] = 'download.apk'; // 安卓机制兼容
        // 强制更新
        if (3 == $verRes['edition_type']){
            $data['update'] = true;
        }
        return $this->toSuccess($data);
    }

    /**
     * 创建版本
     */
    public function CraeteAction()
    {
        if (2008!=$this->request->getHeader('ver')){
            return;
        }
        $request = $this->request->getJsonRawBody(true);
        $request['edition_type'] = 1;
        $request['create_time'] = time();
        $res = (new Edition())->save($request);
        if (false===$res){
            return $this->toError();
        }
        return $this->toSuccess();
    }

    /**
     * 变更版本状态
     */
    public function UpverAction($id)
    {
        if (2008!=$this->request->getHeader('ver')){
            return;
        }
        $request = $this->request->getJsonRawBody(true);
        // 更新状态校验
        if (!isset($request['edition_type']) || !in_array($request['edition_type'], [1,2,3])){
            return $this->toError('参数错误');
        }
        // 查询已有数据
        $oldRes = Edition::findFirst([
            'id = :id:',
            'bind' => ['id'=>$id]
        ]);
        $data = [
            'id' => $id,
            'edition_type' => $request['edition_type'],
        ];
        // 预留更新所有数据操作
        if (isset($request['upall']) && 1==$request['upall'])
        {
            unset($request['upall']);
            $data = array_merge($data, $request);
        }
        $data = array_merge($oldRes->toArray(), $data);
        $res = $oldRes->save($data);
        if (false===$res){
            return $this->toError();
        }
        return $this->toSuccess();
    }

    public function ListAction()
    {
        $where = [];
        if (!empty($_GET['package_name'])){
            if (is_array($_GET['package_name'])){
                $where['package_name'] = ['IN', $_GET['package_name']];
            }else{
                $where['package_name'] = $_GET['package_name'];
            }
        }
        $verRes = Edition::arrFind($where,'and',[
            'order' => 'edition_type, id DESC',
        ]);
        return $this->toSuccess($verRes);
    }
}
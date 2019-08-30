<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/28
 * Time: 14:54
 */
namespace app\modules\charge;

use app\common\library\QqMapService;
use app\models\charge\ChargeDevice;
use app\models\charging\BoxSocketNewchongdian;
use app\models\dispatch\Site;
use app\models\service\Area;
use app\modules\BaseController;
use Phalcon\Acl\Exception;

class DeviceController extends BaseController
{
    /**
     * 设备列表
     */
    public function ListAction()
    {
        $pageSize = intval($this->request->get('pageSize', null, 20));
        $pageNum = intval($this->request->get('pageNum', null, 1));
        $search = $this->request->get('search');
        $model = ChargeDevice::query();
        $model->where('is_delete = :is_delete:', [ 'is_delete' => 0]);
        if ($search) {
            $model->andWhere('identifier LIKE :identifier: OR ssid LIKE :ssid: 
            OR address LIKE :address:',  $parameters = [
                'identifier' => '%'. $search. '%',
                'ssid' => '%'. $search. '%',
                'address' => '%'. $search. '%',
            ]);
        }
        //TODO 暂时获取总的列表数量
        $count = clone $model;
        $count = $count->columns('id')->execute()->toArray();
        //获取列表数据
        $data = $model->columns('id,identifier, ssid, address, lat, lng, active_time, communicate_time, bind_site, total_num')
            ->orderBy('id desc')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->execute()
            ->toArray();
        $site = [];
        $item = [];
        foreach ($data as $key => &$val) {
            $val['active_time'] =  $val['active_time'] ? date('Y-m-d H:i:s',  $val['active_time']) : '--';
            // todo 使用情况及站点信息
            $ssid = ChargeDevice::encodeSsid($val['ssid']);
            $current = (new BoxSocketNewchongdian())->getBox($ssid);
            if ($current) {
                $val['rate'] = $current['num']. '/' .$val['total_num'];
            } else {
                $val['rate'] = '0/' .$val['total_num'];
            }
            $val['communicate_time'] = $current['time'] ? $current['time'] : '--';
            if (!isset($site['bind_site'])) {
                $result = Site::findFirst($val['bind_site']);
                $site[$val['bind_site']] = $result ? $result->site_name : '--';
            }
            $val['site_name'] = $site[$val['bind_site']];
            $item[] = $val;
        }
        return $this->toSuccess($item, ['pageNum'=> $pageNum, 'total' => count($count), 'pageSize' => $pageSize]);
    }
    /**
     * 创建一个设备
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function CreateAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'identifier', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'ssid', 'type' => 'string', 'parameter' => ['default' => true]],
            ['key' => 'bind_site', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'address', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'lng', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'lat', 'type' => 'string', 'parameter' => ['default' => true, ]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);//print_r($result);exit;
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data['create_at'] = time();
        $res = QqMapService::getLocation([ "location" => $data['lat'] . ',' .$data['lng']]);
        if (isset($res['region_id'])) {
            $res = Area::getThreeRegion($res['region_id']);
            $data = array_merge($data, $res);
        }
        $model = new ChargeDevice();
        try {
            if ($model->save($data) === false) {
                $messages = $model->getMessages();
                $msg = '';
                foreach ($messages as $message) {
                    $msg = $message->getMessage();
                }
                return $this->toError('500', $msg);
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $msg = '手机号码重复';
            }
            return $this->toError('500', $msg);
        }

        return $this->toSuccess();
    }

    /**
     * 修改设备信息
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function UpdateAction()
    {
        $data = $this->request->getJsonRawBody(true);
        if (!$data) {
            return $this->toError('500', '未修改任何数据');
        }
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
            ['key' => 'ssid', 'type' => 'string', 'parameter' => ['default' => false]],
            ['key' => 'bind_site', 'type' => 'number', 'parameter' => ['default' => false]],
            ['key' => 'address', 'type' => 'string', 'parameter' => ['default' => false, ]],
            ['key' => 'lat', 'type' => 'string', 'parameter' => ['default' => false, ]],
            ['key' => 'lng', 'type' => 'string', 'parameter' => ['default' => false, ]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data['update_at'] = time();
        $model = ChargeDevice::findFirst($data['id']);
        if (!$model) {
            return $this->toError('500', '未找到此设备信息');
        }

        if (isset($data['lat']) && isset($data['lng'])) {
            $res = QqMapService::getLocation([ "location" => $data['lat'] . ',' .$data['lng']]);
            if (isset($res['region_id'])) {
                $res = Area::getThreeRegion($res['region_id']);
                $data = array_merge($data, $res);
            }
        }

        unset($data['id']);
        try {
            if ($model->save($data) === false) {
                $messages = $model->getMessages();
                $msg = '';
                foreach ($messages as $message) {
                    $msg = $message->getMessage();
                }
                if (strpos($msg, 'Duplicate') !== false) {
                    $msg = '手机号码重复';
                }
                return $this->toError('500', $msg);
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $msg = '手机号码重复';
            }
            return $this->toError('500', $msg);
        }

        return $this->toSuccess();
    }

    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function DeleteAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $model = ChargeDevice::findFirst($data['id']);
        if (!$model) {
            return $this->toError('500', '未找到此设备信息');
        }
        if ($model->save(["is_delete" => 1, "update_at" => time()]) === false) {
            $messages = $model->getMessages();
            $msg = '';
            foreach ($messages as $message) {
                $msg = $message->getMessage();
            }
            return $this->toError('500', $msg);
        }
        return $this->toSuccess();
    }

    /**
     *
     */
    public function DetailAction()
    {
        $ssid = $this->request->get('ssid');
        $ssid = ChargeDevice::encodeSsid($ssid);
        $conditions = "SSID = :SSID:";
        $parameters = ['SSID' => $ssid];
        $result = BoxSocketNewchongdian::find([
            $conditions,
            'bind' => $parameters,
            "columns" => "ID,DIANYA,DIANLIU,WEIDU,SSID,DEVONE,CHONDIAN,GUZHANG,TIMEOUT,IFHAVE,CREATETIME",
        ]);
        return $this->toSuccess($result);
    }

    /**
     *
     */
    public function SiteAction()
    {
        $conditions = "site_status = :site_status:";
        $parameters = ['site_status' => 1];
        $model = Site::find(
            [$conditions,
            'bind' => $parameters,
            "columns" => "id,site_name",]
        );
        return $this->toSuccess($model);
    }
}
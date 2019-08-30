<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/8/29
 * Time: 9:30
 */
namespace app\modules\charge;

use app\models\charging\BoxSocketChongdian;
use app\modules\BaseController;

class LogController extends BaseController
{
    /**
     * 获取充电桩的日志消息
     */
    public function ListAction()
    {
        $pageSize = intval($this->request->get('pageSize', null, 10));
        $pageNum = intval($this->request->get('pageNum', null, 1));
        $ssid = $this->request->get('search');
        $time = $this->request->get('time');
        $model = BoxSocketChongdian::query();
        if ($ssid && $time) {
            $time = explode('-', $time);
            $model->where('SSID LIKE :SSID: AND CREATETIME >= :begin: AND CREATETIME <= :end:',
                $parameters = ['SSID' => '%'. $ssid. '%','begin' => date('Y-m-d H:i:s', $time[0]), 'end' => date('Y-m-d H:i:s', $time[1])]);
        } else if ($ssid) {
            $model->where('SSID like :ssid:', $parameters = ['ssid' =>  '%'. $ssid. '%',]);
        } else if ($time) {
            $time = explode('-', $time);
            $model->where('CREATETIME >= :begin: AND CREATETIME <= :end:',
                $parameters = ['begin' => date('Y-m-d H:i:s', $time[0]), 'end' => date('Y-m-d H:i:s', $time[1])]);
        }
        //TODO 暂时获取总的列表数量
        $count = clone $model;
        $count = $count->columns('count(ID) as count')->execute()->toArray();
        if ($count && isset($count[0]) && isset($count[0]['count'])) {
            $total = $count[0]['count'];
        } else {
            $total = 0;
        }
        //获取列表数据
        $data = $model->columns('ID,SSID,MODE, DEVTYPE, DEVONE,GUINO,DIANLIU,DIANYA,CHONDIAN,XUHANG,GUZHANG,WEIDU,IFHAVE,CREATETIME')
            ->orderBy('ID desc')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->execute()
            ->toArray();
        return $this->toSuccess($data, ['pageNum'=> $pageNum, 'total' => $total, 'pageSize' => $pageSize]);
    }
}
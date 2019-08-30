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

class TrafficController extends BaseController
{
    public function ListAction() {
//        $indexText = $this->request->getQuery('indexText','string',null);
//        if (!is_null($indexText))0
//            $json['indexText'] = $indexText;
        $json['pageNum'] = $this->request->getQuery('pageNum','int',1);
        $json['pageSize'] = $this->request->getQuery('pageSize','int',20);
        $result = $this->userData->common($json,$this->Zuul->biz,"16002");
        $pageInfo = $result['pageInfo'];
        $result = $result['data'];
        if ($result == null) {
            return $this->toSuccess($result,$pageInfo);
        }
        foreach ($result as $key =>  $item) {
            $result[$key]['createTime'] = !empty($result[$key]['createTime']) ? date('Y-m-d H:i:s',$result[$key]['createTime']) : '-';
           // $result[$key]['updateAt'] = !empty($result[$key]['updateAt']) ? date('Y-m-d H:i:s',$result[$key]['updateAt']) : '-';
        }
        return $this->toSuccess($result,$pageInfo);
    }
}
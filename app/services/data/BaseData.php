<?php
namespace app\services\data;

use app\common\errors\AppException;
use app\common\errors\AuthenticationException;
use app\common\errors\AuthorizationException;
use app\common\errors\DataException;
use phpDocumentor\Reflection\Types\Object_;

class BaseData extends \Phalcon\Di\Injectable
{
    /**
     * @param $json
     * @param $url
     * @param $code
     * @return mixed
     * @throws DataException
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 获取数据公共方法
     */
    public function common($json,$url,$code,$name = 'data')
    {
        $params = [
            'code' => $code,
            'parameter' => (Object)$json
        ];
        $result = $this->curl->httpRequest($url,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content'][$name]))
            return ['data' => '', 'pageInfo' => '','msg' => ''];
        if (isset($result['content']['pageInfo'])) {
            $pageInfo = $result['content']['pageInfo'];
        } else {
            $pageInfo = '';
        }
        return ['data' => $result['content'][$name], 'pageInfo' => $pageInfo,'msg' => $result['msg']];
    }


    public function postCommon($json,$url,$code)
    {
        $params = [
            'code' => $code,
            'parameter' => (Object)$json
        ];
        $result = $this->curl->httpRequest($url,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        return ['data' => $result['content'],'msg' => $result['msg']];
    }

    /**
     * @param $json
     * @param $url
     * @return array
     * @throws AuthenticationException
     * 只为token是否存在redis使用
     * 区分服务不可用还是token不存在
     */
    public function postCommon2($json,$url)
    {

        $result = $this->curl->httpRequest($url,$json,"post");

        if (isset($result['msg']) && $result['msg'] == "no permission") {
            throw new AuthenticationException([401,"该用户已被登入！"]);
        }
        if ($result['statusCode'] != '200') {
            throw new AppException([200, $result['msg']]);
        }
        return ['data' => $result['content'],'msg' => $result['msg']];
    }



    public function getTypeName($id)
    {
        $params = ["code" => "10099", 'parameter'=> ['id'=> $id]];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            $result = $result['content']['types'][0];
            return $result['typeName'];
        } else {
            return false;
        }
    }

    /**
     * 由于分页返回的数据为对象，之后需要转成数组
     */
    protected function dataIntegration($pages)
    {
        $meta = array();
        $data = array();
        if ($pages) {
            //$meta['first'] = $pages->first;
            // $meta['before'] = $pages->before;
            $meta['pageNum'] = $pages->current;
            //$meta['next'] = $pages->next;
            //$meta['last'] = $pages->last;
            //$meta['total_pages'] = $pages->total_pages;
            $meta['total'] = $pages->total_items;
            $meta['pageSize'] = $pages->limit;

            if (is_array($pages->items)) {
                $data = $pages->items;
            } else {
                $data = $pages->items->toArray();
            }
        }
        $result = ['meta' => $meta, "data" => $data];
        return $result;
    }
}
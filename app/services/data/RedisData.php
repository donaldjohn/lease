<?php
namespace app\services\data;
use app\common\errors\DataException;



// Redis存取类
class RedisData extends BaseData
{
    // 键名前缀
    private $Prefix = 'PHP_';

    /**
     * 存储
     * @param $key 键名
     * @param $value 值
     * @param  $time 有效期 默认24小时
     * @return bool 是否成功
     */
    public function set($key, $value, $time=86400)
    {
        // 增加前缀防止和微服务缓存产生冲突
        $key = $this->Prefix.$key;
        // 调用Redis服务
        $result = $this->curl->httpRequest($this->Zuul->redisSetValueTimeOut,[
            'key' => $key,
            'json' => $value,
            'timeOut' => $time,
            'timeType' => 'second',
        ],"post");
        // 判断结果
        if (!isset($result['statusCode']) || 200!=$result['statusCode']){
            return false;
        }
        return true;
    }

    /**
     * 获取
     * @param $key 键名
     * @param bool $UsePre
     * @return 失败或结果
     */
    public function get($key, $UsePre = true)
    {
        // 增加前缀防止和微服务缓存产生冲突
        if ($UsePre) $key = $this->Prefix.$key;
        // 调用Redis服务
        $result = $this->curl->httpRequest($this->Zuul->redis,[
            'key' => $key,
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || 200!=$result['statusCode']){
            return false;
        }
        // 返回结果
        return $result['content']['data'];
    }

    /**
     * 删除
     * @param $key 键名
     * @param bool $UsePre
     * @return bool
     */
    public function del($key, $UsePre = true)
    {
        // 增加前缀防止和微服务缓存产生冲突
        if ($UsePre) $key = $this->Prefix.$key;
        // 调用Redis服务
        $result = $this->curl->httpRequest($this->Zuul->redisDel,[
            'key' => $key,
        ],"post");
        // 失败返回
        if (!isset($result['statusCode']) || 200!=$result['statusCode']){
            return false;
        }
        return true;
    }
}

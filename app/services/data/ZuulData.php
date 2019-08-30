<?php
namespace app\services\data;

class ZuulData extends BaseData
{
    private $zuulBeseUrl = null;

    private $zuulConfig = null;

    /**
     * 初始化zuul基础URL
     */
    public function __construct()
    {
        $this->zuulBeseUrl = $this->config->ZuulBaseUrl;
        $this->zuulConfig = $this->config->zuul->toArray();
    }

    /**
     * 以属性形式获取接口地址
     * @param string $name
     * @return string|void
     */
    public function __get($name)
    {
        // 获取框架注入属性
        if (!isset($this->zuulConfig[$name])){
            return parent::__get($name);
        }
        // 兼容当前数据
        $data = [
            $this->zuulBeseUrl.$this->zuulConfig[$name]['uri'],
            $this->zuulConfig[$name]['code'],
        ];
        return $data;
    }

    /**
     * 以方法形式获取接口细节【code等】
     * @param $name
     * @param $arguments
     * @return string|void
     */
    public function __call($name, $arguments)
    {
        if (isset($arguments[0])){
            return $this->zuulConfig[$name][$arguments[0]];
        }
        // 未指定获取参数，则返回拼接好的URL
        return $this->zuulBeseUrl.$this->zuulConfig[$name]['uri'];
    }
}
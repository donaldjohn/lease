<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: WxPayConfig.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\services;

use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\core\ServicesInterface;

class WxPayConfig implements ServicesInterface
{
    public function register(FactoryDefault $di, Config $config)
    {
        // 微信配置预处理日志文件
        $di->setShared('WxPayConfig', function () use ($config) {
            // 获取配置文件
            $WxPayConfig = $config->wxpay->toArray();
            // 解析配置中的日志路径
            $info = pathinfo($WxPayConfig['log']['file']);
            // 按月定义日志文件
            $WxPayConfig['log']['file'] = $info['dirname'].'/'.$info['filename'].'-'.date('Ym', time()).'.'.$info['extension'];
            // 将配置存入属性
            return $WxPayConfig;
        });
    }
}
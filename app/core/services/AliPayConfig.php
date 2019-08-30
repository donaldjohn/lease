<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: AliPayConfig.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\services;

use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use app\core\ServicesInterface;
class AliPayConfig implements ServicesInterface
{

    public function register(FactoryDefault $di, Config $config)
    {
        // 支付宝配置预处理日志文件
        $di->setShared('AliPayConfig', function () use ($config) {
            // 获取配置文件
            $AliPayConfig = $config->alipay->toArray();
            // 解析配置中的日志路径
            $info = pathinfo($AliPayConfig['log']['file']);
            // 按月定义日志文件
            $AliPayConfig['log']['file'] = $info['dirname'].'/'.$info['filename'].'-'.date('Ym', time()).'.'.$info['extension'];
            // 将配置存入属性
            return $AliPayConfig;
        });
    }
}
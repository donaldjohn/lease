<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: DI.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core;

use Phalcon\DI\FactoryDefault;
use Phalcon\Config;


/**
 * Class DI
 * @package App
 */
class DI
{
    protected $di;
    protected $config;

    /**
     * DI constructor.
     * @param Config $config
     * 初始化构造器注入核心类和数据类
     */
    public function __construct(FactoryDefault $di, Config $config)
    {
        $this->di = $di;
        $this->config = $config;
        $this->register();
        $this->dataRegister();
    }

    /**
     * 注入核心类,由于需要特别定制,需要创建每个类
     */
    protected function register()
    {
        foreach ($this->config->services->base as $service) {
            $service = (new $service);
            $service->register($this->di, $this->config);
        }
    }

    /**
     * 定义数据类.数据层定义.免去每次单独定义
     */
    protected function dataRegister()
    {
        foreach ($this->config->services->data as $key => $service) {
            $service = new $service;
            $this->di->setShared($key, function() use ($service){
                return $service;
            });
        }
    }

    public function getDI()
    {
        return $this->di;
    }
}

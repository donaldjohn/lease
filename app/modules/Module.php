<?php
namespace app\modules;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

/**
 * 所有 modules 需要复制这个文件
 */
class Module implements ModuleDefinitionInterface
{
    public function registerAutoloaders(DiInterface $di = null)
    {

    }

    public function registerServices(DiInterface $di)
    {

    }
}
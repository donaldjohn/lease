<?php
namespace app\modules;

use Phalcon\Mvc\Router\Exception;

class ModuleRouter extends \Phalcon\Mvc\Router\Group
{
    public function __construct($prefix, $paths = null)
    {
        $this->_prefix = $prefix;
        parent::__construct($paths);
    }

    /**
     * Examples:
     *      $this->addRoutes("/bases/{id:[0-9]+}", array (
     *          "GET"    => array ("controller" => "bases", "action" => "select"),
     *          "PUT"    => array ("controller" => "bases", "action" => "update"),
     *          "DELETE" => array ("controller" => "bases", "action" => "delete"),
     *      ));
     * @param $pattern
     * @param array $routes
     */
    protected function addRoutes($pattern, array $routes)
    {
        foreach ($routes as $method => $paths) {
            $this->add($pattern, $paths, $method);
        }
    }


    /**
     * 快捷路由
     * key 定义请求规则，空格分割 "请求方式 路由规则" "路由规则"(默认GET)
     * value ["控制器", "方法"]
     * $this->FastRoutes([
     *   '/MyServiceContract' => ['Contract', 'MyServiceContract'],
     *   'POST /RescindContract/{serviceContractId:[0-9]+}' => ['Contract', 'RescindContract'],
     * ]);
     * @param array $routes
     * @throws Exception
     */
    public function FastRoutes(array $routes)
    {
        foreach ($routes as $RequestDefinition => $ActionDefinition)
        {
            $RequestArr = explode(' ', $RequestDefinition);
            if (count($RequestArr) > 2){
                throw new Exception('路由定义不可有多个空格');
            }
            $method = isset($RequestArr[1]) ? $RequestArr[0] : 'GET';
            $URI = $RequestArr[1] ?? $RequestArr[0] ?? '/';
            $paths = $ActionDefinition;
            if (!isset($ActionDefinition['controller'])){
                $paths = [
                    'controller' => $ActionDefinition[0] ?? 'Index',
                    'action' => $ActionDefinition[1] ?? 'Index',
                ];
            }
            $this->addRoutes($URI, [$method => $paths]);
        }
    }
}
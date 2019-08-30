<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: DB.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\core\db;

use app\core\ServicesInterface;
use Phalcon\Config;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Db\Adapter;

class DB implements ServicesInterface
{
    public function register(FactoryDefault $di, Config $config)
    {
        // model事件日志
        $eventManager = null;
        if($config->app->debug) {
            $eventManager = new EventsManager();
            $logger = new FileAdapter($config->log->path . '/db.log');
            $logger->setFormatter(new \Phalcon\Logger\Formatter\Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
            $eventManager->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, Adapter $connection) use ($logger) {
                $sqlVariables = $connection->getSQLVariables();
                if (isset($sqlVariables)) {
                    $logger->info($connection->getSQLStatement() . ' PARAMS:' . json_encode($sqlVariables,JSON_UNESCAPED_UNICODE));
                } else {
                    $logger->info($connection->getSQLStatement());

                }
            });
        }
        // 初始化数据库
        $dbnames = $config->dw->dbnames->toArray();
        foreach ($dbnames as $k => $dbName){
            $this->di->setShared('dw_'.$k, function() use($config, $dbName, $eventManager) {
                $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
                    'host'      => $config->dw->host,
                    'username'  => $config->dw->username,
                    'password'  => $config->dw->password,
                    'dbname'    => $dbName,
                ]);

                if(!is_null($eventManager)) {
                    $connection->setEventsManager($eventManager);
                }
                return $connection;
            });
        }
    }
}
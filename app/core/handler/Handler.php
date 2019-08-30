<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (https://www.phalconphp.com)      |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Nikita Vershinin <endeveit@gmail.com>                         |
  |          Serghei Iakovlev <serghei@phalconphp.com>                     |
  +------------------------------------------------------------------------+
*/

namespace app\core\handler;

use app\common\errors\AppException;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Http\Response;
use Phalcon\Logger\Formatter;
use Phalcon\Logger;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\Formatter\Line as FormatterLine;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Formatter\Line;


/**
 * Class Handler
 * @package app\core\handler
 * 1.exception 1.继承自定义exception的错误走buslogger 3.不认识的走 系统错误
 * 2.error   系统错误
 * 3.shutdown 系统错误
 *
 *
 *
 */

class Handler
{
    /**
     * Registers itself as error and exception handler.
     *
     * @return void
     */
    public static function register($env)
    {
        switch ($env) {
            case 'prod':
            case 'rls':
            default:
                ini_set('display_errors', 0);
                error_reporting(0);
                break;
            case 'test':
            case 'dev':
                ini_set('display_errors', 1);
                error_reporting(-1);
                break;
        }

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!($errno & error_reporting())) {
                return;
            }

            $options = [
                'type'    => $errno,
                'message' => $errstr,
                'file'    => $errfile,
                'line'    => $errline,
                'isError' => true,
            ];

            static::handle(new Error($options));
        });

        set_exception_handler(function ($e) {
            /** @var \Exception|\Error $e */
            $options = [
                'type'        => $e->getCode(),
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'isException' => true,
                'exception'   => $e,
            ];

            static::handle(new Error($options));
        });

//        register_shutdown_function(function () {
//            if (!is_null($options = error_get_last())) {
//                static::handle(new Error($options));
//            }
//        });
    }

    /**
     * Logs the error and dispatches an error controller.
     *
     * @param Error $error
     */
    public static function handle(Error $error)
    {
        $di = Di::getDefault();
        $config = $di->getShared('config');
        $type = static::getErrorType($error->type());
        $messageInfo = "$type: {$error->message()} in {$error->file()} on line {$error->line()}";

        if (!$di instanceof DiInterface) {
            echo $messageInfo;
            return;
        }

        $busLogger = $di->get('busLogger');
        $zuul = $di->getShared('Zuul');
        //业务错误
        if($error->exception() instanceof AppException) {
            /**
             * 远程日志
             */
            $app = $di->getShared('app');
            //$dispatcher = $di->getShared('dispatcher');
            //$result = $dispatcher->getReturnedValue();
            $message = new \app\common\logger\business\Message();
            $message->bizModuleCode = 00000;
            $message->level = 'error';
            $message->desc = "业务错误";
            $message->timestamp = time();
            $message->requestId = $app->getRequestId();
            $params = $busLogger->getInParameters();
            $message->inParameter = json_encode($params,JSON_UNESCAPED_UNICODE);
            $message->outParameter = json_encode($messageInfo,JSON_UNESCAPED_UNICODE);
            $busLogger->setMessage($message);
            $busLogger->sendMessages($zuul->log);
            /**
             * 本地日志
             */
            $logger = $di->get('logger');
            $logger->error(json_encode($message,JSON_UNESCAPED_UNICODE));

            /**
             * 输出json
             */

        } else {
            /**
             * 远程日志
             */
            $sysLogger = $di->getShared('sysLogger');

            $message = new \app\common\logger\system\Message();
            $message->level = $type;
            $message->timestamp = time();
            $params = $sysLogger->getInParameters();
            $message->inParameter = json_encode($params,JSON_UNESCAPED_UNICODE);
            $message->desc = $error->message();
            $message->columnNumber = $error->line();
            $message->sourcePath = $error->file();
            $sysLogger->setMessage($message);
            if ($busLogger->messageCount() > 0) {
                $busLogger->sendMessages($zuul->log);
            }
            $sysLogger->sendMessages($zuul->log);


            /**
             * 本地日志
             */
            $logger = new FileAdapter($config->log->path . '/app-error.log');
            $logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
            $logger->error(json_encode($message,JSON_UNESCAPED_UNICODE));


        }

        /**
         * 输出错误信息
         */
        if ($error->isException()) {
            $e = $error->exception();
            if ($e instanceof AppException) {
                $statusCode =  self::getStatusCode($e);
                $httpCode = $e->getCode();
            } else {
                //其他异常
                $statusCode = 500;
                $httpCode = 500;
            }
        } else {
            //error
            $httpCode = 500;
            $statusCode= 500;
        }

        $response = new Response();
        $json = array (
            "statusCode" => $httpCode,
            "msg" => $error->message(),
            "content" => '',
        );
        self::addDebug($json, $error,$config->app->debug);
        $response->setStatusCode($statusCode);
        $response->setJsonContent($json, JSON_UNESCAPED_UNICODE);
        $response->sendHeaders();
        $response->sendCookies();
        $response->send();
        exit;
    }

    public static function getStatusCode(AppException $e)
    {
        return $e->getStatusCode();
    }

    public static function addDebug(&$json, Error $e, $force = false)
    {
        if ($force == false) return;

        $context = self::getRequestContext();
        if ($e->isException() == true) {
            $exception = self::getExceptionArray($e);
            $json["debug"] = array(
                "context" => $context,
                "exception" => $exception,
            );
        } else {
            $json["debug"] = array(
                "context" => $context,
            );
        }

    }

    public static function getExceptionArray(Error $e)
    {
        $result = array(
            'class'      => get_class($e->exception()),
            'file'       => $e->file(),
            'code'       => $e->exception()->getCode(),
            'msg'        => $e->message(),
            'line'       => $e->line()
        );
        $prev = $e->exception()->getPrevious();
        if (isset($prev)) {
            $options = [
                'type'        => $prev->getCode(),
                'message'     => $prev->getMessage(),
                'file'        => $prev->getFile(),
                'line'        => $prev->getLine(),
                'isException' => true,
                'exception'   => $prev,
            ];
            $result["prev"] = self::getExceptionArray(new Error($options));
        }
        return $result;
    }

    public static function getRequestContext()
    {
        $di = Di::getDefault();
        $request = $di->get('request');
        $dispatcher = $di->get('dispatcher');
        $result = array(
            'method'        => $request->getMethod(),
            'uri'           => $request->getURI(),
            'url'           => $di->get('app')->getRequestUrl(),
            'route'         => $dispatcher->getModuleName() . '::' . $dispatcher->getControllerName() . '.' . $dispatcher->getActionName(),
            'namespace'     => $dispatcher->getNamespaceName(),
            'agent'         => $request->getUserAgent()
        );
        return $result;
    }

    /**
     * Maps error code to a string.
     *
     * @param  integer $code
     * @return string
     */
//    public static function getErrorType($code)
//    {
//        switch ($code) {
//            case 0:
//                return 'Uncaught exception';
//            case E_ERROR:
//                return 'E_ERROR';
//            case E_WARNING:
//                return 'E_WARNING';
//            case E_PARSE:
//                return 'E_PARSE';
//            case E_NOTICE:
//                return 'E_NOTICE';
//            case E_CORE_ERROR:
//                return 'E_CORE_ERROR';
//            case E_CORE_WARNING:
//                return 'E_CORE_WARNING';
//            case E_COMPILE_ERROR:
//                return 'E_COMPILE_ERROR';
//            case E_COMPILE_WARNING:
//                return 'E_COMPILE_WARNING';
//            case E_USER_ERROR:
//                return 'E_USER_ERROR';
//            case E_USER_WARNING:
//                return 'E_USER_WARNING';
//            case E_USER_NOTICE:
//                return 'E_USER_NOTICE';
//            case E_STRICT:
//                return 'E_STRICT';
//            case E_RECOVERABLE_ERROR:
//                return 'E_RECOVERABLE_ERROR';
//            case E_DEPRECATED:
//                return 'E_DEPRECATED';
//            case E_USER_DEPRECATED:
//                return 'E_USER_DEPRECATED';
//        }
//
//        return $code;
//    }
    public static function getErrorType($code)
    {
        switch ($code) {
            case 0:
                return 'info';
            case E_ERROR:
                return 'error';
            case E_WARNING:
                return 'warning';
            case E_PARSE:
                return 'parse';
            case E_NOTICE:
                return 'notice';
            case E_CORE_ERROR:
                return 'error';
            case E_CORE_WARNING:
                return 'warning';
            case E_COMPILE_ERROR:
                return 'error';
            case E_COMPILE_WARNING:
                return 'warning';
            case E_USER_ERROR:
                return 'error';
            case E_USER_WARNING:
                return 'warning';
            case E_USER_NOTICE:
                return 'notice';
            case E_STRICT:
                return 'strict';
            case E_RECOVERABLE_ERROR:
                return 'error';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return $code;
    }

    /**
     * Maps error code to a log type.
     *
     * @param  integer $code
     * @return integer
     */
    public static function getLogType($code)
    {
        switch ($code) {
            case E_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_PARSE:
                return Logger::ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return Logger::WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
                return Logger::NOTICE;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return Logger::INFO;
        }

        return Logger::ERROR;
    }
}

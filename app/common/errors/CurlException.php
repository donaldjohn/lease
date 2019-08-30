<?php
namespace app\common\errors;

use app\common\library\HttpService;
use app\common\errors\AppException;
use Throwable;

class CurlException extends AppException
{
    public function __construct(array $error = null, Throwable $previous = null)
    {
        if ($error == null) $error = [500,"请求超时"];
        parent::__construct($error, HttpService::CURL_TIME_OUT, $previous);
    }
}
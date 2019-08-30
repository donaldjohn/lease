<?php
namespace app\common\errors;

use app\common\library\HttpService;
use app\common\errors\AppException;
use Throwable;

class AuthenticationException extends AppException
{
    public function __construct(array $error = null, Throwable $previous = null)
    {
        if ($error == null) $error = HttpService::STATUS_401;
        parent::__construct($error, HttpService::STATUS_401, $previous);
    }
}
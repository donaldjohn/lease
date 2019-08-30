<?php
namespace app\common\errors;

use app\common\library\HttpService;
use app\common\errors\AppException;
use Throwable;

class EmployException extends AppException
{
    public function __construct(array $error = null, Throwable $previous = null)
    {
        if ($error == null) $error = [4006,"another user used"];
        parent::__construct($error, HttpService::STATUS_401, $previous);
    }
}
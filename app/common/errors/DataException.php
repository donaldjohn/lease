<?php
namespace app\common\errors;

use app\common\library\HttpService;
use app\common\errors\AppException;
use Throwable;

class DataException extends AppException
{
    public function __construct(array $error = null, Throwable $previous = null)
    {
        if ($error == null) $error = HttpService::DATA_ERROR;
        parent::__construct($error, HttpService::DATA_ERROR, $previous);
    }
}
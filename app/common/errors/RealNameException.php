<?php
namespace app\common\errors;

use app\common\library\HttpService;
use app\common\errors\AppException;
use Throwable;

class RealNameException extends BaseException
{
    public static $REALCODE = ["4111","请先实名认证"];

    public function __construct(array $error = null, array $status = null, Throwable $previous = null)
    {
        parent::__construct(self::$REALCODE, $status,$previous);
    }
}
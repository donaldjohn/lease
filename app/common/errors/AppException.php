<?php
namespace app\common\errors;

use app\common\library\HttpService;
use Throwable;

class AppException extends \Exception
{
    protected $status = HttpService::STATUS_500;

    public function __construct(array $error = null, array $status = null, Throwable $previous = null)
    {
        if ($status != null) $this->status = $status;

        $code = HttpService::STATUS_UNKNOWN[0];
        $message = HttpService::STATUS_UNKNOWN[1];
        if ($error != null) {
            if (count($error) > 0) $code = $error[0];
            if ($status != null && count($status) > 0) {
                $code = $status[0];
            }
            if (count($error) > 1) $message = $error[1];
        }
        parent::__construct($message, $code, $previous);
    }

    protected function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatusCode()
    {
        return $this->status[0];
    }

    public function getStatusMessage()
    {
        return $this->status[1];
    }
}
<?php
namespace app\common\errors;

use app\common\library\HttpService;
use Throwable;

class BaseException extends \Exception
{
    protected $status = HttpService::STATUS_200;

    public function __construct(array $error = null, array $status = null, Throwable $previous = null)
    {
        parent::__construct($error[1], $error[0], $previous);
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
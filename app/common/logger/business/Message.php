<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: message.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\common\logger\business;


class Message {

    public $level;

    public $bizModuleCode;

    public $timestamp;

    public $requestId;

    public $desc;

    public $inParameter;

    public $outParameter;

    public $operater;

    public $operObject;

    public $objectType;

    public $sourcePath;

    public $customField;


    /**
     * @return array
     * 返回数组
     */
    public function getMessage()
    {
        $message = array();
        //return (array)$this;
        $message['level'] = $this->level;
        $message['bizModuleCode'] = $this->bizModuleCode;
        $message['timestamp'] = $this->timestamp;
        $message['requestId'] = $this->requestId;
        $message['desc'] = $this->desc;
        $message['inParameter'] = $this->inParameter;
        $message['operater'] = $this->operater;
        $message['operObject'] = $this->operObject;
        $message['objectType'] = $this->objectType;
        $message['sourcePath'] = $this->sourcePath;
        $message['customField'] = $this->customField;
        return $message;

    }





}
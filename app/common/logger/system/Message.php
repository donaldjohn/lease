<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: Message.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\common\logger\system;


class Message {

    /**
     * @var
     * 日志级别
     */
    public $level;

    /**
     * @var
     * 时间戳
     */
    public $timestamp;

    /**
     * @var
     * 描述说明
     */
    public $desc;

    /**
     * @var
     * 入参
     */
    public $inParameter;

    /**
     * @var
     * 错误代码位置
     */
    public $sourcePath;

    /**
     * @var
     * 代码行数
     */
    public $columnNumber;

    /**
     * @var
     * 自定义列
     */
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
        $message['timestamp'] = $this->timestamp;
        $message['desc'] = $this->desc;
        $message['inParameter'] = $this->inParameter;
        $message['sourcePath'] = $this->sourcePath;
        $message['columnNumber'] = $this->columnNumber;
        $message['customField'] = $this->customField;
        return $message;

    }





}
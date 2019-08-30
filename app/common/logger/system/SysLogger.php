<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: buslogger.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\common\logger\system;


use Phalcon\Di\Injectable;
use Phalcon\Logger\Formatter\Line;

class SysLogger extends Injectable
{
   protected $messages = [];

   private static $LOG_SYSTEM_CODE = 10003;


   public function setMessage(Message $message)
   {
       $this->messages[] = $message;
   }

   public function getMessage()
   {
       return $this->messages;
   }

   public function emptyMessage()
   {
       $this->messages = [];
   }

   public function messageCount()
   {
       return count($this->messages);
   }

    /**
     * 推送logger日志
     */
   public function sendMessages($url,$nativeLog = true)
   {
       $params = $this->getPostJson($this->messages);
       $ch = curl_init();
       $timeout = 2;
       $headers[] = 'Content-type:application/json';
       curl_setopt ($ch, CURLOPT_URL, $url[0]);
       curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch,CURLINFO_HEADER_OUT,true);
       curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
       curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);

       if(is_object($params) || is_array($params)){
           $params = json_encode($params, JSON_UNESCAPED_UNICODE);
       }
       // 关闭ssl证书检查
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
       curl_setopt($ch, CURLOPT_POST,true);
       curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
       $file_contents = curl_exec($ch);//获得返回值
       $curl_errno = curl_errno($ch);
       //$code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 获取返回的状态码
       curl_close($ch);
       $log = '===================curl==================='.PHP_EOL;
       $log .= $url[0].PHP_EOL;
       $log .= $params.PHP_EOL;
       $log .= ((string)$file_contents).PHP_EOL;
       /**
        * 数据获取失败curl相关问题
        */
       if ($curl_errno > 0) {
           $this->logger->begin();
           $this->logger->setFormatter(new Line("[%date%][%type%] %message%", "Y-m-d H:i:s"));
           $this->logger->error($log);
           $this->logger->commit();
       }
       return true;
       //return json_decode($file_contents,true);

   }


    /**
     * @param $messages
     * @return array
     * 根据接口要求整理数据格式
     */
   private function getPostJson($messages)
   {
       $json = [];
       $json['code'] = self::$LOG_SYSTEM_CODE;
       foreach ($messages as $message) {
            $json['parameter']['sysLogBuilders'][] = $message;
       }
       return $json;
   }

    public function getInParameters()
    {
        $querys = $this->request->getQuery();
        $json = $this->request->getJsonRawBody(true);
        if ($json == null) {
            return $querys;
        }
        $in = array_merge($querys,$json);
        return $in;

    }
}
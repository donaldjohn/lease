<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: ValidateService.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\services\validate;



use app\common\errors\AppException;
use Phalcon\Di\Injectable;
use Phalcon\Validation;
use Phalcon\Validation\Validator\File as FileValidator;

/**
 * Class ValidateService
 * @package app\services\validate
 *
 */
class ValidateService extends Validation
{

      public $data = [
          [
               'key' => '',  //后期兼容数组
               'type' => 'number',
               'parameter' => [
                   'default' => true,   //非空
                   'min' => '',        //最小长度
                   'max' => '',        //最大长度
                   //'float' => 'true',  //允许float
                   'in' => [1,2,3], //判断当前值是否在规定值内
                   'value' => '' ,      //判断是否和指定值相同
                   'not_in' => [], //判断当前值不在规定值内
               ]
          ],
          [
               'key' => '',  //后期兼容数组
               'type' => 'string',
               'parameter' => [
                   'type' => '', //alnum 数字和字母 ,alpha 字母,bigit 数字
                   'default' => true,   //非空
                   'message' => '',     //错误返回message
                   'min' => '',        //最小长度
                   'max' => '',        //最大长度
                   'in' => [1,2,3], //判断当前值是否在规定值内
                   'value' => '' ,      //判断是否和指定值相同
                   'not_in' => [], //判断当前值不在规定值内
                   'regex' => ''    //正则匹配
               ]
          ],
          [
              'key' => '',  //后期兼容数组
              'type' => 'email',
              'parameter' => [
                  'default' => true,   //非空
                  'message' => '',     //错误返回message
              ]
           ],
          [
              'key' => '',  //后期兼容数组
              'type' => 'ip',
              'parameter' => [
                  'default' => true,   //非空
                  'message' => '',     //错误返回message
              ]
          ],
          [
              'key' => '',  //后期兼容数组
              'type' => 'date',
              'parameter' => [
                  'default' => true,   //非空 非空验证
                  'format' => 'Y-m-d',
                  'message' => '',     //错误返回message
              ]
          ],
          [
              'key' => '',  //后期兼容数组
              'type' => 'file',
              'parameter' => [
                  'default' => true,   //非空
                  'message' => '',     //错误返回message
                  'maxSize' => '',
                  "allowedTypes" => [
                      "image/jpeg",
                      "image/png",
                  ],
                  "maxResolution"        => "800x600",
              ]
          ],

      ];


    /**
     * @param $fields
     * @param $json
     * @return array
     * @throws AppException
     * 用户输入验证
     * TODO: next get more key
     */
    public function myValidation($fields,$json)
    {

        $data = [];
        //check $fields is array
        if (!is_array($fields))
            return ['code' => false,'msg' => '错误的输入参数'];
        foreach($fields as $item) {
            if (!isset($item['key'])) {
                return ['code' => false,'msg' => '参数没有key'];
            }
            //判断值是否存在
            if (!isset($item['type']))
                return ['code' => false,'msg' => $item['key'].'参数没有type'];
            $type = $item['type'];
            if (!isset($item['parameter']))
                return ['code' => false,'msg' => $item['key'].'参数没有parameter'];
            $parameter = $item['parameter'];

            //绑定参数
            if (isset($json[$item['key']])) {
                $data[$item['key']] = $json[$item['key']];
            }
                //根据$type和$parameter里面的参数判断
            switch ($type) {
                case "number" :
                    $this->checkNumber($item['key'],$parameter);
                    break;
                case "string" :
                        $this->checkString($item['key'],$parameter);
                        break;
                case "email" :
                        $this->checkEmail($item['key'],$parameter);
                        break;
                case "ip" :
                        $this->checkIp($item['key'],$parameter);
                        break;
                case "date" :
                        $this->checkDate($item['key'],$parameter);
                        break;
                case "file" :
                        $this->checkFile($item['key'],$parameter);
                        break;
                default:
                        return ['code' => false,'msg' => '非法type,请检查'];
                }
            }

        return ['status' => true, 'content' => $data];
    }


    /**
     * @param $data
     * @return array
     * 获取验证错误message
     */
    public function messages($data)
    {
        $result_messages = [];
        $messages = $this->validate($data);
        if (count($messages)) {
            foreach ($messages as $item) {
                $result_messages[] = $item->getMessage();
            }
        } else {
            return true;
        }
        return $result_messages;
    }


    /**
     * @param $key
     * @param $parameter
     * @return bool
     * @throws AppException
     * 验证数字
     */
    private function checkNumber($key,$parameter) {
        //验证是否是数字
        $this->add($key, new Validation\Validator\Numericality([
            "message" => $key.'必须是number!',
        ]));


        //验证字段是否为必填
        if (isset($parameter['default']) && $parameter['default'] == true) {
            $this->add($key,new Validation\Validator\PresenceOf([
                "message" => $key.'必填!',
                'cancelOnFail' => true]));
        }
        //验证数字区间min-max
        if (isset($parameter['min']) && isset($parameter['max'])) {
            $this->add($key, new Validation\Validator\Between([
                "minimum" => (int)$parameter['min'],
                "maximum" => (int)$parameter['max'],
                "message" => $key.'的大小区间为['.$parameter['min'].'-'.$parameter['max'].']',
            ]));
        } elseif (isset($parameter['min'])) {
            $this->add($key, new Validation\Validator\Between([
                "minimum" => (int)$parameter['min'],
                "message" => $key.'大于'.$parameter['min'],
            ]));
        } elseif (isset($parameter['max'])) {
            $this->add($key, new Validation\Validator\Between([
                "maximum" => (int)$parameter['max'],
                "message" => $key.'小于'.$parameter['max'],
            ]));
        }
        if (isset($parameter['in']) && is_array($parameter['in'])) {
            $this->add($key, new Validation\Validator\InclusionIn([
                "message" => $key.'必须在数组:'.json_encode($parameter['in']).'里',
                "domain"  => $parameter['in'],
            ]));
        }
        if (isset($parameter['in']) && !is_array($parameter['in'])) {
            //报错非法传参
            throw new AppException([500,'参数$parameter[\'in\']类型错误']);
        }

        if (isset($parameter['not_in']) && is_array($parameter['not_in'])) {
            $this->add($key, new Validation\Validator\ExclusionIn([
                "message" => $key.'必须不在数组:'.json_encode($parameter['in']).'里',
                "domain"  => $parameter['not_in'],
            ]));
        }
        if (isset($parameter['not_in']) && is_array($parameter['not_in'])) {
            //报错非法传参
            throw new AppException([500,'参数$parameter[\'not_in\']类型错误']);
        }
        if (isset($parameter['value']) && !empty($parameter['value'])) {
            $this->add($key,new Validation\Validator\Identical([
                "accepted" => (int)$parameter['value'],
                "message" => $key.'值必须为:'.(int)$parameter['value'],
            ]));
        }
        return true;
    }


    /**
     * @param $key
     * @param $parameter
     * @return bool
     * @throws AppException
     * 验证字符串 alnum 数字和字母 ,alpha 字母,Digit 数字
     */
    private function checkString($key,$parameter) {

        //验证字段是否为必填
        if (isset($parameter['default']) && $parameter['default'] == true) {
            $this->add($key,new Validation\Validator\PresenceOf([
                "message" => $key.'必填!',
                'cancelOnFail' => true]));
        }

        if (isset($parameter['type']) && $parameter['default'] == 'alnum') {
            $this->add($key, new Validation\Validator\Alnum([
                'message' => $key.'必须为字母和数字'
            ]));
        }

        if (isset($parameter['type']) && $parameter['default'] == 'alpha') {
            $this->add($key,new Validation\Validator\Alpha([
                'message' => $key.'必须为字母'
            ]));
        }

        if (isset($parameter['type']) && $parameter['default'] == 'bigit') {
            $this->add($key,new Validation\Validator\Digit([
                'message' => $key.'必须为数字'
            ]));
        }



        //验证数字区间min-max
        if (isset($parameter['min']) && isset($parameter['max'])) {
            $this->add($key, new Validation\Validator\StringLength([
                "min" => (string)$parameter['min'],
                "max" => (string)$parameter['max'],
                "message" => $key.'的大小区间为['.$parameter['min'].'-'.$parameter['max'].']',
            ]));
        } elseif (isset($parameter['min'])) {
            $this->add($key, new Validation\Validator\StringLength([
                "min" => (string)$parameter['min'],
                "message" => $key.'长度大于'.$parameter['min'],
            ]));
        } elseif (isset($parameter['max'])) {
            $this->add($key, new Validation\Validator\StringLength([
                "max" => (string)$parameter['max'],
                "message" => $key.'长度小于'.$parameter['max'],
            ]));
        }
        if (isset($parameter['in']) && is_array($parameter['in'])) {
            $this->add($key, new Validation\Validator\InclusionIn([
                "message" => $key.'必须在数组:'.json_encode($parameter['in']).'里',
                "domain"  => $parameter['in'],
            ]));
        }
        if (isset($parameter['in']) && !is_array($parameter['in'])) {
            //报错非法传参
            throw new AppException([500,'参数$parameter[\'in\']类型错误']);
        }

        if (isset($parameter['not_in']) && is_array($parameter['not_in'])) {
            $this->add($key, new Validation\Validator\ExclusionIn([
                "message" => $key.'必须不在数组:'.json_encode($parameter['in']).'里',
                "domain"  => $parameter['not_in'],
            ]));
        }
        if (isset($parameter['not_in']) && is_array($parameter['not_in'])) {
            //报错非法传参
            throw new AppException([500,'参数$parameter[\'not_in\']类型错误']);
        }
        if (isset($parameter['value'])) {
            $this->add($key,new Validation\Validator\Identical([
                "accepted" => (int)$parameter['value'],
                "message" => $key.'值必须为:'.(string)$parameter['value'],
            ]));
        }

        if (isset($parameter['regex'])) {
            $this->add($key,new Validation\Validator\Regex([
              "pattern" => $parameter['regex'],
              "message" =>  $key."数据非法",
            ]));
        }
        return true;
    }

    /**
     * @param $key
     * @param $parameter
     * @return bool
     * 验证邮箱
     */
    private function checkEmail($key,$parameter)
    {
        //验证字段是否为必填
        if (isset($parameter['default']) && $parameter['default'] == true) {
            $this->add($key,new Validation\Validator\PresenceOf([
                "message" => $key.'必填!',
                'cancelOnFail' => true]));
            $this->add($key, new Validation\Validator\Email([
                "message" => $key.'值必须为邮箱格式',
            ]));
        }

        return true;
    }

    /**
     * @param $key
     * @param $parameter
     * @return bool
     * 验证ip
     */
    private function checkIp($key,$parameter)
    {
        if (isset($parameter['default']) && $parameter['default'] == true) {
            $this->add($key,new Validation\Validator\PresenceOf([
                "message" => $key.'必填!',
                'cancelOnFail' => true]));
            $this->add($key,new Validation\Validator\IpValidator(
                ['message' => $key.'必须为ip格式']
            ));
        }
        return true;

    }

    private function checkDate($key,$parameter){
        //验证字段是否为必填
        if (isset($parameter['default']) && $parameter['default'] == true) {
            $this->add($key,new Validation\Validator\PresenceOf([
                "message" => $key.'必填!',
            'cancelOnFail' => true]));
        }





    }

    /**
     * @param $key
     * @param $parameter
     * @return bool
     * @throws AppException
     * 验证时间
     */
    private function checkFile($key,$parameter)
    {
        if (!isset($parameter['maxSize']))
            throw new AppException([500,'参数$parameter[\'maxSize\']类型错误']);
        if (!isset($parameter['allowedTypes']))
            throw new AppException([500,'参数$parameter[\'allowedTypes\']类型错误']);
        if (!isset($parameter['maxResolution']))
            throw new AppException([500,'参数$parameter[\'maxResolution\']类型错误']);

         $this->add($key, new FileValidator(
          [
              "maxSize"              => $parameter['maxSize'],
              "messageSize"          => "文件大小必须小于".$parameter['maxSize'],
              "allowedTypes"         => $parameter['allowedTypes'],
              "messageType"          => "文件格式必须为:".json_encode($parameter['allowedTypes']),
              "maxResolution"        => $parameter['maxResolution'],
              "messageMaxResolution" => "最大格式为:".$parameter['maxResolution'],
          ]));
         return true;
    }


}
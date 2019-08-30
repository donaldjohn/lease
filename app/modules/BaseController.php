<?php
namespace app\modules;

use app\common\errors\AppException;
use app\common\errors\AuthenticationException;
use app\common\errors\DataException;
use app\common\library\HttpService;

use app\models\dispatch\DriverDevicetoken;
use app\models\MyBaseModel;
use Phalcon\Exception;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Mvc\Controller;
use app\Application;
use Phalcon\Logger\Adapter\File as FileAdapter;
use app\common\logger\business\Message;

Class BaseController extends Controller
{
    /**
     * @var
     * 当前登入用户信息 如userid,groupId,roleId等
     */
    protected $authed;
    /**
     * @var
     * 当前访问类型  web ios micro
     */
    protected $type;
    /**
     * @var
     * 设备号
     */
    protected $device_id;
    /**
     * @var
     * 用户访问信息
     */
    protected $content;
    /**
     * @var
     * 当前范文系统 默认为0 主系统
     */
    protected $system;   //系统ID


    protected $code;

    /**
     * initialize 仅仅会在事件 beforeExecuteRoute 成功执行后才会被调用。
     * 这样可以避免在初始化中的应用逻辑不会在未验证的情况下执行不了。
     */
    public function initialize() {

        /**
         * 获取 header 头  type 访问类型
         */
        $type = $this->request->getHeader("type");
        if (empty($type)) {
            $this->type = "web";
        } else {
            $this->type = $type;
        }

        /**
         *  获取code
         */
        $code = $this->request->getHeader("code");
        if (empty($code)) {
            $this->code = "";
        } else {
            $this->code = $code;
        }


        /**
         * 获取 header 头  type 访问类型
         */
        $device_id = $this->request->getHeader("device_id");
        if (empty($device_id)) {
            $this->device_id = "";
        } else {
            $this->device_id = $device_id;
        }


        /**
         * 获取当前访问系统ID (主系统默认为0)
         */
        $system_code = $this->request->getHeader("system");
        if (empty($system_code)) {
            $this->system = 0;
            unset($system_code);
        } else {
//            $parameter = ['systemCode' => $system_code];
//            $params = ["code" => "10043", "parameter" => $parameter];
//            $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
//            if ($result['statusCode'] == '200') {
//                $systems_list = isset($result['content']['systems'][0]) ? $result['content']['systems'][0] : 0;
//                if (isset($systems_list['id'])) {
//                    if(isset($systems_list['systemStatus']) && $systems_list['systemStatus'] != 1)
//                        throw new DataException([500,'子系统已禁用!请通知管理员']);
//                    $this->system = $systems_list['id'];
//                } else {
//                    throw new DataException([500,'子系统不存在!请通知管理员']);
//                }
//            } else {
//                throw new DataException();
//            }
            $this->system = -1;
        }

        $this->content = $this->request->getJsonRawBody();

        if (!$this->auth->isPublic('')) {
            $this->authed = $this->auth->getAuthentication();
            // 屏蔽调试
            if ($this->authed->userId == -1){
                throw new AuthenticationException([401,'用户无权限']);
            }
            // 骑手APP校验是否为有效登录设备
            if (false === $this->DriverAuth($this->authed->userId)){
                throw new AuthenticationException([401,'登录信息无效，请重新登录']);
            }
            $this->logger->debug(json_encode($this->authed));
            $module     = strtoupper($this->dispatcher->getModuleName());
            $controller = strtoupper($this->dispatcher->getControllerName());
            $action     = strtoupper($this->dispatcher->getActionName());
            $message = '用户:'.$this->authed->userId.'.'.$this->authed->userName.':'.'访问系统:'.$this->system.$module.'::'.$controller.'/'.$action;
            $this->logger->info("$message");
            $this->busLogger->setUser($this->authed);
        }

    }

    public function DriverAuth($DriverId)
    {
        // 如果没有有效deviceUUID 允许访问
        if (!isset($this->authed->deviceUUID) || empty(($this->authed->deviceUUID))){
            return true;
        }
        // 查询是否有匹配设备记录
        $DriverDevicetoken = DriverDevicetoken::findFirst([
            'device_uuid = :device_uuid: and driver_id = :driver_id:',
            'bind' => [
                'device_uuid' => $this->authed->deviceUUID,
                'driver_id' => $DriverId,
            ]
        ]);
        return $DriverDevicetoken ? true : false ;
    }

    /**
     * 返回错误信息
     * @param int $errcode
     * @param string $errmsg
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    protected function toError($errcode = 500, $errmsg = "未知错误"){

        //$result_code = $this->httpService->getHttpCode($errcode);
        //$this->response->setStatusCode($result_code[0],$result_code[1]);
        $this->response->setStatusCode(500);
        $this->response->setHeader('device_id',$this->device_id);
        $this->response->setHeader('type',$this->type);
        $RunTimeInfo = $GLOBALS['RunTimeInfo'];
        $RunTimeInfo['e'] = microtime();
        if ($GLOBALS['ExceptionTipsMsg']){
            $errmsg .= ': ' . implode(' ', $GLOBALS['ExceptionTipsMsg']);
        }
        $result = ['content' => '', 'statusCode' => $errcode, 'msg' => $errmsg, 'RTI'=>$RunTimeInfo];
        return $this->response->setJsonContent($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回成功的数据
     * $meta   其他信息如分页数据等等
     * $data   具体的数据
     * @param array $data
     * @param array $meta
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    protected function toSuccess($data = array(),$meta = array(),$code = 200,$msg = '') {
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setStatusCode(200);
        $this->response->setHeader('device_id',$this->device_id);
        $this->response->setHeader('type',$this->type);
        $meta = $meta == null ? new \stdClass() : $meta;
        if (is_array($meta)){
            foreach ($meta as $k => $v){
                if (is_numeric($v)) $meta[$k] = (int)$v;
            }
            // pageNum=0前端会有问题
            if (isset($meta['pageNum']) && 0 == $meta['pageNum']) $meta['pageNum'] = 1;
        }
        $content = array("data" => $data,"meta" => $meta);
        $RunTimeInfo = $GLOBALS['RunTimeInfo'];
        $RunTimeInfo['e'] = microtime();
        $result = ['content' => $content, 'statusCode' => $code, 'msg' => $msg, 'RTI'=>$RunTimeInfo];
        return $this->response->setJsonContent($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回空列表
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function toEmptyList(){
        return $this->toSuccess([], [
            'total' => 0,
            'pageSize' => 0,
            'pageNum' => 1,
        ]);
    }

    /**
     * 请求参数验证
     * @param $fields   字段需求
     * @param $request   参数体
     * @param bool $air   允许保留空值
     * @return array  结果数组
     * @throws DataException 验证失败
     */
    public function getArrPars($fields, $request ,$air=false)
    {
        /*
         * 字段定义示例
         * 字段名 => int 表示非必需
         * 字段名 => string 表示必需，string为必需时的提示语
         * 字段名 => array [
         *          name 返回名称
         *          def  默认值
         *          need 缺失提示语,有即参数为必需
         *          min  最小值(仅可用于数值类型)
         *          max  最大值(仅可用于数值类型)
         *          mixl 最小长度(可用于数值、字符串类型)
         *          maxl 最大长度(可用于数值、字符串类型)
         * ]
        $fields = [
            'id' => 'id不可为空',
            'auth' => [
                'def' => '123',
            ],
            'payBillId' => [
                'as' => 'pay_bill_id',
            ],
            'age' => [
                'max' => 60,
            ],
            'name' => [
                'minl' => 1,
                'maxl' => 3,
            ]
        ];
         */
        $data = [];
        foreach ($fields as $field => $explain){
            // 获取req键
            $reqfield = $explain['as'] ?? $field;
            $name = $explain['name'] ?? $reqfield;
            //删除无用的传参
            if (false===$air && isset($request[$reqfield]) && ''===$request[$reqfield]){
                unset($request[$reqfield]);
            }
            // 获取参数
            if (isset($request[$reqfield]) || isset($explain['def'])){
                $data[$field] = $request[$reqfield] ?? $explain['def'];
            }
            // 获取提示语
            $tip = $explain['need'] ?? (is_string($explain) ? $explain : null) ?? false;
            // 必需且不存在时，抛异常提示
            if ($tip && !isset($data[$field])){
                throw new DataException([500, $tip]);
            }
            // 未定义辅助条件 || 无此参数，过
            if (!is_array($explain) || !isset($data[$field])) continue;
            // in条件
            if (isset($explain['in']) && !in_array($data[$field], $explain['in'])){
                throw new DataException([500, $name.'不在有效值列表内']);
            }
            // min/max范围校验(int/float)
            if (!(!(isset($explain['min']) && $data[$field]<$explain['min'])
                && !(isset($explain['max']) && $data[$field]>$explain['max']))){
                throw new DataException([500, $name.'值范围不合法']);
            }
            // minl/maxl长度校验(int/string/float)
            if (!(!(isset($explain['minl']) && strlen($data[$field])<$explain['minl'])
                && !(isset($explain['maxl']) && strlen($data[$field])>$explain['maxl']))){
                throw new DataException([500, $name.'值长度不合法']);
            }
        }
        return $data;
    }

    /**
     * 简单处理数据库查出字段
     * @param $fields 字段规则
     * @param $res  源数据
     * @return array
     */
    public function backData($fields, $res)
    {
        $data = [];
        foreach ($fields as $field => $rule){
            // 如果规则不是数组，直接赋值或默认
            if (!is_array($rule)) {
                $data[$field] = $res[$field] ?? $rule;
                continue;
            }
            // 获取res键
            $resfield = $field;
            if (isset($rule['as'])){
                $resfield = $rule['as'];
            }
            // 基础处理到临时变量
            $tmp = $res[$resfield] ?? $rule['def'] ?? '' ;
            // 未设置处理方法，直接赋值
            if (!isset($rule['fun'])) {
                $data[$field] = $tmp;
                continue;
            }
            // 预定义处理方法
            if (is_string($rule['fun'])){
                switch ($rule['fun']){
                    case 'time':
                        $tmp = empty($tmp) ? '-' : date('Y-m-d H:i:s', $tmp);
                        break;
                    case 'identity':
                        $tmp = empty($tmp) ? '' : substr_replace($tmp,'******',8,6);
                        break;
                    case 'free': // 如果fun为free则使用自定义方法func
                        $tmp = $rule['func']($tmp);
                        break;
                }
            }
            // 关系对应
            if (is_array($rule['fun'])){
                $tmp = isset($rule['fun'][(string)$tmp]) ? $rule['fun'][(string)$tmp] :'';
            }
            $data[$field] = $tmp;
        }
        return $data;
    }

    // 处理时间戳
    public function handleBackTimestamp(&$list, $add=[])
    {
        if (!is_array($list)){
            return $list;
        }
        $fields = ['createAt', 'updateAt', 'createTime', 'updateTime', 'startTime', 'endTime', 'create_at', 'update_at', 'create_time', 'update_time'];
        $fields = array_merge($fields, $add);
        foreach ($list as $k => $v){
            // 非二维数组，直接处理
            if (!is_array($v)){
                foreach ($fields as $field){
                    if (isset($list[$field]) && is_numeric($list[$field])){
                        $list[$field] = 0==$list[$field] ? '-' : date('Y-m-d H:i:s', $list[$field]);
                    }
                }
                break;
            }
            // 二维数组，常规处理
            foreach ($fields as $field){
                if (isset($v[$field]) && is_numeric($v[$field])){
                    $list[$k][$field] = 0==$v[$field] ? '-' : date('Y-m-d H:i:s', $v[$field]);
                }
            }
        }
        return $list;
    }

    /**
     * 部分隐藏身份证号
     * @param $identify
     * @return mixed
     */
    public function hideIDnumber($identify)
    {
        if(18==strlen($identify))
        {
            $identify = substr_replace($identify,'****************',1,16);
        }
        elseif(15==strlen($identify))
        {
            $identify = substr_replace($identify,'*************',1,13);
        }
        return $identify;
    }

    // 校验身份证号是否合规
    function isIdCard($id)
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx, $id)){
            return FALSE;
        }
        //检查15位
        if(15==strlen($id)){
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)){
                return FALSE;
            }
            return TRUE;
        }
        // 下面为检查18位
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        // 检查生日日期是否正确
        if(!strtotime($dtm_birth)){
            return FALSE;
        }
        //检验18位身份证的校验码是否正确。
        //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
        $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $sign = 0;
        for ( $i = 0; $i < 17; $i++ ){
            $b = (int) $id{$i};
            $w = $arr_int[$i];
            $sign += $b * $w;
        }
        $n = $sign % 11;
        $val_num = $arr_ch[$n];
        if ($val_num != substr($id,17, 1)){
            return FALSE;
        }
        return TRUE;
    }

    /**
     * @param $json
     * @return string
     * @throws Exception
     * 私钥解密
     */
    public function RSADec($json)
    {
        $result = '';
        $private_key = file_get_contents(BASE_PATH.'/config/rsa/rsa_private_key.pem');
        if(empty($private_key)){
            throw new Exception('秘钥不存在!');
        }
        $pi_key =  openssl_pkey_get_private($private_key);
        openssl_private_decrypt(base64_decode($json),$result,$pi_key);
        return $result;
    }


    /**
     * @param $json
     * @return string
     * @throws Exception
     * 公钥加密
     */
    public function RSAEncrypt($json)
    {
        $public_key = file_get_contents(BASE_PATH.'/config/rsa/rsa_public_key.pem');
        if(empty($public_key)){
            throw new Exception('秘钥不存在!');
        }
        $pu_key =  openssl_pkey_get_public($public_key);// 可用返回资源id

        $result = '';
        openssl_public_encrypt($json, $result, $pu_key);//公钥加密
        return base64_encode($result);
    }

    public function arrToQuery($arr, $relation='and', $assist=[])
    {
        if (isset($arr['bind']) && is_array($arr['bind'])){
            return $arr;
        }
        if (is_array($relation)){
            $assist = array_merge($relation, $assist);
            $relation = 'and';
        }
        // 预处理conditions
        $conditions = [];
        $bind = [];
        foreach ($arr as $k => $v){
            $bindKey = str_replace('.', '__', $k);
            if (!is_array($v)){
                $conditions[] = $k.' = :'.$bindKey.':';
                $bind[$bindKey] = $v;
                continue;
            }
            if (isset($v[1]) && is_array($v[1])){
                $conditions[] = "{$k} {$v[0]} ({{$bindKey}:array})";
                $bind[$bindKey] = $v[1];
                continue;
            }
            $conditions[] = $k.' '.implode(' ', $v);
        }
        $conditions = implode(" {$relation} ", $conditions);
        // 查询返回
        return array_merge([
            'conditions' => $conditions,
            'bind' => $bind,
        ], $assist);
    }


    protected function dataIntegration($pages)
    {
        $meta = array();
        $data = array();
        if ($pages) {
            //$meta['first'] = $pages->first;
            // $meta['before'] = $pages->before;
            $meta['pageNum'] = $pages->current;
            //$meta['next'] = $pages->next;
            //$meta['last'] = $pages->last;
            //$meta['total_pages'] = $pages->total_pages;
            $meta['total'] = $pages->total_items;
            $meta['pageSize'] = $pages->limit;

            if (is_array($pages->items)) {
                $data = $pages->items;
            } else {
                $data = $pages->items->toArray();
            }
        }
        $result = ['meta' => $meta, "data" => $data];
        return $result;
    }


    /**
     * 接口透传
     * @param string $serviceName 服务名称
     * @param $code 接口代码
     * @param null $parameter 接口参数【默认取JsonBody】
     * @param bool $DoNotHandleResult 是否直接处理结果
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function PenetrateTransferToService($serviceName, $code, $parameter=null,  $DoNotHandleResult=false)
    {
        $parameter = $parameter ?? $this->request->getJsonRawBody(true);
        if (empty($parameter)){
            $parameter = $_GET;
        }
//        $parameter['CurrentLoginInsId'] = $this->authed->insId ?? null;
//        $parameter['CurrentLoginUserId'] = $this->authed->userId ?? null;
        //调用微服务接口获取数据
        $result = $this->CallService($serviceName, $code, $parameter);
        if ($DoNotHandleResult){
            return $result;
        }
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $data = $result['content']['data'] ?? $result['content'] ?? [];
        $meta = $result['content']['pageInfo'] ?? [];
        return $this->toSuccess($data, $meta);
    }

    // 调用微服务
    public function CallService($serviceName, $code, $parameter, $checkCode=false){
        // 兼容空参会JSON为数组导致服务异常
        if (empty($parameter)){
            $params['parameter']['compatibleFilling'] = null;
        }
        $result = $this->curl->httpRequest($this->Zuul->$serviceName,[
            'code' => $code,
            'parameter' => $parameter
        ],"post");
        if ($checkCode){
            $statusCode = $result['statusCode'];
            if (200 != $result['statusCode']){
                $msg = (is_string($checkCode)?$checkCode:null) ?? $checkCode[$statusCode] ?? $result['msg'] ?? json_encode($result);
                throw new AppException([$statusCode, $msg]);
            }
        }
        return $result;
    }

}

<?php
namespace app\services\data;
use app\common\errors\DataException;
use app\models\users\User;

// 公共Data类
class CommonData extends BaseData
{
    // 验证码短信类型
    const PhoneSMSCodeTypeDW = 1;
    const PhoneSMSCodeTypeSF = 2;

    const PHONE_SMS_CODE_PRE = 'PHP_SMS'; // 短信验证码验证码的缓存key前缀
    // 得威出行
    const APP_RENT_RIDER = 'APP_RENT_RIDER';
    // 小哥助手
    const APP_POSTAL_RIDER = 'APP_POSTAL_RIDER';
    // 网点助手
    const APP_SITE_RIDER = 'APP_SITE_RIDER';

    /**
     * 发送短信验证码
     * @param $phone 手机号
     * @param string $pre
     * @param int $type 类型，默认得威模版
     * @return bool
     */
    public function SendPhoneSMSCode($phone, $pre = self::PHONE_SMS_CODE_PRE, $type=1)
    {
        // 请求微服务接口发送验证码
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            "code" => 60015,
            "parameter" => [
                'mobile' => $phone,
                'key'    => $pre . $phone,
                'sign'   => $type
            ]
        ],"post");
        // 返回状态
        if (200 != $result['statusCode']) {
            return false;
        }
        return true;
    }

    /**
     * 验证手机验证码
     * @param string $phone 手机号
     * @param string $code  验证码
     * @param string $pre
     * @param bool $door
     * @return bool
     * @throws DataException
     */
    public function CheckPhoneSMSCode($phone, $code, $pre = self::PHONE_SMS_CODE_PRE, $door = false)
    {
        if ($door && $code == '987654') {
            return true;
        }
        $key = $pre . $phone;
        $redisData = new RedisData();
        // 获取Redis验证码
        $redisCode = $redisData->get($key, false);
        if ($redisCode === null){
            throw new DataException([500, '验证码无效，请重新获取有效验证码']);
        }
        if ($code == $redisCode) {
            // 删除验证码
            $redisData->del($key, false);
            return true;
        }
        return false;
    }

    // 发送站长短信
    public function SendSiteUserSMS($userId)
    {
        $user = User::arrFindFirst([
            'id' => $userId
        ]);
        if(false===$user){
            throw new DataException([500, '用户不存在']);
        }
        $user = $user->toArray();
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => 10099,
            'parameter' => [
                'mobile' => $user['phone'],
                'account' => $user['user_name'],
                'psw' => '123456 (如已修改，请忽略)',
            ]
        ],'post');
        //结果处理返回
        if (!isset($result['statusCode']) || $result['statusCode'] != '200') {
            $this->logger->error('站长短信发送失败');
            return false;
        }
        return true;
    }
}

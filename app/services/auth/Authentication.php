<?php
namespace app\services\auth;

class Authentication
{
    //默认游客用户ID
    const GUEST_USER_ID = -1;

    public $userId = self::GUEST_USER_ID;
    public $userName = "guest";
    public $groupId = -1;
    public $roleId = -1;
    public $isAdministrator;
    public $insId;
    public $system;
    public $userType;
    public $regionId = -1;
    public $deviceUUID;

    public $iss;
    public $aud;
    public $iat;
    public $nbf;
    public $exp;

    public function isGuest()
    {
        return $this->userId == self::GUEST_USER_ID;
    }

    public static function newGuest()
    {
        return new Authentication();
    }
}
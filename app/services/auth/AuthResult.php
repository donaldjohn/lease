<?php
namespace app\services\auth;

class AuthResult
{
    public $access_token;
    public $token_type = "bearer";
    public $expires_in;
    public $scope = "";

    public function __construct($token)
    {
        $this->access_token = $token;
    }
}
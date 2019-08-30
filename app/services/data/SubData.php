<?php
namespace app\services\data;


use app\common\errors\DataException;


class SubData extends BaseData
{

    public function DeleteGroupSystemById($id) {
        $res = ["id" => $id];
        $json = ['code' => 10062,"parameter" => $res];
        $result = $this->curl->httpRequest($this->Zuul->user, $json, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return true;
        } else {
           throw new DataException();
        }
    }


    public function AddGroupSystem($groupId,$systemId)
    {
        $res = ["groupId" => $groupId,"systemId" => $systemId];
        $params = [
            'code' => 10061,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return true;
        } else {
            throw new DataException();
        }
    }


    public function DeleteRoleSystemById($id) {
        $res = ["id" => $id];
        $json = ['code' => 10067,"parameter"=> $res];
        $result = $this->curl->httpRequest($this->Zuul->user, $json, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return true;
        } else {
            throw new DataException();
        }
    }


    public function AddRoleSystem($roleId,$systemId)
    {
        $res = ["roleId" => $roleId,"systemId" => $systemId];
        $params = [
            'code' => 10066,
            'parameter' => $res
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return true;
        } else {
            throw new DataException();
        }
    }


}
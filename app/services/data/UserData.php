<?php
namespace app\services\data;
use app\common\errors\AppException;
use app\common\errors\DataException;
use app\models\users\Association;
use app\models\users\Company;
use app\models\users\Institution;
use app\models\users\Postoffice;
use app\models\users\Trafficpolice;
use app\models\users\User;


class UserData extends BaseData
{
    // 获取多条用户信息 通过idlist
    public function getUserByIds($ids)
    {
        $userlist = [];
        // 去除0值和重复值
        $ids = array_values(array_unique(array_diff($ids,[0])));
        foreach ($ids as $id){
            $result = $this->getUserById($id);
            $userlist[$id] = $result;
        }
        return $userlist;
    }
    // 获取单条用户信息 通过id
    public function getUserById($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10004',
            'parameter' => [
                'id' => $id,
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['users'][0]))
            throw new DataException([500, "用户{$id}数据不存在"]);
        if(!isset($result['content']['users'][0]))
            return false;
        $user = $result['content']['users'][0];



        //根据返回的数组进行匹配.理论上存在多个数组
        if (count($result['content']['userInstitutions']) > 0) {
            //用户对应机构
            foreach ($result['content']['userInstitutions'] as $item) {
                if ( 0 == $item['systemId']) {
                    $user['insId'] = $item['insId'];
                    $user['insAdmin'] = true;
                    //is_sub 1不是 2是
                }
            }
        }
        //TODO:: hack
        if (!isset($user['insId'])) {
            $user['insId'] = isset($result['content']['userInstitutions'][0]) ? $result['content']['userInstitutions'][0]['insId'] : -1;
        }

        return $user;
    }

    /**
     * @param $type
     * @return array
     * @throws DataException
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 内部用户
     */
    public function getInsideUserPage($type, $isAdministrator, $parentId , $companyName = null)
    {
        $this->logger->info("获取内部用户");
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
            "userType" => (int)$type,
            "isAdministrator" => (int)$isAdministrator,
            "parentId" => (int)$parentId,
        ];
        if (isset($_GET['type']) && 'list'==$_GET['type']){
            unset($parameter['pageNum'], $parameter['pageSize']);
        }
        $userName = $this->request->getQuery("userName","string",null);
        $realName = $this->request->getQuery("realName","string",null);
        $userStatus = $this->request->getQuery("userStatus","int",null);
        if(isset($userName))
            $parameter['userName'] = $userName;
        if(isset($realName))
            $parameter['realName'] =$realName;
        if(isset($userStatus))
            $parameter['userStatus'] =$userStatus;

        //调用微服务接口获取数据
        $params = ["code" => "10004", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (!isset($result['statusCode']) && $result['statusCode'] != '200')
            throw new DataException();
        $meta = $result['content']['pageInfo'] ?? null;
        $result = $result['content']['users'];

        foreach($result as $key => $item) {
            //根据角色Id查找角色名称
//            if ($item['roleId'] >= 1) {
//                $role = $this->roleData->getRoleById($item['roleId']);
//                if ($role == false) {
//                    $result[$key]['roleName'] = '---';
//                } else {
//                    $result[$key]['roleName'] = $role['roleName'];
//                }
//            } else {
//                $result[$key]['roleName'] = '---';
//            }
            unset($result[$key]['password']);
            $result[$key]['createAt'] = date("Y-m-d H:i:s", $item['createAt']);
            $result[$key]['updateAt'] = date("Y-m-d H:i:s", $item['updateAt']);

            if (!is_null($companyName)) {
                $result[$key]['companyName'] = $companyName;
            }
        }
        $users = ["data" => $result, "meta" => $meta];
        return $users;
    }


    //邮管局列表
    public function getPostOfficePage($authed,$type)
    {
        $this->logger->info("获取邮管局用户");
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
            "userType" => (int)$type
        ];
        $userName = $this->request->getQuery("userName","string",null);
        $userStatus = $this->request->getQuery("userStatus","int",null);
        $postName = $this->request->getQuery("postName","string",null);
        if(!empty($userName))
            $parameter['userName'] = $userName;
        if(!empty($userStatus))
            $parameter['userStatus'] = $userStatus;
        if(!empty($postName))
            $parameter['postName'] = $postName;

        if($authed->userType != 1)
            $parameter['insId'] = $authed->insId;


        //调用微服务接口获取数据
        $params = ["code" => "10074", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (isset($result['statusCode']) && $result['statusCode'] != '200')
            throw new DataException();
        $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : 0;
        $meta['pageNum'] = $pageNum;
        $meta['pageSize'] = $pageSize;
        $result = $result['content']['postoffices'];
        foreach ($result as $key => $item) {
            $result[$key]['createAt'] = date('Y-m-d H:i:s',$item['createAt']);
            $result[$key]['updateAt'] = date('Y-m-d H:i:s',$item['updateAt']);
        }
        return ['data' => $result,'meta' => $meta];

    }

    //快递协会
    public function getAssociationPage($authed,$type)
    {
        $this->logger->info("获取邮管局用户");
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
            "userType" => (int)$type
        ];
        $userName = $this->request->getQuery("userName","string",null);
        $userStatus = $this->request->getQuery("userStatus","int",null);
        $associationName = $this->request->getQuery("associationName","string",null);
        if(!empty($userName))
            $parameter['userName'] = $userName;
        if(!empty($userStatus))
            $parameter['userStatus'] = $userStatus;
        if(!empty($associationName))
            $parameter['associationName'] = $associationName;

        if($authed->userType != 1)
            $parameter['insId'] = $authed->insId;

        //调用微服务接口获取数据
        $params = ["code" => "10072", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (isset($result['statusCode']) && $result['statusCode'] != '200')
            throw new DataException();
        $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : 0;
        $meta['pageNum'] = $pageNum;
        $meta['pageSize'] = $pageSize;
        $result = $result['content']['associations'];
        foreach ($result as $key => $item) {
            $result[$key]['createAt'] = date('Y-m-d H:i:s',$item['createAt']);
            $result[$key]['updateAt'] = date('Y-m-d H:i:s',$item['updateAt']);
        }
        return ['data' => $result,'meta' => $meta];

    }

    //获取供应商 保险公司 配送企业
    public function getCompanyPage($authed,$type)
    {
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
            "userType" => (int)$type
        ];
        $userName = $this->request->getQuery("userName","string",null);
        $companyName = $this->request->getQuery("companyName","string",null);
        $userStatus = $this->request->getQuery("userStatus","int",null);
        $companyType = $this->request->getQuery("companyType","int",null);
        if(!empty($userName))
            $parameter['userName'] = $userName;
        if(!empty($companyName))
            $parameter['companyName'] =$companyName;
        if(!empty($userStatus))
            $parameter['userStatus'] =$userStatus;
        if(!empty($companyType))
            $parameter['companyType'] =$companyType;

//        //TODO :: 数据过滤
//        if($authed->userType != 1) {
//            $parameter['parentId'] =$authed->userId;
//        }
        if ($authed->userType == 2) {
            /**
             * 邮管局
             */
            $p = Postoffice::findFirst(['ins_id = :ins_id:','bind' => ['ins_id' => $authed->insId]]);
            if ($p != false) {
                if ($p->getCityId() > 0) {
                    /**
                     * 市邮管局
                     * 查询市级快递协会 insId
                     */
                    $cityId = (int)$p->getCityId();
                    $a = Association::findFirst(['level = :level: and city_id = :city_id:','bind' =>['level' => 3,'city_id' => $cityId]]);
                    if ($a) {
                        $parameter['insIds'][] = $a->getInsId();
                    }
                    $parameter['insIds'][] = $authed->insId;
                } else if ($p->getProvinceId() > 0) {
                    /**
                     * 省邮管局
                     * 查询省级快递协会 insId
                     */
                    $provinceId = (int)$p->getProvinceId();
                    $a = Association::find(['level = :level: and province_id = :province_id:','bind' =>['level' => 3,'province_id' => $provinceId]])->toArray();
                    if ($a) {
                        foreach ($a as $item) {
                            $parameter['insIds'][] = $item['ins_id'];
                        }
                    }
                    /**
                     * 查询市级邮管局
                     */
                    $childProvince = Postoffice::find(['level = :level: and province_id = :province_id:','bind' => ['level' => 3,'province_id' => $provinceId]])->toArray();
                    if ($childProvince) {
                        foreach ($childProvince as $item) {
                            $parameter['insIds'][] = $item['ins_id'];
                        }
                    }
                }
            }
        } elseif ($authed->userType == 3) {
            /**
             * 快递协会
             */
            $a = Association::findFirst(['ins_id = :ins_id:','bind' =>['ins_id' => $authed->insId]]);
            if ($a != false) {
                if ($a->getCityId() > 0) {
                    /**
                     * 查询市级快递协会生成的快递公司
                     */
                    $parameter['insIds'][] = $authed->insId;
                    /**
                     * 获取市级邮管局insId
                     */
                    $postoffice1 = Postoffice::findFirst(['level = :level: and city_id = :city_id:','bind' => ['level' => 3,'city_id' => $a->getCityId()]]);
                    if ($postoffice1) {
                        $parameter['insIds'][] =$postoffice1->getInsId();
                    }
                } elseif ($a->getProvinceId() > 0) {
                    /**
                     * 查询当前省下的市级快递协会
                     */
                    $parameter['insIds'][] = $authed->insId;
                    $provinceId = (int)$a->getProvinceId();
                    $a1 = Association::find(['level = :level: and province_id = :province_id:','bind' =>['level' => 3,'province_id' => $provinceId]])->toArray();
                    if ($a1 == false ) {
                        return $this->toSuccess(null, ['pageSize' => $pageSize, 'pageNum' => $pageNum, 'total' => 0]);
                    }
                    foreach ($a1 as $item) {
                        $parameter['insIds'][] = $item['ins_id'];
                    }
                    /**
                     * 查询市级邮管局
                     */
                    $childProvince = Postoffice::find(['level = :level: and province_id = :province_id:','bind' => ['level' => 3,'province_id' => $a->getProvinceId()]])->toArray();
                    if ($childProvince) {
                        foreach ($childProvince as $item) {
                            $parameter['insIds'][] = $item['ins_id'];
                        }
                    }

                }

            }
        } else {
            $parameter['insId'] = $authed->insId;
        }

        //调用微服务接口获取数据
        $params = ["code" => "10069", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (!isset($result['statusCode']) && $result['statusCode'] != '200')
            throw new DataException();
        $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : 0;
        $meta['pageNum'] = $pageNum;
        $meta['pageSize'] = $pageSize;
        $result = $result['content']['companyDOS'];
        foreach ($result as $key => $item) {
            $result[$key]['createAt'] = date('Y-m-d H:i:s',$item['createAt']);
            $result[$key]['updateAt'] = date('Y-m-d H:i:s',$item['updateAt']);
        }
        return ['data' => $result,'meta' => $meta];
    }



    //获取门店
    public function getStoreUserPage($authed,$type)
    {
        $pageSize = $this->request->getQuery('pageSize', "int", 20);
        $pageNum = $this->request->getQuery('pageNum', "int", 1);  //分页
        $areaDeep = $this->request->getQuery('areaDeep', "int", 0);  //门店区域级别
        $areaId = $this->request->getQuery('areaId', "int", 0);  //门店区域ID
        $legalPerson = $this->request->getQuery('legalPerson', "string", null);  //门店法人
        $storeType = $this->request->getQuery('storeType', "string", null);  //门店类型
        $parameter = [
            "pageNum" => (int)$pageNum,
            "pageSize" => (int)$pageSize,
            "userType" => (int)$type,
            "storeType" => $storeType
        ];

        // 条件筛选功能（根据门店区域或门店法人搜索门店列表）
        if ($areaDeep > 0 && $areaId > 0) {
            switch ($areaDeep) {
                case 1:
                    $parameter['provinceId'] = (int)$areaId;
                    break;
                case 2:
                    $parameter['cityId'] = (int)$areaId;
                    break;
            }
        }
        if (!empty($legalPerson)) {
            $parameter['legalPerson'] = (string)$legalPerson;
        }

        $userName = $this->request->getQuery("userName","string",null);
        $storeName = $this->request->getQuery("storeName","string",null);
        $userStatus = $this->request->getQuery("userStatus","int",null);
        if(!empty($userName))
            $parameter['userName'] = $userName;
        if(!empty($storeName))
            $parameter['storeName'] =$storeName;
        if(!empty($userStatus))
            $parameter['userStatus'] =$userStatus;

        if($authed->userType != 1) {
            $parameter['insId'] = $authed->insId;
            $parameter['userType'] = $authed->userType;
        }


        //调用微服务接口获取数据
        $params = ["code" => "10057", "parameter" => $parameter];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (!isset($result['statusCode']) && $result['statusCode'] != '200')
            throw new DataException();
        $meta['total'] = isset($result['content']['pageInfo']['total']) ? $result['content']['pageInfo']['total'] : 0;
        $meta['pageNum'] = $pageNum;
        $meta['pageSize'] = $pageSize;
        if (!isset($result['content']['stores'])) throw new DataException();
        $result = $result['content']['stores'];
        foreach ($result as $key => $item) {
            $result[$key]['createAt'] = date('Y-m-d H:i:s',$item['createAt']);
            $result[$key]['updateAt'] = date('Y-m-d H:i:s',$item['updateAt']);
        }
        return ['data' => $result,'meta' => $meta];

    }


    /**
     * @param $json
     * 创建内部用户
     */
    public function createInsideUser($json,$type)
    {
        $user = $this->newUser($json,$type);
        $result = $this->addUser($user);
        return $result;
    }


    /**
     * @param $json
     * 创建保险公司用户
     * 创建供应商用户
     * 创建配送企业
     */
    public function createCompany($authed,$json,$type)
    {

        $user = $this->newUser($json,$type);
        if (isset($json['companyName'])) {
            $user['insName'] = $json['companyName'];
        }
        //新增用户
        $params = ["code" => "10100","parameter" => $user];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            //return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        //获取insId
        if (!isset($result['content']['insId']))
            throw new DataException([500,"不存在机构ID"]);
        $insId = $result['content']['insId'];

//        //增加机构关系
//        if ($authed->insId > 0) {
//
//            $time = time();
//            $ins_params = ["code" => "10051","parameter" => ['insId' =>$authed->insId,'relationId' => $insId,'createAt' => $time ]];
//            $result = $this->curl->httpRequest($this->Zuul->user,$ins_params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                throw new DataException([500,$result['msg']]);
//            }
//        }

        //创建company
        $company = $this->newCompany($insId,$json);
        $params = ["code" => "10070","parameter" => $company];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            // return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        return $result['content'];
    }


    public function createPostOffice($authed,$json ,$type){
        if(!isset($json['provinceId']))
            throw new DataException([500, "对应的省不存在"]);
        if(!isset($json['level']))
            throw new DataException([500, "等级不存在"]);
        if($json['level'] == 3 && !isset($json['cityId']))
            throw new DataException([500, "城市不存在"]);
//        $old_prarms = ['provinceId' => $json['provinceId'],'level' => $json['level'],'cityId' => $json['cityId']];
//        $post = $this->checkPostOffice($old_prarms);
//        if ($post == true)
//            throw new AppException([500,"创建的邮管局已经存在"]);
        /**
         *  根据省和市ID获取机构信息 is_delete = 0 is_administrator = 2 user_type = 2
         */
        $builder = $this->modelsManager->createBuilder()
            ->columns("c.id,c.level,c.ins_id,c.province_id,c.city_id,ui.is_admin,u.is_administrator,u.is_delete,u.user_type")
            ->addFrom("app\models\users\Postoffice",'c')
            ->leftJoin("app\models\users\UserInstitution",'c.ins_id = ui.ins_id','ui')
            ->leftJoin("app\models\users\User",'u.id = ui.user_id','u')
            ->where('c.level = :level: and c.province_id = :provinceId: and c.city_id = :cityId: and u.is_delete = 0 and u.is_administrator = 2 and u.user_type = :userType:',['provinceId' => $json['provinceId'],'cityId' => $json['cityId'],'userType' => 2,'level' =>$json['level'] ])
            ->getQuery()->getSingleResult();
        if ($builder != false) {
            throw new DataException([500, "邮管局已经存在！"]);
        }

        if(isset($json['level']) &&  $json['level'] == 3) {
            $params = ["code" => "10022","parameter" => ['areaId' => $json['cityId']]];
            $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                throw new DataException([$result['statusCode'], $result['msg']]);
            }
            if (!isset($result['content']['data'][0]))
                throw new DataException([500, "数据不存在"]);
            $json['postName'] = $result['content']['data'][0]['areaName'].'邮管局';
            $json['insName'] = $result['content']['data'][0]['areaName'].'邮管局';
            $areaParentId  = $result['content']['data'][0]['areaParentId'];
            $params = ['provinceId' => $areaParentId, "level" => 2,'cityId' => 0];
            $post = $this->getPostOffice($params);
            $json['parentId'] = $post['id'];
        } else {
            $params = ["code" => "10022","parameter" => ['areaId' => $json['provinceId']]];
            $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                throw new DataException([$result['statusCode'], $result['msg']]);
            }
            if (!isset($result['content']['data'][0]))
                throw new DataException([500, "数据不存在"]);
            $json['postName'] = $result['content']['data'][0]['areaName'].'邮管局';
            $json['insName'] = $result['content']['data'][0]['areaName'].'邮管局';
            $json['parentId'] = 0;
        }

        $user = $this->newUser($json,$type);
        $user['insName'] = $json['insName'];
        //新增用户
        $params = ["code" => "10100","parameter" => $user];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            //return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        //获取insId
        if (!isset($result['content']['insId']))
            throw new DataException([500,"不存在机构ID"]);
        $insId = $result['content']['insId'];

        //增加机构关系
//        if ($authed->insId > 0) {
//
//            $time = time();
//            $ins_params = ["code" => "10051","parameter" => ['insId' =>$authed->insId,'relationId' => $insId,'createAt' => $time ]];
//            $result = $this->curl->httpRequest($this->Zuul->user,$ins_params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                throw new DataException([500,$result['msg']]);
//            }
//
//        }

        //创建company
        $postoffice = $this->newPostOffice($insId,$json);

        $params = ["code" => "10073","parameter" => $postoffice];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            // return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        return $result['content']['postoffice'];

    }

    public function createAssociation($authed,$json ,$type){

        if(!isset($json['provinceId']))
            throw new DataException([500, "省ID不存在"]);
        if(!isset($json['level']))
            throw new DataException([500, "level不存在"]);
        if($json['level'] == 3 && !isset($json['cityId']))
            throw new DataException([500, "cityID不存在"]);

        /**
         * 验证新增数据是否存在 2018.10.12 zhengchao
         */
//        $checkJson = [];
//        if (isset($json['provinceId'])) {
//            $checkJson['provinceId'] = $json['provinceId'];
//        }
//        if (isset($json['cityId'])) {
//            $checkJson['cityId'] = $json['cityId'];
//        }
//        $result = $this->curl->httpRequest($this->Zuul->user,[
//            'code' => '10072',
//            'parameter' => $checkJson
//        ],"post");
//        //结果处理返回
//        if ($result['statusCode'] != '200') {
//            throw new DataException([$result['statusCode'], $result['msg']]);
//        }
//        if (isset($result['content']['associations'][0]))
//            throw new DataException([500, "快递协会已存在！"]);

        /**
         *  根据省和市ID获取机构信息 is_delete = 0 is_administrator = 2 user_type = 3
         */
        $builder = $this->modelsManager->createBuilder()
            ->columns("c.id,c.ins_id,c.province_id,c.city_id,ui.is_admin,u.is_administrator,u.is_delete,u.user_type")
            ->addFrom("app\models\users\Association",'c')
            ->leftJoin("app\models\users\UserInstitution",'c.ins_id = ui.ins_id','ui')
            ->leftJoin("app\models\users\User",'u.id = ui.user_id','u')
            ->where('c.province_id = :provinceId: and c.city_id = :cityId: and u.is_delete = 0 and u.is_administrator = 2 and u.user_type = :userType:',['provinceId' => $json['provinceId'],'cityId' => $json['cityId'],'userType' => 3])
            ->getQuery()->getSingleResult();
        if ($builder != false) {
            throw new DataException([500, "快递协会已存在！"]);
        }

        //先找对应 邮管局ID
        if (isset($json['cityId']) && $json['cityId'] != 0) {
            $region= $this->getRegionName($json['cityId']);

            //10074
            $params = ["code" => "10074", "parameter" => ['cityId' => $json['cityId']]];
            $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
            if (isset($result['statusCode']) && $result['statusCode'] != '200')
                throw new DataException();
            if (!isset($result['content']['postoffices'][0]['id']))
                throw new  DataException([500,'暂无'.$region['areaName'].'邮管局']);
            $postId = $result['content']['postoffices'][0]['id'];
            $json['associationName'] = $region['areaName'].'快递协会';
            $json['insName'] = $region['areaName'].'快递协会';
            /**
             * 验证上级快递协会是否存在
             */
            $params = ['provinceId' => $json['provinceId'], "level" => 2,'cityId' => 0];
            $result = $this->curl->httpRequest($this->Zuul->user,[
                'code' => '10072',
                'parameter' => $params
            ],"post");
            //结果处理返回
            if ($result['statusCode'] != '200') {
                throw new DataException([$result['statusCode'], $result['msg']]);
            }
            if (!isset($result['content']['associations'][0]))
                throw new DataException([500, "上级快递协会信息数据不存在"]);
            $post = $result['content']['associations'][0];
            $json['parentId'] = $post['id'];

        }else if (isset($json['provinceId'])) {
            $region= $this->getRegionName($json['provinceId']);
            $params = ["code" => "10074", "parameter" => ['provinceId' => $json['provinceId']]];
            $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
            if (isset($result['statusCode']) && $result['statusCode'] != '200')
                throw new DataException();
            if (!isset($result['content']['postoffices'][0]['id']))
                throw new  DataException([500,'暂无'.$region['areaName'].'邮管局']);
            $postId = $result['content']['postoffices'][0]['id'];
            $json['associationName'] = $region['areaName'].'快递协会';
            $json['insName'] = $region['areaName'].'快递协会';
            $json['parentId'] = 0;
        }

        $user = $this->newUser($json,$type);
        $user['insName'] = $json['insName'];
        //新增用户
        $params = ["code" => "10100","parameter" => $user];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            //return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        //获取insId
        if (!isset($result['content']['insId']))
            throw new DataException([500,"不存在机构ID"]);
        $insId = $result['content']['insId'];

        //增加机构关系
//        if ($authed->insId > 0) {
//
//            $time = time();
//            $ins_params = ["code" => "10051","parameter" => ['insId' =>$authed->insId,'relationId' => $insId,'createAt' => $time ]];
//            $result = $this->curl->httpRequest($this->Zuul->user,$ins_params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                throw new DataException([500,$result['msg']]);
//            }
//
//        }

        $association = $this->newAssociation($insId,$json);
        $association['postId'] = $postId;
        $params = ["code" => "10071","parameter" => $association];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            // return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        return $result['content'];
    }

    /**
     * @param $json
     * 创建门店用户
     */
    public function createStore($authed,$json,$type)
    {
        $user = $this->newUser($json,$type);
        if (isset($json['storeName'])) {
            $user['insName'] = $json['storeName'];
        }
        //新增用户
        $params = ["code" => "10100","parameter" => $user];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            //return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        //获取insId
        if (!isset($result['content']['insId']))
            throw new DataException([500,"不存在机构ID"]);
        $insId = $result['content']['insId'];


        //增加机构关系
//        if ($authed->insId > 0) {
//
//            $time = time();
//            $ins_params = ["code" => "10051","parameter" => ['insId' =>$authed->insId,'relationId' => $insId,'createAt' => $time ]];
//            $result = $this->curl->httpRequest($this->Zuul->user,$ins_params,"post");
//            //结果处理返回
//            if ($result['statusCode'] != '200') {
//                throw new DataException([500,$result['msg']]);
//            }
//
//        }

        $store = $this->newStore($insId,$json);
        $params = ["code" => "10056","parameter" => $store];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            // return $this->toError($result['statusCode'],$result['msg']);
            throw new DataException([500,$result['msg']]);
        }
        return $result['content'];

    }



    /**
     * @param $id
     * @return mixed
     * @throws DataException
     * @throws \app\common\errors\CurlException
     * @throws \app\common\errors\MicroException
     * 根据机构ID查找公司信息
     */
    public function getCompanyByInsId($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10069',
            'parameter' => [
                'insId' => $id,
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['companyDOS'][0]))
            throw new DataException([500, "公司信息数据不存在"]);

        $company = $result['content']['companyDOS'][0];
        return $company;
    }

    public function getStoreByInsId($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10057',
            'parameter' => [
                'insId' => $id,
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['stores'][0]))
            throw new DataException([500, "公司信息数据不存在"]);

        $company = $result['content']['stores'][0];
        return $company;
    }

    public function getPostOfficeByInsId($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10074',
            'parameter' => [
                'insId' => $id,
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['postoffices'][0]))
            throw new DataException([500, "邮管局信息数据不存在"]);

        $post = $result['content']['postoffices'][0];
        return $post;
    }

    public function getPostOffice($json)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10074',
            'parameter' => $json,
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['postoffices'][0]))
            throw new DataException([500, "上级邮管局信息数据不存在"]);

        $post = $result['content']['postoffices'][0];
        return $post;
    }

    public function checkPostOffice($json)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10074',
            'parameter' => $json,
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['postoffices']))
            throw new DataException([500, "上级邮管局信息数据不存在"]);

        if (count($result['content']['postoffices']) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getAssociation($json)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10072',
            'parameter' => $json
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['associations'][0]))
            throw new DataException([500, "快递协会信息数据不存在"]);

        $post = $result['content']['associations'][0];
        return $post;
    }

    public function getAssociationByInsId($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10072',
            'parameter' => [
                'insId' => $id,
            ]
        ],"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['associations'][0]))
            throw new DataException([500, "邮管局信息数据不存在"]);

        $post = $result['content']['associations'][0];
        return $post;
    }



    public function deleteUserById($id)
    {
        $params = [
            'code' => '10002',
            'parameter' => [
                'id' => $id,
            ]
        ];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] == '200') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $json
     * @param $type
     * @return array
     * @throws DataException
     * 创建用户数据
     */
    private function newUser($json,$type)
    {
        $user = [];
        if (!isset($json['userName'])) throw new DataException([500,"创建用户不能为空"]);
        $user['userName'] = trim($json['userName']);
        if (isset($json['realName'])) {
            $user['realName'] = $json['realName'];
        } else {
            $user['realName'] = $json['userName'];
        }
        if (isset($json['phone'])) {
            $user['phone'] = $json['phone'];
        } else {
            $user['phone'] = '';
        }
        if (!isset($json['groupId'])) throw new DataException([500,"用户组不能为空"]);
        $user['groupId'] = $json['groupId'];
        if (isset($json['roleId'])) {
            $user['roleId'] = $json['roleId'];
        } else {
            $user['roleId'] = 0;
        }
        if (!isset($json['parentId'])) {
            $user['parentId'] = 0;
        } else {
            $user['parentId'] = $json['parentId'];
        }

        if (!isset($json['isAdministrator'])) {
            $user['isAdministrator'] = 1;
        } else {
            $user['isAdministrator'] = $json['isAdministrator'];
        }
        if (isset($json['userStatus']) && $json['userStatus'] == 1) {
            $user['userStatus'] = 1;
        } else {
            $user['userStatus'] = 2;
        }

        if (isset($json['systemId'])) {
            $user['systemId']  = $json['systemId'] ;
        } else {
            $user['systemId']  = 0;
        }

        if (isset($json['sex'])) {
            $user['sex']  = $json['sex'] ;
        } else {
            $user['sex']  = 1;
        }

        if (isset($json['email'])) {
            $user['email']  = $json['email'] ;
        } else {
            $user['email']  = '';
        }

        if (isset($json['userRemark'])) {
            $user['userRemark']  = $json['userRemark'] ;
        } else {
            $user['userRemark']  = '';
        }

        if (isset($json['idCard'])) {
            $user['idCard']  = $json['idCard'] ;
        } else {
            $user['idCard']  = '';
        }

        if (isset($json['insId'])) {
            $user['insId']  = $json['insId'] ;
        } else {
            $user['insId']  = 0;
        }

        $user['createAt'] = time();
        $user['userType'] = $type;
        $user['password'] = $this->security->hash("123456");
        return $user;
    }


    /**
     * @param $insId
     * @param $json
     * @return array
     * @throws DataException
     */
    private function newCompany($insId,$json)
    {
        $company = [];
        $company['insId'] = $insId;
        if (isset($json['companyName'])) {
            $company['companyName'] = $json['companyName'];
        } else {
            $company['companyName'] = '';
        }

        if (isset($json['companyType'])) {
            $company['companyType'] = $json['companyType'];
        } else {
            $company['companyType'] = '';
        }

        if (isset($json['legalPerson'])) {
            $company['legalPerson'] = $json['legalPerson'];
        } else {
            $company['legalPerson'] = '';
        }

        if (isset($json['scale'])) {
            $company['scale'] = $json['scale'];
        } else {
            $company['scale'] = '';
        }
        if (isset($json['orgCode'])) {
            $company['orgCode'] = $json['orgCode'];
        } else {
            $company['orgCode'] = '';
        }

        if (isset($json['regMark'])) {
            $company['regMark'] = $json['regMark'];
        } else {
            $company['regMark'] = '';
        }

        if (isset($json['bankName'])) {
            $company['bankName'] = $json['bankName'];
        } else {
            $company['bankName'] = '';
        }
        if (isset($json['bankOwner'])) {
            $company['bankOwner'] = $json['bankOwner'];
        } else {
            $company['bankOwner'] = '';
        }

        if (isset($json['bankCard'])) {
            $company['bankCard'] = $json['bankCard'];
        } else {
            $company['bankCard'] = '';
        }
        if (isset($json['scope'])) {
            $company['scope'] = $json['scope'];
        } else {
            $company['scope'] = '';
        }
        if (!isset($json['linkMan'])) throw new DataException([500,"联系人不能为空"]);
        $company['linkMan'] = $json['linkMan'];
        if (isset($json['linkCard'])) {
            $company['linkCard'] = $json['linkCard'];
        } else {
            $company['linkCard'] = '';
        }
        if (isset($json['linkPhone'])) {
            $company['linkPhone'] = $json['linkPhone'];
        } else {
            $company['linkPhone'] = '';
        }
        if (isset($json['linkTel'])) {
            $company['linkTel'] = $json['linkTel'];
        } else {
            $company['linkTel'] = '';
        }
        if (isset($json['linkMail'])) {
            $company['linkMail'] = $json['linkMail'];
        } else {
            $company['linkMail'] = '';
        }
        if (!isset($json['provinceId'])) throw new DataException([500,"省ID不能为空"]);
        $company['provinceId'] = $json['provinceId'];
        if (!isset($json['cityId'])) throw new DataException([500,"市ID不能为空"]);
        $company['cityId'] = $json['cityId'];
        if (!isset($json['areaId'])) throw new DataException([500,"区ID不能为空"]);
        $company['areaId'] = $json['areaId'];
        if (isset($json['address'])) {
            $company['address'] = $json['address'];
        } else {
            $company['address'] = '';
        }
        $company['createAt'] = time();

        return $company;
    }


    private function newPostOffice($insId,$json)
    {
        $company = [];
        $company['insId'] = $insId;

        if (!isset($json['postName'])) throw new DataException([500,"邮管局名称不能为空"]);
        $company['postName'] = $json['postName'];
        if (!isset($json['level'])) throw new DataException([500,"邮管局级别 1:国家级 2省级 3市级不能为空"]);
        $company['level'] = $json['level'];

        if (!isset($json['parentId'])) throw new DataException([500,"父辈ID国家级邮管局parent_id为0"]);
        $company['parentId'] = $json['parentId'];

        if (!isset($json['provinceId'])) throw new DataException([500,"省ID不能为空"]);
        $company['provinceId'] = $json['provinceId'];

        if (isset($json['cityId'])) {
            $company['cityId'] = $json['cityId'];
        } else {
            $company['cityId'] = 0;
        }


        if (!isset($json['linkMan'])) throw new DataException([500,"联系人不能为空"]);
        $company['linkMan'] = $json['linkMan'];

        if (isset($json['linkPhone'])) {
            $company['linkPhone'] = $json['linkPhone'];
        } else {
            $company['linkPhone'] = '';
        }
        if (isset($json['remark'])) {
            $company['remark'] = $json['remark'];
        } else {
            $company['remark'] = '';
        }
        $company['createAt'] = time();

        return $company;
    }


    private function newAssociation($insId,$json)
    {
        $company = [];
        $company['insId'] = $insId;

        if (!isset($json['associationName'])) throw new DataException([500,"快递协会名称不能为空"]);
        $company['associationName'] = $json['associationName'];
        if (!isset($json['level'])) throw new DataException([500,"快递协会级别 1:国家级 2省级 3市级不能为空"]);
        $company['level'] = $json['level'];

        if (!isset($json['parentId'])) throw new DataException([500,"父辈ID国家级邮管局parent_id为0"]);
        $company['parentId'] = $json['parentId'];

        if (!isset($json['provinceId'])) throw new DataException([500,"省ID不能为空"]);
        $company['provinceId'] = $json['provinceId'];

        if (!isset($json['cityId'])) throw new DataException([500,"市ID不能为空"]);
        $company['cityId'] = $json['cityId'];

        if (isset($json['linkMan'])) {
            $company['linkMan'] = $json['linkMan'];
        } else {
            $company['linkMan'] = '';
        }

        if (isset($json['linkPhone'])) {
            $company['linkPhone'] = $json['linkPhone'];
        } else {
            $company['linkPhone'] = '';
        }

        if (isset($json['remark'])) {
            $company['remark'] = $json['remark'];
        } else {
            $company['remark'] = '';
        }

        if (isset($json['storeType']))
            $company['storeType'] = $json['storeType'];

        $company['createAt'] = time();

        return $company;
    }


    private function newStore($insId,$json)
    {
        $company = [];
        $company['insId'] = $insId;
        $company['scope'] = $json['scope'];
        if (!isset($json['storeName'])) throw new DataException([500,"门店名称不能为空"]);
        $company['storeName'] = $json['storeName'];
        if (isset($json['legalPerson'])) {
            $company['legalPerson'] = $json['legalPerson'];
        } else {
            $company['legalPerson'] = '';
        }

        if (!isset($json['lat'])) throw new DataException([500,"lat不能为空"]);
        $company['lat'] = $json['lat'];
        if (!isset($json['lng'])) throw new DataException([500,"lng不能为空"]);
        $company['lng'] = $json['lng'];
        if (isset($json['orgCode'])) {
            $company['orgCode'] = $json['orgCode'];
        } else {
            $company['orgCode'] = '';
        }
        if (isset($json['regMark'])) {
            $company['regMark'] = $json['regMark'];
        } else {
            $company['regMark'] = '';
        }

        if (isset($json['bankName'])) {
            $company['bankName'] = $json['bankName'];
        } else {
            $company['bankName'] = '';
        }
        if (isset($json['bankOwner'])) {
            $company['bankOwner'] = $json['bankOwner'];
        } else {
            $company['bankOwner'] = '';
        }
        if (isset($json['bankCard'])) {
            $company['bankCard'] = $json['bankCard'];
        } else {
            $company['bankCard'] = '';
        }

        if (isset($json['linkMan'])) {
            $company['linkMan'] = $json['linkMan'];
        } else {
            $company['linkMan'] = '';
        }
        if (isset($json['imgUrl'])) {
            $company['imgUrl'] = $json['imgUrl'];
        } else {
            $company['imgUrl'] = '';
        }

        if (isset($json['linkCard'])) {
            $company['linkCard'] = $json['linkCard'];
        } else {
            $company['linkCard'] = '';
        }
        if (isset($json['linkPhone'])) {
            $company['linkPhone'] = $json['linkPhone'];
        } else {
            $company['linkPhone'] = '';
        }
        if (isset($json['linkTel'])) {
            $company['linkTel'] = $json['linkTel'];
        } else {
            $company['linkTel'] = '';
        }
        if (isset($json['linkMail'])) {
            $company['linkMail'] = $json['linkMail'];
        } else {
            $company['linkMail'] = '';
        }
        if (!isset($json['provinceId'])) throw new DataException([500,"省ID不能为空"]);
        $company['provinceId'] = $json['provinceId'];
        if (!isset($json['cityId'])) throw new DataException([500,"市ID不能为空"]);
        $company['cityId'] = $json['cityId'];
        if (!isset($json['areaId'])) throw new DataException([500,"区ID不能为空"]);
        $company['areaId'] = $json['areaId'];
        if (isset($json['address'])) {
            $company['address'] = $json['address'];
        } else {
            $company['address'] = '';
        }
        if (isset($json['storeType']) && !empty($json['storeType']) && !is_array($json['storeType']))
            throw new DataException([500,"门店经营范围格式错误"]);
        $company['storeType'] = $json['storeType'];
        $company['createAt'] = time();
        if (isset($json['linkMail'])) {
            $company['linkMail'] = $json['linkMail'];
        }
        if (isset($json['startAt'])) {
            $company['startAt'] = $json['startAt'];
        }
        if (isset($json['endAt'])) {
            $company['endAt'] = $json['endAt'];
        }
        return $company;
    }

    private function addUser($user)
    {
        $params = ["code" => "10100", "parameter" => $user];
        $result = $this->curl->httpRequest($this->Zuul->user, $params, "post");
        if (isset($result['statusCode']) && $result['statusCode'] == '200') {
            return $result['content'];
        } else {
            return ['code' => false,'msg' => $result['msg']];
        }
    }


    /**
     * @param $user
     * 根据用户ID获取用户然后更新用户
     */
    public function updateUser($user)
    {
        //修改用户数据
        $result = $this->curl->httpRequest($this->Zuul->user,[
            'code' => '10003',
            'parameter' => $user
        ],"post");
        //错误返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        return true;
    }


    /**
     *
     * 根据companyID获取用户然后更新用户
     * 10078
     */
    public function updateCompany($company)
    {
        $put_params = ["code" => "10078","parameter" => $company];
        $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
        if ($result['statusCode'] != '200')
            throw new DataException([$result['statusCode'], $result['msg']]);
        return true;

    }

    //10076
    public function updatePostOffice($company)
    {
        $put_params = ["code" => "10076","parameter" => $company];
        $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
        if ($result['statusCode'] != '200')
            throw new DataException([$result['statusCode'], $result['msg']]);
        return true;
    }

    //10077
    public function updateAssociation($company)
    {
        $put_params = ["code" => "10077","parameter" => $company];
        $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
        if ($result['statusCode'] != '200')
            throw new DataException([$result['statusCode'], $result['msg']]);
        return true;

    }

    //10075
    public function updateStore($company)
    {
        $put_params = ["code" => "10075","parameter" => $company];
        $result = $this->curl->httpRequest($this->Zuul->user,$put_params,"post");
        if (!$result['statusCode'] == '200')
            throw new DataException([$result['statusCode'], $result['msg']]);
        return $result['content'];

    }


    public function getRegionName($res)
    {
        if (!($res>0)){
            return false;
        }
        $params = ["code" => "10022","parameter" => ['areaId' => $res]];

        $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['data'][0]))
            throw new DataException([500, "数据不存在"]);
        return $result['content']['data'][0];
    }



    public function getUserSystem($userId,$systemId)
    {
        $params = ["code" => "10059","parameter" => ['userId' => $userId,'systemId' => $systemId]];

        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['data'][0]))
            return false;
        return true;
    }


    public function getRegionByUserId($id)
    {
        $result = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => '60009',
            'parameter' => [
                'userId' => $id
            ]
        ],"post");
        //失败返回
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], '获取区域人员关系：'.$result['msg']]);
        }

        return $result['content']['data'];
    }

    // 批量获取公司名
    public function getCompanyNamesByInsIds($insIds)
    {
        if (!is_array($insIds)){
            $insIds = [$insIds];
        }
        // 去除0值和重复值
        $insIds = array_values(array_unique(array_diff($insIds,[0, null])));
        if (empty($insIds)){
            return [];
        }
        $companys = Company::arrFind([
            'ins_id' => ['IN', $insIds]
        ])->toArray();
        $companyNames = [];
        foreach ($companys as $company){
            $companyNames[$company['ins_id']] = $company['company_name'];
        }
        return $companyNames;
    }

    /**
     * TODO: 主子合并预废弃
     * 批量获取快递公司名称通过子系统insIds
     * @param $insIds
     * @return array
     */
    /*
    public function getExpressNamesByInsIds($insIds)
    {
        if (!is_array($insIds)){
            $insIds = [$insIds];
        }
        // 去除0值和重复值
        $insIds = array_values(array_unique(array_diff($insIds,[0, null])));
        if (empty($insIds)){
            return [];
        }
        $expressNames = [];
        if (!empty($insIds)){
            // 获取快递公司名字
            $insNames =  $this->modelsManager->createBuilder()
                // 查询快递公司
                ->addfrom('app\models\users\Institution','i')
                ->where('i.id IN ({insIds:array})', [
                    'insIds' => $insIds,
                ])
                // 关联子系统快递公司insID
                ->join('app\models\users\Company', 'c.ins_id = i.parent_id','c')
                ->columns('i.id, c.company_name')
                ->getQuery()
                ->execute()
                ->toArray();
            foreach ($insNames as $insName) {
                $expressNames[$insName['id']] = $insName['company_name'];
            }
        }
        return $expressNames;
    }*/

    /**
     * 获取快递公司名称通过insId
     * @param $insId
     * @return mixed|null
     */
    public function getExpressNamesByInsId($insId)
    {
        $expressNames = $this->getCompanyNamesByInsIds([$insId]);
        return $expressNames[$insId] ?? null;
    }

    public function getUserByJson($json)
    {
        $params = ["code" => "10004", "parameter" => $json];
        //调用微服务接口获取数据
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        return $result;
    }

    /**
     * 获取邮管局下的快递协会
     * @param $PostofficeInsId 邮管局InsId
     * @return array 快递协会InsIds
     * @throws DataException
     */
    public function getAssociationInsIdsByPostoffice($PostofficeInsId)
    {
        // 获取邮管局的区域
        $association = Postoffice::findFirst([
            'ins_id = :ins_id:',
            'bind' => [
                'ins_id' => $PostofficeInsId,
            ],
        ]);
        if (false===$association){
            throw new DataException([500, '机构异常']);
        }
        $data = [];
        switch ($association->level){
            case 2:
                $data['province_id'] = $association->province_id;
                break;
            case 3:
                $data['city_id'] = $association->city_id;
                break;
        }
        // 获取邮管局下的快递协会
        $associations = Association::arrFind($data);
        $associationInsIds = [];
        foreach ($associations as $association){
            $associationInsIds[] = $association->ins_id;
        }
        return $associationInsIds;
    }

    /**
     * 根据行政区域区域获取快递协会
     * @param $area 区域
     * @return array 快递协会InsIds
     */
    public function getAssociationInsIdsByArea($area)
    {
        $data = [];
        if (isset($area['areaId']) && $area['areaId']>0){
        }
        if (isset($area['cityId']) && $area['cityId']>0){
            $data['city_id'] = $area['cityId'];
        }
        if (isset($area['provinceId']) && $area['provinceId']>0){
            $data['province_id'] = $area['provinceId'];
        }
        // 获取区域下下的快递协会
        $associations = Association::arrFind($data);
        $associationInsIds = [];
        foreach ($associations as $association){
            $associationInsIds[] = $association->ins_id;
        }
        return $associationInsIds;
    }

    public function getPostOfficeInsIdsByArea($area)
    {
        $data = [];
        if (isset($area['areaId']) && $area['areaId']>0){
        }
        if (isset($area['cityId']) && $area['cityId']>0){
            $data['city_id'] = $area['cityId'];
        }
        if (isset($area['provinceId']) && $area['provinceId']>0){
            $data['province_id'] = $area['provinceId'];
        }
        // 获取区域下下的邮管局
        $postOffices = Postoffice::arrFind($data);
        $postOfficeInsIds = [];
        foreach ($postOffices as $postOffice){
            $postOfficeInsIds[] = $postOffice->ins_id;
        }
        return $postOfficeInsIds;
    }
    /***
     * 获取指定类型机构idList通过快递协会(仅支持快递协会创建的机构)
     * @param $associationInsIds 快递协会InsId/s
     * @param null $type 获取的机构类型
     * @param bool $ChildAssociation 是否获取子级【传入insId非数组时才有效】
     * @return array 机构InsIDs
     * @throws DataException
     */
    private function getInstitutionIdListByAssociation($associationInsIds, $type=null, $ChildAssociation=false)
    {
        if (empty($associationInsIds)){
            return [];
        }
        // 需要向下获取快递协会 && 单id传参
        if ($ChildAssociation && !is_array($associationInsIds)){
            $area = $this->getAreaByInsId($associationInsIds);
            $associationInsIds = $this->getAssociationInsIdsByArea($area);
        }
        $where = [];
        if (null !== $type){
            $where['type_id'] = $type;
        }
        if (is_array($associationInsIds)){
            $where['parent_id'] = ['IN', $associationInsIds];
        }else{
            $where['parent_id'] = $associationInsIds;
        }
        $institutions = Institution::arrFind($where,['columns'=>'id AS insId'])->toArray();
        $institutionIdList = [];
        foreach ($institutions as $item) {
            $institutionIdList[] = $item['insId'];
        }
        return $institutionIdList;
    }

    /**
     * 获取快递协会下的快递公司
     * @param $associationInsIds 快递协会InsId/s
     * @param bool $subSystem 默认获取子系统快递公司 TODO: 主子合并预废弃此参数
     * @param bool $ChildAssociation 是否获取子级【传入insId非数组时才有效】
     * @return array 快递公司InsIDs
     * @throws DataException
     */
    public function getExpressIdsByAssociation($associationInsIds, $ChildAssociation=false)
    {
        return $this->getInstitutionIdListByAssociation($associationInsIds, 7, $ChildAssociation);
    }

    // 查询快递协会下的供应商
    public function getSupplierByAssociation($associationInsIds)
    {
        $SupplierIdList = $this->getInstitutionIdListByAssociation($associationInsIds, 5);
        return $SupplierIdList;
    }

    /**
     * 获取邮管局下的快递公司
     * @param $PostofficeInsId 邮管局InsId
     * @return array 快递公司InsIDs
     * @throws DataException
     */
    public function getExpressIdsByPostoffice($PostofficeInsId)
    {
        /**
         * 邮管局本身就能创建快递公司
         * dududu
         */
        $expressIdList = [];
        $p = Postoffice::findFirst(['ins_id = :insId:','bind' => ['insId' =>$PostofficeInsId]]);
        if (!$p) {
            return $expressIdList;
        }
        if ($p->getLevel() == 2) {
            /**
             * 省
             * 1.获取市级邮管局ID
             */
            //$cityProvince = Postoffice::find(['level = 3 and province_id = :provinceId:'])->toArray();
            $model = $this->modelsManager->createBuilder()
                ->columns('i.id')
                ->addFrom("app\models\users\Institution",'i')
                ->Join("app\models\users\Institution",'i.parent_id = i1.id','i1')
                ->Join("app\models\users\Postoffice",'p.ins_id = i1.id and p.level = 3','p')
                ->leftJoin("app\models\users\Postoffice",'p1.province_id = p.province_id and p1.level = 2','p1')
                ->where('i.type_id = 7 and p1.ins_id = :insId:',['insId' =>$PostofficeInsId])
                ->getQuery()
                ->execute();
            foreach ($model as $item) {
                $expressIdList[] = $item['id'];
            }
        } elseif ($p->getLevel() == 3) {
            /**
             * 市
             */
            $model = $this->modelsManager->createBuilder()
                ->columns('i.id')
                ->addFrom("app\models\users\Institution",'i')
                //->leftJoin("app\models\users\Institution",'i.parent_id = i1.id','i1')
                ->where('i.type_id = 7 and i.parent_id = :insId:',['insId' =>$PostofficeInsId])
                ->getQuery()
                ->execute();
            foreach ($model as $item) {
                $expressIdList[] = $item['id'];
            }
        }
        $associationInsIds = $this->getAssociationInsIdsByPostoffice($PostofficeInsId);
        if (empty($associationInsIds)){
            return $expressIdList;
        }
        // 获取快递协会下的快递公司
        $expressIdLists = $this->getExpressIdsByAssociation($associationInsIds);
        $expressIdList =  array_merge($expressIdList,$expressIdLists);
        return array_unique($expressIdList);
    }

    // 获取机构父级机构id
    public function getParentInsIdByInsId($insId)
    {
        $institution = Institution::arrFindFirst([
            'id' => $insId,
        ], ['columns'=>'parent_id']);
        if (false == $institution){
            throw new DataException([500, "机构{$insId}不存在"]);
        }
        return $institution->parent_id;
    }

    /**
     * 获取保险公司关联的快递协会
     * @param $InsuranceCompanyInsId
     * @return int
     * @throws DataException
     */
    public function getAssociationInsIdsByInsuranceCompany($InsuranceCompanyInsId)
    {
        return $this->getParentInsIdByInsId($InsuranceCompanyInsId);
    }

    /**
     * 获取保险公司关联的快递公司
     * @param $InsuranceCompanyInsId
     * @return array
     * @throws DataException
     */
    public function getExpressIdsByInsuranceCompany($InsuranceCompanyInsId, $ChildAssociation=false)
    {
        // 获取保险公司关联的快递协会
        $associationInsId = $this->getAssociationInsIdsByInsuranceCompany($InsuranceCompanyInsId);
        // 查询快递协会下的快递公司
        $expressIdList = $this->getExpressIdsByAssociation($associationInsId, $ChildAssociation);

        $a = Association::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $associationInsId]]);
        if (!$a) {
            return $expressIdList;
        }
        if ($a->getLevel() == 2) {
            /**
             * 省
             * 1.获取市级邮管局ID
             */
            $model = $this->modelsManager->createBuilder()
                ->columns('i.id')
                ->addFrom("app\models\users\Institution",'i')
                ->leftJoin("app\models\users\Institution",'i.parent_id = i1.id','i1')
                ->Join("app\models\users\Postoffice",'p.ins_id = i1.id and p.level = 3','p')
                ->Join("app\models\users\Postoffice",'p1.province_id = p.province_id','p1')
                ->where('i.type_id = 7 and p1.province_id = :provinceId: and p1.level = 2',['provinceId' =>$a->getProvinceId()])
                ->getQuery()
                ->execute();
            foreach ($model as $item) {
                $expressIdList[] = $item['id'];
            }
        } elseif ($a->getLevel() == 3) {
            /**
             * 市
             */
            $model = $this->modelsManager->createBuilder()
                ->columns('i.id')
                ->addFrom("app\models\users\Institution",'i')
                ->Join("app\models\users\Institution",'i.parent_id = i1.id','i1')
                ->Join("app\models\users\Postoffice",'p.ins_id = i1.id and p.level = 3','p')
                ->where('i.type_id = 7 and p.city_id = :cityId: and p.level = 3',['cityId' =>$a->getCityId()])
                ->getQuery()
                ->execute();
            foreach ($model as $item) {
                $expressIdList[] = $item['id'];
            }
        }
        return $expressIdList;
    }

    /**
     * 获取当前机构下关联的快递公司insIds
     * @param $insId
     * @param null $type
     * @param bool $ChildAssociation 是否
     * @return array|bool
     * @throws DataException
     */
    public function getExpressIdsByInsId($insId, $type=null, $ChildAssociation=true)
    {
        $expressIdList = [];
        if (!($insId > 0)){
            return false;
        }
        if (is_null($type)){
            $ins = Institution::findFirst([
                'id' => $insId,
            ]);
            if (false===$ins){
                return false;
            }
            $type = $ins->getTypeId();
        }
        // 如果是快递公司
        if (7 == $type){
            $expressIdList = [$insId];
        }
        // 如果是快递协会
        if (3 == $type){
            $expressIdList = $this->getExpressIdsByAssociation($insId, $ChildAssociation);
            /**
             * 根据快递协会获取邮管局创建的ID
             */
            $a = Association::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $insId]]);
            if (!$a) {
                return $expressIdList;
            }
            if ($a->getLevel() == 2) {
                /**
                 * 省
                 * 1.获取市级邮管局ID
                 */
                $model = $this->modelsManager->createBuilder()
                    ->columns('i.id')
                    ->addFrom("app\models\users\Institution",'i')
                    ->leftJoin("app\models\users\Institution",'i.parent_id = i1.id','i1')
                    ->Join("app\models\users\Postoffice",'p.ins_id = i1.id and p.level = 3','p')
                    ->Join("app\models\users\Postoffice",'p1.province_id = p.province_id','p1')
                    ->where('i.type_id = 7 and p1.province_id = :provinceId: and p1.level = 2',['provinceId' =>$a->getProvinceId()])
                    ->getQuery()
                    ->execute();
                foreach ($model as $item) {
                    $expressIdList[] = $item['id'];
                }
            } elseif ($a->getLevel() == 3) {
                /**
                 * 市
                 */
                $model = $this->modelsManager->createBuilder()
                    ->columns('i.id')
                    ->addFrom("app\models\users\Institution",'i')
                    ->Join("app\models\users\Institution",'i.parent_id = i1.id','i1')
                    ->Join("app\models\users\Postoffice",'p.ins_id = i1.id and p.level = 3','p')
                    ->where('i.type_id = 7 and p.city_id = :cityId: and p.level = 3',['cityId' =>$a->getCityId()])
                    ->getQuery()
                    ->execute();
                foreach ($model as $item) {
                    $expressIdList[] = $item['id'];
                }
            }
            return array_unique($expressIdList);
        }
        // 如果是邮管局
        if (2 == $type){
            $expressIdList = $this->getExpressIdsByPostoffice($insId);
        }
        // 保险公司
        if (4 == $type){
            $expressIdList = $this->getExpressIdsByInsuranceCompany($insId, $ChildAssociation);
        }
        // 交警队
        if (10 == $type){
            // 获取所属行政区域
            $area = $this->getAreaByInsId($insId, $type);
            // 获取行政区域下的快递协会ids
            $associationInsIds = $this->getAssociationInsIdsByArea($area);
            if (!empty($associationInsIds)){
                // 获取快递协会下的快递公司
                $expressIdList = $this->getExpressIdsByAssociation($associationInsIds);
            }
            // 获取行政区域下的邮管局ids
            $postofficeInsIds = $this->getPostOfficeInsIdsByArea($area);
            if (!empty($postofficeInsIds)) {
                $expressIdList1 = $this->getInstitutionIdListByAssociation($postofficeInsIds,7);
                $expressIdList = array_unique(array_merge($expressIdList,$expressIdList1));
            }
        }
        return $expressIdList ?? false;
    }

    /**
     * 获取当前机构下关联的供应商insIds
     * @param null $type
     * @return array|bool
     * @throws DataException
     */
    public function getSupplierIdsByInsId($insId, $type=null)
    {
        if (!($insId > 0)){
            return false;
        }
        if (is_null($type)){
            $ins = Institution::findFirst([
                'id' => $insId,
            ]);
            if (false===$ins){
                return false;
            }
            $type = $ins->getTypeId();
        }
        // 如果是供应商
        if (5 == $type){
            $supplierIdList = [$insId];
        }
        // 如果是邮管局
        if (2 == $type){
            // 获取邮管局下的快递协会
            $associationInsIds = $this->getAssociationInsIdsByPostoffice($insId);
            // 获取快递协会下的供应商
            $supplierIdList = $this->getSupplierByAssociation($associationInsIds);
        }
        // 保险公司
        if (4 == $type){
            // 获取保险公司所属的快递协会
            $associationInsId = $this->getAssociationInsIdsByInsuranceCompany($insId);
            // 获取快递协会下的供应商
            $supplierIdList = $this->getSupplierByAssociation($associationInsId);
        }
        // 如果是快递协会
        if (3 == $type){
            $supplierIdList = $this->getSupplierByAssociation($insId);
        }
        return $supplierIdList ?? false;
    }

    // 获取当前机构的行政区域
    public function getAreaByInsId($insId, $type=null)
    {
        if (!($insId > 0)){
            return false;
        }
        if (is_null($type)){
            $ins = Institution::arrFindFirst([
                'id' => $insId,
            ]);
            if (false===$ins){
                return false;
            }
            $type = $ins->getTypeId();
        }
        // 如果是快递公司
        if (7 == $type){
            return false;
        }
        // 如果是快递协会
        if (3 == $type){
            $res = Association::arrFindFirst([
                'ins_id' => $insId
            ]);
        }
        // 如果是邮管局
        if (2 == $type){
            $res = Postoffice::arrFindFirst([
                'ins_id' => $insId
            ]);
        }
        // 保险公司
        if (4 == $type){
            $res = Company::arrFindFirst([
                'ins_id' => $insId
            ]);
        }
        // 交警队
        if (10 == $type){
            $res = Trafficpolice::arrFindFirst([
                'ins_id' => $insId
            ]);
        }
        if (!isset($res) || false===$res){
            throw new DataException([500, '机构异常']);
        }
        $res = $res->toArray();
        $area = [];
        if (isset($res['area_id']) && $res['area_id'] > 0){
            $area['areaId'] = $res['area_id'];
        }
        if ($res['city_id'] > 0){
            $area['cityId'] = $res['city_id'];
        }
        if ($res['province_id'] > 0){
            $area['provinceId'] = $res['province_id'];
        }
        return $area;
    }
    /**
     * 批量获取用户名
     * @param $userIds
     * @return array
     */
    public function getUserNameByIds($userIds)
    {
        if (!is_array($userIds)){
            $userIds = [$userIds];
        }
        // 去除0值和重复值
        $userIds = array_values(array_unique(array_diff($userIds,[0])));
        if (empty($userIds)){
            return [];
        }
        $users = User::arrFind([
            'id' => ['IN', $userIds]
        ], 'and', [
            'column' => 'id, real_name'
        ])->toArray();
        $userNames = [];
        foreach ($users as $user){
            $userNames[$user['id']] = $user['real_name'];
        }
        return $userNames;
    }

    // 获取快递公司子系统InsId通过主系统
    /* TODO: 主子合并预废弃
    public function getSubInsIdByMainInsId($MainInsId)
    {
        $subIns = Institution::arrFindFirst([
            'parent_id' => $MainInsId
        ]);
        // TODO:未查到暂不报错
        return $subIns ? $subIns->getId() : false;
    }*/

    public function getCompanyPageByAreaId($json)
    {
        $params = [
            'code' => 10085,
            'parameter' => (Object)$json
        ];
        $result = $this->curl->httpRequest($this->Zuul->user,$params,"post");
        if ($result['statusCode'] != '200') {
            throw new DataException([$result['statusCode'], $result['msg']]);
        }
        if (!isset($result['content']['data']))
            return ['data' => '', 'pageInfo' => '','msg' => ''];
        if (isset($result['content']['pageInfo'])) {
            $pageInfo = $result['content']['pageInfo'];
        } else {
            $pageInfo = '';
        }
        return ['data' => $result['content']['data'], 'pageInfo' => $pageInfo,'msg' => $result['msg']];
    }
}

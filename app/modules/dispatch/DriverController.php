<?php
namespace app\modules\dispatch;


use app\models\dispatch\Drivers;
use app\models\dispatch\DriversAttribute;
use app\models\dispatch\RegionDrivers;
use app\models\service\RegionVehicle;
use app\models\service\Vehicle;
use app\models\users\Institution;
use app\modules\BaseController;
use app\services\data\DriverData;
use app\services\data\RegionData;
use SebastianBergmann\CodeCoverage\Driver\Driver;

//骑手模块
class DriverController extends BaseController
{
    /**
     * 查询骑手 1.5
     * code：60014
     */
    public function listAction()
    {
        $fields = [
            //真实姓名
            'd.real_name' => [
                'as' => 'realName',
            ],
            //账户
            'd.user_name' => [
                'as' => 'userName',
            ],
            //状态 1启用 2禁用
            'd.status' => [
                'as' => 'status',
            ],
            // 性别 1男 2女
            'd.sex' => [
                'as' => 'sex',
            ],
            // 手机号
            'd.phone' => [
                'as' => 'phone',
            ],
            // 身份证号
            'd.identify' => [
                'as' => 'identify',
            ],
        ];
        $parameter = $this->getArrPars($fields, $_GET);
        $where = $this->arrToQuery($parameter);
        $pageSize = isset($_GET['pageSize'])&&$_GET['pageSize']>0 ? $_GET['pageSize'] : 20;
        $pageNum = isset($_GET['pageNum'])&&$_GET['pageNum']>0 ? $_GET['pageNum'] : 1;
        $useAttribute = $this->request->getQuery("useAttribute",null,1);
        // 有区域
        if (isset($this->authed->regionId) && $this->authed->regionId>0){
            // 查询下属站点
            $regionIds = (new RegionData())->getBelongRegionIdsByRegionId($this->authed->regionId, $this->authed->insId);
        }
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Drivers','d')
            ->where($where['conditions'], $where['bind'])
            ->leftJoin('app\models\dispatch\DriversIdentification', 'di.driver_id=d.id','di')
            ->leftJoin('app\models\dispatch\DriversAttribute', 'da.driver_id=d.id','da')
            ->andWhere('da.type_id = :type_id:',['type_id' => $useAttribute]);
        if (isset($regionIds)){
            $model = $model->join('app\models\dispatch\RegionDrivers', 'rd.driver_id = d.id','rd')
                ->andWhere('rd.ins_id = :insID: and rd.region_id IN ({regionIds:array})',
                    ['regionIds'=>$regionIds, 'insID'=>$this->authed->insId]);
        }elseif ($this->authed->insId>0 && $this->authed->userType != 11){
            $model = $model->join('app\models\dispatch\RegionDrivers', 'rd.driver_id = d.id','rd')
                ->andWhere('rd.ins_id = :insID:', ['insID'=>$this->authed->insId]);
        } elseif ($this->authed->insId>0 && $this->authed->userType == 11){
            /**
             * 根据父级ID 查询 子机构ID
             */
            $insID = [];
            $insList = Institution::find(['columns' => 'id','conditions' => 'parent_id = :parentId:','bind' => ['parentId' => $this->authed->insId]])->toArray();
            foreach ($insList as $item) {
                $insID[] = $item;
            }
            $model = $model->join('app\models\dispatch\RegionDrivers', 'rd.driver_id = d.id','rd')
                ->andWhere('rd.ins_id IN ({insID:array})', ['insID'=>$insID]);
        }
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(d.id) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        // 查询数据
        $res = $model->columns('d.id, d.user_name, d.real_name, d.phone, d.identify, d.sex, d.status, d.create_time, di.is_authentication')
            ->orderBy('d.create_time DESC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()
            ->execute()
            ->toArray();
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        $fields = [
            'id' => '',
            'userName' => [
                'as' => 'user_name'
            ],
            'realName' => [
                'as' => 'real_name'
            ],
            'phone' => '',
            'identify' => '',
            'sex' => [
                'fun' => [
                    '1' => '男',
                    '2' => '女'
                ]
            ],
            'status' => [
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用'
                ]
            ],
            'createTime' => [
                'as' => 'create_time',
                'fun' => 'time',
            ],
            // 1:未实人认证 2：实人认证过
            'isAuthentication' => [
                'as' => 'is_authentication',
                'def' => 1
            ]
        ];
        $list = [];
        foreach ($res as $key => $value){
            // 身份证掩码
            $value['identify'] = $this->hideIDnumber($value['identify']);
            $list[$key] = $this->backData($fields,$value);
        }
        if (!isset($_GET['needVehicle'])){
            return $this->toSuccess($list,$meta);
        }
        // 如果需要关联车辆信息
        $driverIdList = [];
        foreach ($list as $k => $driver){
            // 默认没车
            $list[$k]['hasVehicle'] = false;
            $driverIdList[] = $driver['id'];
        }
        if (count($driverIdList)>0){
            // 查询骑手的车辆
            $result = $this->curl->httpRequest($this->Zuul->vehicle ,[
                'code' => 60010,
                'parameter' => [
                    'driverIdList' => $driverIdList,
                ]
            ], "post");
            // 失败返回
            if ('200'!=$result['statusCode']) {
                return $this->toError(500, '车辆服务异常-60010');
            }
            $DV = [];
            foreach ($result['content']["vehicleDOS"] as $vehicle){
                $DV[$vehicle['driverId']] = $vehicle;
            }
            foreach ($list as $k => $driver){
                // 如果有车
                if (isset($DV[$driver['id']])){
                    $list[$k]['hasVehicle'] = true;
                    $list[$k]['vehicleId'] = $DV[$driver['id']]['id'];
                }
            }
        }
        return $this->toSuccess($list,$meta);
    }

    /**
     * 骑手详情 1.5
     */
    public function OneAction($id)
    {
        $driver =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Drivers','d')
            ->where('d.id = :driver_id:', ['driver_id'=>$id])
            ->join('app\models\dispatch\RegionDrivers', 'rd.driver_id = d.id','rd')
            ->andWhere('rd.ins_id = :insID:', ['insID'=>$this->authed->insId])
            // 查询关联站点
            ->leftJoin('app\models\dispatch\Region', 'rd.region_id = r.id','r')
            // 查询关联区域
            ->leftJoin('app\models\dispatch\Region', 'pr.id = r.parent_id','pr')
            ->columns('d.id, d.user_name AS userName, d.real_name AS realName, d.phone, d.identify, d.remark, d.img_opposite_url AS imgOppositeUrl, d.img_front_url AS imgFrontUrl, d.sex, d.status, d.create_time AS createTime, d.email, r.id AS siteId, r.region_name AS siteName, r.parent_id AS regionId, pr.region_level AS regionLevel, pr.parent_id AS parentId, pr.region_name AS regionName')
            ->getQuery()
            ->getSingleResult();
        if (false===$driver){
            return $this->toError(500, '未查到骑手信息');
        }
        $driver = $driver->toArray();
        //结果处理返回
        $fields = [
            'id' => '',
            'jobNo' => 0,
            'userName' => '',
            'realName' => '',
            'phone' => 0,
            'identify' => 0,
            'siteId' => 0,
            'regionId' => 0,
            'siteName' => '',
            'email' => '',
            'remark' => '',
            'regionLevel' => 0,
            'parentId' => 0,
            'regionName' => '',
            'imgOppositeUrl' => '',
            'imgFrontUrl' => '',
            'sex' => [
                'fun' => [
                    '1' => '男',
                    '2' => '女'
                ]
            ],
            'status' => [
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用'
                ]
            ],
            'createTime' => [
                'fun' => 'time'
            ]
        ];
        $list = $this->backData($fields,$driver);
        return $this->toSuccess($list);
    }


    /**
     * 新增骑手1.5
     *
     */
    public function CreateAction()
    {
        $request = $this->request->getJsonRawBody(true);
        // 参数处理
        $fields = [
            'userName' => '请输入用户名',
            'status' => '请选择状态',
            'realName' => '请输入骑手真实姓名',
            'sex' => '请选择骑手性别', //1男 2女
            'identify' => '请输入身份证号码',
            'phone' => '请输入手机号',
            'email' => 0,
            'regionLevel' => '请选择区域级别',
            'regionId' => '请选择所属区域',
            'siteId' => '请选择所属站点',
            'remark' => 0,
        ];
        $parameter = $this->getArrPars($fields, $request);
        // 机构id
        $parameter['insId'] = $this->authed->insId;

        // 校验身份证号是否合规
        if (false == $this->isIdCard($parameter['identify'])){
            return $this->toError(500, '身份证号码校验不通过');
        }
        // 查询身份证号/手机号重复
        $repeatDriver = Drivers::arrFindFirst([
            'identify' => $parameter['identify'],
            'phone' => $parameter['phone'],
        ], 'or');
        if ($repeatDriver){
            if ($repeatDriver->phone != $parameter['phone']){
                return $this->toError(500, '当前身份证已关联其它手机号，请联系管理员');
            }
            if ($repeatDriver->identify != $parameter['identify'] && '' != $repeatDriver->identify){
                return $this->toError(500, '手机号已被其它骑手绑定，请联系管理员');
            }
        }

        // 开启事务
        $this->dw_dispatch->begin();
        $this->dw_service->begin();
        // 查询骑手
        $driver = Drivers::arrFindFirst(['phone'=>$parameter['phone']]);
        // 没有骑手，新增骑手
        if (false===$driver){
            $driver = new Drivers();
            $driver->phone = $parameter['phone'];
            $driver->password = $this->security->hash(123456);
            $driver->create_time = time();
        }
        $driver->user_name = $parameter['userName'] ?? $driver->user_name ?? '';
        $driver->sex = $parameter['sex'] ?? $driver->sex;
        $driver->real_name = $parameter['realName'] ?? $driver->real_name;
        $driver->identify = $parameter['identify'] ?? $driver->identify ?? '';
        $driver->email = $parameter['email'] ?? $driver->email ?? '';
        $driver->remark = $parameter['remark'] ?? $driver->remark ?? '';
        $driver->status = $parameter['status'] ?? $driver->status;
        $driver->update_time = time();
        $bol = $driver->save();
        if (false===$bol){
            // 事务回滚
            $this->dw_dispatch->rollback();
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }
        // 绑定骑手站点关系【含业务解绑】
        $bol = $this->BindDriverSiteRelation($driver->id, $parameter['siteId'], $this->authed->insId);
        if (false===$bol){
            // 事务回滚
            $this->dw_dispatch->rollback();
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }

        /**
         * 向骑手关系表里添加数据（邮管局）  骑手多用途
         * 插入 不能发版注释
         */
        $DA = DriversAttribute::findFirst(['conditions' => 'driver_id = :driver_id: and type_id = 1','bind' => ['driver_id' =>$driver->id]]);
        if (!$DA) {
            $DA = new DriversAttribute();
            $DA->driver_id = $driver->id;
            $DA->type_id = 1;
            $DA->create_time = time();
            if ($DA->save() == false) {
                $this->dw_dispatch->rollback();
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }

        // 提交事务
        $this->dw_dispatch->commit();
        $this->dw_service->commit();

        /**
         * 向志辉推送
         */
        $parameter = ['driverId' => $driver->id,'eventType' => 'A'];
        $result = $this->CallService('biz', 10311, $parameter, false);

        return $this->toSuccess();
    }

    /**
     * 绑定骑手站点关系【含业务解绑】
     * @param $driverId 骑手
     * @param $siteId 站点id
     * @param null $insId 机构insid
     * @return bool
     */
    private function BindDriverSiteRelation($driverId, $siteId, $insId=null)
    {
        $insId = $insId ?? $this->authed->insId;
        // 查询是否有区域绑定关系
        $RD = RegionDrivers::arrFindFirst([
            'driver_id' => $driverId,
        ]);
        // 有关系且与提交不一致，删除关系
        if (false !== $RD &&
            ($RD->ins_id != $insId || $RD->region_id != $siteId)){
            $hasDelRelation = true;
            // 删除骑手所有邮管业务关系
            $bol = (new DriverData())->DelPostOfficeDriverRelation($driverId);
            if (false===$bol){
                return $bol;
            }
        }
        // 无区域关系 || 已删除关系  新建区域骑手关系
        if (false==$RD || isset($hasDelRelation)){
            $RD = new RegionDrivers();
            $RD->driver_id = $driverId;
            $RD->ins_id = $insId;
            $RD->create_time = time();
            $RD->region_id = $siteId;
            $RD->update_time = time();
            $bol = $RD->save();
            if (false===$bol){
                return $bol;
            }
        }
        return true;
    }


    /**
     * 修改骑手1.5
     *
     */
    public function UpdateAction($id)
    {
        $request = $this->request->getJsonRawBody(true);
        // 查出骑手
        $driver = Drivers::findFirst($id);
        if(false===$driver){
            return $this->toError(500, '骑手不存在');
        }
        // 变更手机号排重
        if (isset($request['phone']) && $request['phone'] != $driver->phone){
            $count = Drivers::count([
                'phone = :phone: and id != :id:',
                'bind' => [
                    'phone' => $request['phone'],
                    'id' => $driver->id,
                ]
            ]);
            if ($count > 0){
                return $this->toError(500, '手机号已被使用');
            }
        }

        if (isset($request['resetPWD'])) $driver->password = $this->security->hash(123456);
        if (isset($request['userName'])) $driver->user_name = $request['userName'];
        if (isset($request['status'])) $driver->status = $request['status'];
        if (isset($request['realName'])) $driver->real_name = $request['realName'];
        if (isset($request['sex'])) $driver->sex = $request['sex'];
        if (isset($request['phone'])) $driver->phone = $request['phone'];
        if (isset($request['email'])) $driver->email = $request['email'];
        if (isset($request['remark'])) $driver->remark = $request['remark'];
        if (isset($request['identify'])){
            // 校验身份证号是否合规
            if (false == $this->isIdCard($request['identify'])){
                return $this->toError(500, '身份证号码校验不通过');
            }
            // 查询身份证号是否被占用
            $repeatDriver = Drivers::arrFindFirst([
                'identify' => $request['identify'],
                'id' => ['!=', $id],
            ]);
            if ($repeatDriver){
                return $this->toError(500, '身份证号已被使用');
            }
            $driver->identify = $request['identify'];
        };
        $driver->update_time = time();

        // 开启事务
        $this->dw_dispatch->begin();
        $this->dw_service->begin();
        $bol = $driver->save();
        if (false===$bol){
            // 事务回滚
            $this->dw_dispatch->rollback();
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }
        // 启禁用不操作站点
        if (isset($request['siteId'])){
            // 绑定骑手站点关系【含业务解绑】
            $bol = $this->BindDriverSiteRelation($id, $request['siteId'], $this->authed->insId);
            if (false===$bol){
                // 事务回滚
                $this->dw_dispatch->rollback();
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
        }
        // 提交事务
        $this->dw_dispatch->commit();
        $this->dw_service->commit();


        /**
         * 向志辉推送
         */
        if (count($request) > 1) {
            $parameter = ['driverId' => $driver->id,'eventType' => 'U'];
            $result = $this->CallService('biz', 10311, $parameter, false);
        }


        return $this->toSuccess(200, '更新成功' );
    }

    /**
     * 删除骑手 1.5
     *
     */
    public function DeleteAction($id)
    {
        // 骑手可能有多机构 仅删除骑手与当前机构的关系
        // 开启事务
        $this->dw_dispatch->begin();
        $this->dw_service->begin();
        // 查询骑手在本机构的关系
        $RD = RegionDrivers::arrFindFirst([
            'driver_id' => $id,
            'ins_id' => $this->authed->insId,
        ]);
        if (false===$RD){
            return $this->toError(500, '非本机构骑手，无需删除');
        }
        // 删除骑手区域关系
        $bol = $RD->delete();
        if (false===$bol){
            // 事务回滚
            $this->dw_dispatch->rollback();
            $this->dw_service->rollback();
            return $this->toError(500, '操作失败');
        }
        // 查询与本机构的车辆关系
        $RV = RegionVehicle::arrFindFirst([
            'driver_id' => $id,
            'ins_id' => $this->authed->insId,
        ]);
        // 解除机构车辆-骑手关系
        if ($RV){
            $RV->driver_id = 0;
            $RV->bind_status = 1;
            $RV->bind_time = 0;
            $RV->update_time = time();
            $bol = $RV->save();
            if (false===$bol){
                // 事务回滚
                $this->dw_dispatch->rollback();
                $this->dw_service->rollback();
                return $this->toError(500, '操作失败');
            }
            // 查询绑定车辆
            $Vehicle = Vehicle::arrFindFirst([
                'id' => $RV->vehicle_id,
            ]);
            // 解除车辆的骑手关系
            if ($Vehicle){
                $Vehicle->driver_bind = 1;
                $Vehicle->driver_id = 0;
                $Vehicle->update_time = time();
                $bol = $Vehicle->save();
                if (false===$bol){
                    // 事务回滚
                    $this->dw_dispatch->rollback();
                    $this->dw_service->rollback();
                    return $this->toError(500, '操作失败');
                }
            }
        }
        // 提交事务
        $this->dw_dispatch->commit();
        $this->dw_service->commit();
        return $this->toSuccess();
    }

    /**
     * APP获取站点骑手列表 1.5
     */
    public function DriversAction()
    {
        $pageSize = isset($_GET['pageSize'])&&$_GET['pageSize']>0 ? $_GET['pageSize'] : 20;
        $pageNum = isset($_GET['pageNum'])&&$_GET['pageNum']>0 ? $_GET['pageNum'] : 1;
        $regionId = (new RegionData())->getRegionIdByUserId($this->authed->userId);
        $model =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\dispatch\Drivers','d')
            ->join('app\models\dispatch\RegionDrivers', 'rd.driver_id = d.id','rd')
            ->where('rd.ins_id = :insID: and rd.region_id = :region_id:',
                ['region_id'=>$regionId, 'insID'=>$this->authed->insId]);
        // 查询总数
        $modelCount= clone $model;
        $countRes = $modelCount->columns('count(d.id) as count')->getQuery()->execute()->toArray();
        $count = $countRes[0]['count'];
        // 查询数据
        $res = $model->columns('d.*')
            ->orderBy('d.id ASC')
            ->limit($pageSize, ($pageNum-1)*$pageSize)
            ->getQuery()
            ->execute()
            ->toArray();
        //结果处理返回
        $meta = [
            'pageNum'=> $pageNum,
            'total' => $count,
            'pageSize' => $pageSize
        ];
        $fields = [
            'id' => '',
            'userName' => [
                'as' => 'user_name'
            ],
            'realName' => [
                'as' => 'real_name'
            ],
            'phone' => '',
            'identify' => '',
            'sex' => [
                'fun' => [
                    '1' => '男',
                    '2' => '女'
                ]
            ],
            'status' => [
                'fun' => [
                    '1' => '启用',
                    '2' => '禁用'
                ]
            ],
            'createTime' => [
                'as' => 'create_time',
                'fun' => 'time',
            ],
        ];
        $list = [];
        foreach ($res as $key => $value){
            // 身份证掩码
            $value['identify'] = $this->hideIDnumber($value['identify']);
            $list[$key] = $this->backData($fields,$value);
        }
        return $this->toSuccess($list, $meta);
    }

    /**
     * 获取骑手身份证号
     * @param $driverId
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function DriveridentifyAction($driverId)
    {
        $Driver = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => 60014,
            'parameter' => [
                'id' => $driverId,
            ]
        ],"post");
        if (!isset($Driver['statusCode']) || $Driver['statusCode'] != '200' || 1!=count($Driver['content']['driversDOS'])) {
            return $this->toError(500,'获取骑手信息失败'.$driverId);
        }
        $Driver = $Driver['content']['driversDOS'][0];
        $data = [
            'identify' => $Driver['identify'],
        ];
        return $this->toSuccess($data);
    }

    public function SelectlistAction()
    {
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'real_name' => [
                'as' => 'realName'
            ],
            'identify' => 0,
        ];
        $params = [];
        // 预置默认条件查询关系
        $relation = 'and';
        // 通配优先级最高
        if (isset($_GET['searchText']) && ''!==$_GET['searchText']){
            // 通配时，or查询
            $relation = 'or';
            $searchText = $_GET['searchText'];
            foreach ($fields as $k => $v){
                $params[$k] = $searchText;
            }
        }else{
            $params = $this->getArrPars($fields, $_GET);
        }
        // 条件
        $where = [];
        foreach ($params as $k => $param){
            $where[$k] = ['LIKE', '"%'.$param.'%"'];
        }
        $count = Drivers::count(Drivers::dealWithWhereArr($where, $relation));
        $assist = [
            'columns' => 'id, user_name AS userName, identify, real_name AS realName'
        ];
        // 分页
        if (!isset($request['list']) || !$request['list']){
            $pageSize = $_GET['pageSize'] ?? 18;
            $pageNum = $_GET['pageNum'] ?? 1;
            $offset = $pageSize*($pageNum-1);
            $assist['limit'] = [$pageSize, $offset];
            $meta = [
                'total' => $count,
                'pageNum' => $pageNum,
                'pageSize' => $pageSize
            ];
        }
        $list = Drivers::arrFind($where, $relation, $assist)->toArray();
        // 是否掩码身份证号
        if (!isset($request['NotMask'])){
            foreach ($list as $k => $item){
                $list[$k]['identify'] = $this->hideIDnumber($item['identify']);
            }
        }
        return $this->toSuccess($list, $meta ?? []);
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     * @throws \app\common\errors\DataException
     * 骑手导入
     */
    public function LeadInAction()
    {
        // todo hack 只让子用户使用 有毒
//        if ($this->authed->isAdministrator == 2) {
//            return $this->toError(500,"快递公司不能导入骑手，只有站长才能导入骑手");
//        }
        $json = $this->request->getJsonRawBody(true);
        $json['siteId'] = $this->authed->regionId;
        $json['insId'] = $this->authed->insId;
        $result = $this->userData->postCommon($json,$this->Zuul->dispatch,"60065");
        return $this->toSuccess($result['data'],null,200,$result['msg']);
    }


}

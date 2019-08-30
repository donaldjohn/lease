<?php
namespace app\modules\shrent;


use app\modules\BaseController;
use app\services\data\AlipayData;
use app\services\data\DriverData;
use app\services\data\WxpayData;
use app\services\data\PackageData;
use app\services\data\BillData;
use app\services\data\RentWarrantyData;
use app\services\data\StoreData;
use app\services\data\CabinetData;
use app\common\errors\DataException;
use app\models\service\VehicleLockQueue;
use app\models\service\Vehicle;
use app\models\service\RegionVehicle;
use app\models\dispatch\RegionDrivers;

//骑手APP模块
class TransactionController extends BaseController
{
    /**
     * 骑手APP发起支付【OK】
     */
    public function ApppayAction()
    {
        // 获取骑手id
        $driverId = $this->authed->userId;
//        $driverId = 5;
        $request = $this->request->getJsonRawBody(true);
        $fields = [
            'businessSn' => '未收到支付单号',
            'payType' => '未选择支付类型',
        ];
        $request = $this->getArrPars($fields, $request);
        if (false === $request){
            return;
        }
        // 获取账单信息
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10024,
            'parameter' => [
                'businessSn' => $request['businessSn'],
                'payerId' => $driverId,
            ],
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        // 如果账单所有者不是当前用户，报错返回
        if ($result['content']['data']['driverId'] != $driverId){
            return $this->toError(500, '账单信息异常');
        }
        // 账单编号
        $businessSn = $result['content']['data']['businessSn'];
        // 组装订单信息
        $order = [
            'orderNo' => $businessSn,
            // 金额 支付类会转换单位
            'amount' => $result['content']['data']['totalAmount'],
            'title' => '得威服务账单',
            'body' => date('Y-m-d H:i:s', time()),
        ];
        // 判断支付方式
        switch ($request['payType']){
            case 'Alipay':
            case 1:
                $pay = (new AlipayData())->StartAppPay($order);
                break;
            case 'Wechat':
            case 2:
                $pay = (new WxpayData())->StartAppPay($order);
                break;
            default :
                return $this->toError(500,'未知的支付类型');
        }
        // 接口异常返回
        if ('' == $pay){
            return $this->toError(500, '接口异常');
        }
        // 返回交易信息
        return $this->toSuccess(['sdk'=>$pay]);
    }

    /**
     * 实人认证
     */
    public function PersoncertAction()
    {
        // 获取套餐详情列表
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10012",
            'parameter' => [
                // 占位
                's' => 1
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        $data = $result['content'];
        return $this->toSuccess($data);
    }

    /**
     * 骑手实人认证完成
     */
    public function PersoncertedAction()
    {
        $driverId = $this->authed->userId;
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['ticketId'])){
            return $this->toError(500,'未收到ticketId');
        }

        // 查询认证结果
        $result = $this->curl->httpRequest($this->Zuul->biz,[
            'code' => "10026",
            'parameter' => [
                'biz' => 'RealManIdentify',
                'ticketId' => $request['ticketId']
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'],$result['msg']);
        }
        // 认证状态(-1 未认证, 0 认证中, 1 认证通过, 2 认证不通过)
        $status = $result['content']['status'];

        if (1 != $status){
            return $this->toError(500,'处理认证失败');
        }
        // 更新骑手表的信息
        $upRes = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => "60012",
            'parameter' => [
                'insId' => '-1', // 接口必传，否则报错
                'id' => $driverId,
                'realName' => $result['content']['realPersonMaterial']['name'],
                'identify' => $result['content']['realPersonMaterial']['identificationNumber'],
                'imgOppositeUrl' => $result['content']['realPersonMaterial']['idCardFrontPic'],
                'imgFrontUrl' => $result['content']['realPersonMaterial']['idCardBackPic'],
                // m 代表男性，f 代表女性 性别:1男 2女
                'sex' => 'm'==$result['content']['realPersonMaterial']['sex'] ? 1 : 2,
            ]
        ],"post");
        // 失败返回
        if ($upRes['statusCode'] != '200') {
            return $this->toError(500, '骑手信息更新失败');
        }
        // 查询用户是否已经有实名认证信息
        $info = $this->curl->httpRequest($this->Zuul->dispatch,[
            'code' => "60064",
            'parameter' => [
                'driverIdList' => [$driverId]
            ]
        ],"post");
        // 判断骑手是否存在认证信息记录 有 - 修改同步信息  无 - 新增记录
        if ($info['statusCode'] == 200 && isset($info['content']['data'][0])) {
            // 更新骑手的信息
            $params = [
                'id'                    => $info['content']['data'][0]['id'],
                'driverId'              => $info['content']['data'][0]['driverId'],
                'identificationNumber'  => $result['content']['realPersonMaterial']['identificationNumber'],
                'isAuthentication'      => 2,
                'isGetmaterials'        => $info['content']['data'][0]['isGetmaterials'],
                'idCardType'            => $result['content']['realPersonMaterial']['idCardType'],
                'address'               => $result['content']['realPersonMaterial']['address']['province']['text']
                                          .$result['content']['realPersonMaterial']['address']['city']['text']
                                          .$result['content']['realPersonMaterial']['address']['area']['text']
                                          .$result['content']['realPersonMaterial']['detail'],
                'idCardFrontPic'        => $result['content']['realPersonMaterial']['idCardFrontPic'],
                'idCardBackPic'         => $result['content']['realPersonMaterial']['idCardBackPic'],
                'facePic'               => $result['content']['realPersonMaterial']['facePic'],
                'ethnicGroup'           => $result['content']['realPersonMaterial']['ethnicGroup'],
                'getmaterialsTime'      => time(),
                'idCardStartDate'       => $result['content']['realPersonMaterial']['idCardStartDate'],
                'idCardExpiry'          => $result['content']['realPersonMaterial']['idCardExpiry'],
                'sex'                   => $result['content']['realPersonMaterial']['sex'],
                'provinceId'            => $result['content']['realPersonMaterial']['address']['province']['value'],
                'cityId'                => $result['content']['realPersonMaterial']['address']['city']['value'],
            ];

            // 请求微服务接口，更新骑手认证信息记录
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => "60062",
                'parameter' => $params
            ],"post");

            if ($result['statusCode'] == 200) {
                return $this->toSuccess($result['msg']);
            } else {
                return $this->toError(500, $result['msg']);
            }
        } else {
            // 新增骑手认证信息
            $params = [
                'driverId'              => $this->authed->userId,
                'identificationNumber'  => $result['content']['realPersonMaterial']['identificationNumber'],
                'isAuthentication'      => 2,
                'isGetmaterials'        => 2,
                'idCardType'            => $result['content']['realPersonMaterial']['idCardType'],
                'address'               => $result['content']['realPersonMaterial']['address']['province']['text']
                    .$result['content']['realPersonMaterial']['address']['city']['text']
                    .$result['content']['realPersonMaterial']['address']['area']['text']
                    .$result['content']['realPersonMaterial']['detail'],
                'idCardFrontPic'        => $result['content']['realPersonMaterial']['idCardFrontPic'],
                'idCardBackPic'         => $result['content']['realPersonMaterial']['idCardBackPic'],
                'facePic'               => $result['content']['realPersonMaterial']['facePic'],
                'ethnicGroup'           => $result['content']['realPersonMaterial']['ethnicGroup'],
                'getmaterialsTime'      => time(),
                'sex'                   => $result['content']['realPersonMaterial']['sex'],
                'provinceId'            => $result['content']['realPersonMaterial']['address']['province']['value'],
                'cityId'                => $result['content']['realPersonMaterial']['address']['city']['value'],
                'idCardStartDate'       => $result['content']['realPersonMaterial']['idCardStartDate'],
                'idCardExpiry'          => $result['content']['realPersonMaterial']['idCardExpiry'],
            ];

            // 请求微服务接口新增骑手认证记录
            $result = $this->curl->httpRequest($this->Zuul->dispatch,[
                'code' => "60061",
                'parameter' => $params
            ],"post");

            if ($result['statusCode'] == 200) {
                return $this->toSuccess($result['msg']);
            } else {
                return $this->toError(500, $result['msg']);
            }
        }
    }

    /**
     * 取消支付
     */
    public function CancelpayAction()
    {
        // 获取骑手ID
         $driverId = $this->authed->userId;
        // 获取传递参数
        $request = $this->request->getJsonRawBody(true);
        if (!isset($request['businessSn'])){
            return $this->toError(500,'未收到账单编号');
        }
        // 查询支付单
        $bill = (new BillData())->getAppBillBySn($request['businessSn']);
        // 判断服务单是否属于当前骑手
        if ($driverId != $bill['serviceContract']['driverId']){
            return $this->toError(500,'订单不属于当前骑手');
        }
        // 判断支付单是否不处于未支付状态
        if (1 != $bill['payBill']['payStatus']){
            return $this->toError(500,'当前订单状态不可取消');
        }
        // 取消支付单
        $result = $this->curl->httpRequest($this->Zuul->order,[
            'code' => 10029,
            'parameter' => [
                'businessSn' => $request['businessSn'],
            ]
        ],"post");
        // 失败返回
        if ($result['statusCode'] != '200') {
            return $this->toError(500,'取消单据失败');
        }
        // 预测试关闭支付宝订单，暂不影响业务逻辑
        (new AlipayData())->Close($request['businessSn']);
        return $this->toSuccess();
    }

}

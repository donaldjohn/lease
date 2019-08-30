<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: ReturnCodeService.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\common\library;

class ReturnCodeService
{
    const USER_CREATE_USER = 10001;
    const USER_DELETE_USER = 10002;
    const USER_READ_USER = 10003;
    const USER_UPDATE_USER = 10004;

    const USER_CREATE_ROLE = 10005;
    const USER_DELETE_ROLE = 10006;
    const USER_READ_ROLE = 10007;
    const USER_UPDATE_ROLE = 10008;







    const WARRANTY_READ_VEHICLE_TYPE = 10045;    //车辆类型
    const WARRANTY_READ_VEHICLE_AREA = 10046;    //车辆区域

    const WARRANTY_OUT_PRODUCTS = 10038;    //商品导出

    const WARRANTY_CREATE_VEHICLE_ELEMENT = 10040;  //新增车辆架构
    const WARRANTY_DELETE_VEHICLE_ELEMENT = 10041;  //删除车辆架构
    const WARRANTY_READ_VEHICLE_ELEMENT = 10044;    //查询车辆架构
    const WARRANTY_UPDATE_VEHICLE_ELEMENT = 10042;  //编辑车辆架构
    const WARRANTY_STATUS_VEHICLE_ELEMENT = 10043;  //启用禁用
    const WARRANTY_ORDER_VEHICLE_ELEMENT = 10059;
    const WARRANTY_OUT_VEHICLE_ELEMENT = 10063;   //架构导出

    const WARRANTY_READ_CUSTOMER = 10047; //来源客户

    const WARRANTY_OUT_ = 10045;    //车辆类型


    const WARRANTY_CREATE_BOMS = 10048;     //创建bom
    const WARRANTY_DELETE_BOMS = 10051;     //删除bom
    const WARRANTY_READ_BOMS_ID = 10052;       //获取bom详情
    const WARRANTY_READ_BOMS = 10056;       //获取bom详情
    const WARRANTY_UPDATE_BOMS = 10050;     //更新bom
    const WARRANTY_STATUS_BOMS = 10053;     //更新bom状态
    const WARRANTY_OUT_BOMS = 10064;        //导出
    const WARRANTY_IN_BOMS = 10065;        //导入


    const WARRANTY_CREATE_ORDERS = 11021;     //联保订单
    const WARRANTY_READ_ORDERS_ID = 11025;
    const WARRANTY_DELETE_ORDERS = 10000;
    const WARRANTY_READ_ORDERS = 11024;
    const WARRANTY_UPDATE_ORDERS = 11023;
    const WARRANTY_STATUS_ORDERS = 11026;


    const WARRANTY_CREATE_ORDERS_SCHEMES = 11031;     //联保方案
    const WARRANTY_READ_ORDERS_SCHEMES_ID = 11035;
    const WARRANTY_DELETE_ORDERS_SCHEMES = 11032;
    const WARRANTY_READ_ORDERS_SCHEMES = 11034;
    const WARRANTY_UPDATE_ORDERS_SCHEMES = 11033;
    const WARRANTY_STATUS_ORDERS_SCHEMES = 11036;
    const WARRANTY_SCHEMES_REGION = 11051;

    const WARRANTY_READ_PRICES = 10019;    //配件价格
    const WARRANTY_UPDATE_PRICES = 10022;
    const WARRANTY_DELETE_PRICES = 10023;
    const WARRANTY_CREATE_PRICES = 10024;
    const WARRANTY_STATUS_PRICES = 10021;
    const WARRANTY_OUT_PRICES = 10039;      //价格导出
    const WARRANTY_IN_PRICES = 10041;      //价格导入


    const WARRANTY_CREATE_SCHEMES = 11001;     //创建方案
    const WARRANTY_DELETE_SCHEMES = 11003;     //删除方案
    const WARRANTY_READ_SCHEMES = 11004;       //获取方案
    const WARRANTY_UPDATE_SCHEMES = 11002;     //更新方案
    const WARRANTY_STATUS_SCHEMES = 11005;     //更新方案状态
    const WARRANTY_READ_SCHEMES_ID = 11006;     //更新方案状态
    const WARRANTY_OUT_SCHEMES = 11009;     //更新方案状态
    const WARRANTY_IN_SCHEMES = 11008;     //更新方案状态



    const WARRANTY_CREATE_PRODUCT = 10010;


    const WARRANTY_CREATE_CUSTOMER_RELATION = 10032;

}
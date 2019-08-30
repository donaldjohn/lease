<?php
namespace app\modules\postofficeapp;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        // 骑手登录
        $this->addRoutes('/login/SMS',[
            'POST' => ['controller' => 'driver', 'action' => 'GetSMSCodeCode'],
        ]);
        // 骑手登录
        $this->addRoutes('/login',[
            'POST' => ['controller' => 'driver', 'action' => 'Login'],
        ]);
        // 骑手注册
        $this->addRoutes('/registration',[
            'POST' => ['controller' => 'driver', 'action' => 'Registration'],
        ]);
        // 重置密码
        $this->addRoutes('/ResetPassword',[
            'POST' => ['controller' => 'driver', 'action' => 'ResetPassword'],
        ]);
        // 骑手绑车
        $this->addRoutes('/BindVehicle',[
            'POST' => ['controller' => 'driver', 'action' => 'BindVehicle'],
        ]);

        // 附近门店
        $this->addRoutes('/NearbyStore',[
            'POST' => ['controller' => 'Store', 'action' => 'NearbyStore'],
        ]);

        $this->addRoutes('/store/city',[
            'GET' => ['controller' => 'Store', 'action' => 'city'],
        ]);

        $this->addRoutes('/needAuth',[
            'GET' => ['controller' => 'Store', 'action' => 'NeedAuth'],
        ]);

        $this->addRoutes('/display',[
            'GET' => ['controller' => 'Store', 'action' => 'display'],
        ]);
        $this->addRoutes('/inspection',[
            // 年检信息
            'GET' => ['controller' => 'driver', 'action' => 'InspectionInfo'],
            // 提交年检
            'POST' => ['controller' => 'driver', 'action' => 'SubmitInspection'],
        ]);

        // 首页
        $this->addRoutes('/index',[
            'GET' => ['controller' => 'index', 'action' => 'index'],
        ]);

        // 车辆信息
        $this->addRoutes('/vehicle/info',[
            'GET' => ['controller' => 'driver', 'action' => 'info'],
        ]);

        // 骑手解绑车辆
        $this->addRoutes('/driver/untiedvehicle',[
            'POST' => ['controller' => 'driver', 'action' => 'untiedvehicle'],
        ]);

        // 骑手驾照
        $this->addRoutes('/driver/licence',[
            'GET' => ['controller' => 'driver', 'action' => 'LicenceDetail'],
        ]);
        // 骑手锁车
        $this->addRoutes('/driver/vehicle/lock',[
            'POST' => ['controller' => 'driver', 'action' => 'LockVehicle'],
        ]);
        // 骑手解锁车辆
        $this->addRoutes('/driver/vehicle/unLock',[
            'POST' => ['controller' => 'driver', 'action' => 'UnLockVehicle'],
        ]);

        // 骑手个人信息
        $this->addRoutes('/driver/info',[
            'GET' => ['controller' => 'driver', 'action' => 'DriverInfo'],
        ]);
        //获取考试信息
        $this->addRoutes('/exam/info',[
            'GET' => ['controller' => 'exam', 'action' => 'info'],
        ]);
        //获取考试信息
        $this->addRoutes('/exam/history',[
            'GET' => ['controller' => 'exam', 'action' => 'history'],
        ]);
        //获取题目信息,打分
        $this->addRoutes('/exam/question',[
            'GET' => ['controller' => 'exam', 'action' => 'question'],
            'POST' => ['controller' => 'exam', 'action' => 'save']
        ]);

        //模拟考试获取题目信息,打分
        $this->addRoutes('/practice/question',[
            'GET' => ['controller' => 'Practiceexam', 'action' => 'question'],
            'POST' => ['controller' => 'Practiceexam', 'action' => 'save']
        ]);

        // 骑手查看保单
        $this->addRoutes('/secure',[
            'GET' => ['controller' => 'Secure', 'action' => 'List'],
        ]);

        // 骑手发起实人认证
        $this->addRoutes('/PersonCert/RPBioID',[
            'POST' => ['controller' => 'Driver', 'action' => 'RPBioIDPersonCert'],
        ]);
        // 骑手实人认证完成
        $this->addRoutes('/PersonCertEnd/RPBioID',[
            'POST' => ['controller' => 'Driver', 'action' => 'RPBioIDPersonCertEnd'],
        ]);

        $this->add('[/]?');
    }
}
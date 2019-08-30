<?php
namespace app\modules\postofficeapp;

use app\models\service\RegionVehicle;
use app\services\data\UserData;

class SecureController extends PostofficebaseController
{
    // 骑手查看保单
    public function ListAction()
    {
        $driverId = $this->authed->userId;
        // 查询当前绑定车辆
        $RV = RegionVehicle::arrFindFirst([
            'driver_id' => $driverId,
        ]);
        if (false == $RV){
            return $this->toError(500, '您暂未绑定车辆哦~');
        }
        $time = time();
        $secures =  $this->modelsManager->createBuilder()
            ->addfrom('app\models\service\Secure','s')
            ->where('s.start_time < :time: AND s.end_time > :time:', ['time'=>$time])
            ->leftJoin('app\models\service\SecureInfo',
                'si.secure_ins_id = s.secure_id AND si.secure_num = s.secure_num','si')
            ->andWhere('s.vehicle_id = :vehicle_id:', ['vehicle_id'=>$RV->vehicle_id])
            ->columns('s.id, s.secure_id AS secureInsId, s.secure_num, s.start_time, s.end_time, si.secure_file, si.service_line')
            ->orderBy('s.end_time DESC')
            ->getQuery()
            ->execute()
            ->toArray();
        $secureInsIds = [];
        foreach ($secures as $secure){
            $secureInsIds[] = $secure['secureInsId'];
        }
        $secureCompanyNames = (new UserData())->getCompanyNamesByInsIds($secureInsIds);
        foreach ($secures as $k => $secure){
            $secures[$k]['secureCompanyName'] = $secureCompanyNames[$secure['secureInsId']] ?? '';
        }
        return $this->toSuccess($secures);
    }
}

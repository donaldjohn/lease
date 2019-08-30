<?php
namespace app\models\service;

// 保单附件表
class SecureInfo extends BaseModel
{
    public function initialize()
    {
        parent::initialize();
        $this->setSource('dw_secure_info');
    }

    // 维护SecureInfo
    public static function upSecureInfo($secureInsId, $secureNum, $data)
    {
        $secureInfo = SecureInfo::arrFindFirst([
            'secure_ins_id' => $secureInsId,
            'secure_num' => $secureNum,
        ]);
        if (false == $secureInfo){
            $secureInfo = new self();
            $secureInfo->secure_ins_id = $secureInsId;
            $secureInfo->secure_num = $secureNum;
        }
        $bol = $secureInfo->save($data);
        return $bol;
    }
}

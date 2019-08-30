<?php
namespace app\modules\home;


use app\models\service\Area;
use app\models\users\Association;
use app\models\users\Company;
use app\models\users\Institution;
use app\models\users\Postoffice;
use app\models\users\Store;
use app\modules\BaseController;
use app\services\data\PostOfficeData;
use Gregwar\Captcha\CaptchaBuilder;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Validation;
use phpDocumentor\Reflection\Types\Object_;
use app\services\data\RedisData;

class IndexController extends BaseController
{

    public function VcodeAction()
    {
        header('Content-type: image/jpeg');

        $captcha = new CaptchaBuilder(4);
        $captcha->build();
        $phrase = strtolower($captcha->getPhrase());
//        $this->session->set($phrase,"vcode");
        // 存储验证码到Redis
        (new RedisData())->set('vcode_'.$phrase, 1, 120);
        $captcha->output();
        //获取验证码的内容
    }


    public function testAction()
    {
//        $area = Area::find(['conditions' =>'area_parent_id = :id:','bind' => ['id' =>0 ]])->toArray();
//        foreach($area as $item) {
//            $sql = 'insert into x2_area (areaid,area,areacode,arealevel) values ('.$item['area_id'].',\''.$item['area_name'].'\','.$item['area_id'].',0)';
//            $this->phpems->query($sql);
//        }
//        return true;

//        $id = $this->request->getQuery('id');
//        $id1 =  $this->session->get($id);
//        echo $id1;
//        $this->session->destroy("$id");
//        exit;
//        $k['number'] = 10;
//        $v = new Validation();
//        $v->add("number",new Validation\Validator\Between([
//            "minimum" => 20,
//            "message" => '必须大于20',
//        ]));
//        $v->validate($k);
//        print_r($v->getMessages());
//        exit;
        $files = [
                [
                'key' => 'code', 'type' => 'number',
                    'parameter' => [
                    'default' => true,   //非空
                    'min' => 5,        //最小
                    'max' => 10,        //最大
                    //'float' => 'true',  //允许float
                    //'in' => [1,2,3], //判断当前值是否在规定值内
                    //'value' => '' ,      //判断是否和指定值相同
                    //'not_in' => [], //判断当前值不在规定值内
                ]
            ],
//            "index" => [
//                'type' => 'number',
//                'parameter' => [
//                    'default' => true,   //非空
//        ''
//                    'min' => 5,        //最小
//                    'max' => 10,        //最大
//                    //'float' => 'true',  //允许float
//                    'in' => [6,2,3], //判断当前值是否在规定值内
//                    //'value' => '' ,      //判断是否和指定值相同
//                    //'not_in' => [], //判断当前值不在规定值内
//                ]
//            ],

        ];
        $data['code'] = "123";
        //$data['index'] = 6;
        $valdate = $this->validate;
        $result = $valdate->myValidation($files,$data);
        $message = $valdate->messages($result['content']);
        print_r($message);
        exit;

    }

    public function NotfoundAction()
    {
//        $logger = new Stream("compress.zlib://week.log.gz","");
//
//        $logger->log("This is a message");
//        $logger->error("This is another error");
//
//        $k = file_get_contents("compress.zlib://week.log.gz");
//        $i = stream_get_contents("compress.zlib://week.log.gz");
//        print_r($k);
//        exit;
       return $this->toError(404,'NOT FOUND');

    }


    public function regionAction()
    {
        $res = [];
        $areaId = $this->request->getQuery('areaId','int',null);
        if (!is_null($areaId))
            $res['areaId'] = $areaId;
        $areaParentId = $this->request->getQuery('areaParentId','int',null);
        if (!is_null($areaParentId))
            $res['areaParentId'] = $areaParentId;
        $areaDeep = $this->request->getQuery('areaDeep','int',null);
        if (!is_null($areaDeep))
            $res['areaDeep'] = $areaDeep;
        // 模糊搜索区域名
        $fuzzySearch = $this->request->getQuery('fuzzySearch','string',null);
        if (!is_null($fuzzySearch))
            $res['fuzzySearch'] = $fuzzySearch;
        // 区域名
        $areaName = $this->request->getQuery('areaName','string', null);
        if (!is_null($areaName))
            $res['areaName'] = $areaName;
        if (count($res) == 0) {
            $params = ["code" => "10022","parameter" => (Object)$res];
        } else {
            $params = ["code" => "10022","parameter" => $res];
        }
        $result = $this->curl->httpRequest($this->Zuul->biz,$params,"post");
        //结果处理返回
        if ($result['statusCode'] != '200') {
            return $this->toError($result['statusCode'], $result['msg']);
        }
        if (!isset($result['content']['data']))
            return $this->toError(500, "数据不存在");

        return $this->toSuccess($result['content']['data']);
    }

    public function CatenvfileAction()
    {
        echo $this->config->CONFIG_FILE_NAME;
    }



    public function RsaencryptAction()
    {
        $password = $this->request->getQuery('password');
        $code = $this->RSAEncrypt($password);
        return $this->toSuccess($code);

    }



    public function getInsExpressPrintDriversLicenseStatusAction()
    {
        //根据机构ID获取城市ID
        $areas = $this->userData->getAreaByInsId($this->authed->insId);

        if (!empty($areas['cityId'])) {
            return $this->toSuccess(false);
        }
        $result = (new PostOfficeData())->getPostOfficeSystemParam($areas['cityId'], PostOfficeData::expressPrintDriversLicense);
        return $this->toSuccess($result);
    }



    public function updateInsNameAction()
    {
        $false = [];
        $inss = Institution::find()->toArray();
        foreach ($inss as $ins) {
            if ($ins['foreign_table'] == "dw_company") {
                $company = Company::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $ins['id']]]);
                if ($company) {
                    $newIns = Institution::findFirst($ins['id']);
                    $newIns->ins_name = $company->getCompanyName();
                    if ($newIns->update() == false) {
                        $false[] = $ins['id'];
                    }
                }
            } else if ($ins['foreign_table'] == "dw_postoffice") {
                $post = Postoffice::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $ins['id']]]);
                if ($post) {
                $newIns = Institution::findFirst($ins['id']);
                $newIns->ins_name = $post->getPostName();
                if ($newIns->update() == false) {
                    $false[] = $ins['id'];
                }
                }
            } else if ($ins['foreign_table'] == "dw_association") {
                $ass = Association::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $ins['id']]]);
                if ($ass) {
                    $newIns = Institution::findFirst($ins['id']);
                    $newIns->ins_name = $ass->getAssociationName();
                    if ($newIns->update() == false) {
                        $false[] = $ins['id'];
                    }
                }
            } else if ($ins['foreign_table'] == "dw_store") {
                $store = Store::findFirst(['conditions' => 'ins_id = :insId:','bind' => ['insId' => $ins['id']]]);
                if ($store) {
                    $newIns = Institution::findFirst($ins['id']);
                    $newIns->ins_name = $store->getStoreName();
                    if ($newIns->update() == false) {
                        $false[] = $ins['id'];
                    }
                }

            } else if ($ins['foreign_table'] == "dw_trafficpolice") {


            } else {
                $false[] = $ins['id'];
            }
        }

        return $this->toSuccess($false);
    }



    public function getAllRouterAction() {
        error_reporting(0);
       $result = [];

       $router = $this->_dependencyInjector->getServices()['router'];
       $routers = $router->resolve()->getRoutes();
       foreach($routers as $item) {
           $api = [];
           $api['module'] = $item->getPaths()['module'];
           $api['method'] = $item->getHttpMethods();
           $api['url'] = $item->getPattern();
           $api['module'] = $item->getPaths()['module'];
           $api['controller'] = $item->getPaths()['controller'];
           $api['action'] = $item->getPaths()['action'];
           $result[] = $api;
       }
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setStatusCode(200);
        return $this->response->setJsonContent($result, JSON_UNESCAPED_UNICODE);
    }

}
<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: XController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\errors\AppException;
use app\common\library\ReturnCodeService;
use app\modules\BaseController;
use Phalcon\Mvc\Controller;

class XController extends Controller {

    private $customerId = 0;


    public function initialize()
    {
        $secretKey = $this->request->getHeader('secretKey');
        if (!isset($secretKey)) {
            throw new AppException([500,'授权ID不能为空']);
        }
        //TODO:判断secretKey是否存在.
        $params['secretKey'] = $secretKey;
        $result = $this->userData->common($params,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_CUSTOMER);
        if(isset($result['data'][0]['id'])) {
            $this->customerId = $result['data'][0]['id'];
        } else {
            throw new AppException([500,'秘钥错误,请联系管理员!']);
        }
    }

    protected function getCustomerId()
    {
        return $this->customerId;
    }


    /**
     * 返回错误信息
     * @param int $errcode
     * @param string $errmsg
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    protected function toError($errcode = 500, $errmsg = "未知错误"){

        //$result_code = $this->httpService->getHttpCode($errcode);
        //$this->response->setStatusCode($result_code[0],$result_code[1]);
        $this->response->setStatusCode(500);
        $result = ["content" => "","statusCode" => $errcode,"msg" => $errmsg];
        return $this->response->setJsonContent($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回成功的数据
     * $meta   其他信息如分页数据等等
     * $data   具体的数据
     * @param array $data
     * @param array $meta
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    protected function toSuccess($data = array(),$meta = array(),$code = 200,$msg = '') {
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setStatusCode(200);
        $meta = $meta == null ?new \stdClass() :$meta;
        $content = array("data" => $data,"meta" => $meta);
        $result = ["content" => $content,"statusCode" => $code,"msg" => $msg];
        return $this->response->setJsonContent($result, JSON_UNESCAPED_UNICODE);
    }



}
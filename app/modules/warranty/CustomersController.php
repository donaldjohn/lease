<?php
// +--------------------------------------------------------
// |  PROJECT_NAME: lease
// +--------------------------------------------------------
// |  FILE_NAME: IndexController.php
// +--------------------------------------------------------
// |  AUTHOR: zhengchao
// +--------------------------------------------------------
namespace app\modules\warranty;


use app\common\library\ReturnCodeService;
use app\models\service\WarrantyCustomer;
use app\modules\BaseController;
use Phalcon\Acl\Adapter;
use Phalcon\Paginator\Adapter\Model;
use Phalcon\Paginator\Adapter\QueryBuilder;

class CustomersController extends BaseController {

    public function ListAction()
    {
        //获取客户列表
        $userStatus = $this->request->getQuery('userStatus','int',1);
        $json['userStatus'] = $userStatus;
        $result = $this->userData->common($json,$this->Zuul->biz,ReturnCodeService::WARRANTY_READ_CUSTOMER);
        return $this->toSuccess($result['data'],$result['pageInfo']);
    }

    /**
     * 客户列表信息
     * author zyd
     */
    public function IndexAction()
    {
        $search = $this->request->getQuery('search','string',null);
        $pageNum = $this->request->getQuery('pageNum','int',1);
        $limit = $this->request->getQuery('pageSize','int',20);
        $model = WarrantyCustomer::query();
        $model->where('is_delete = :is_delete:', [ 'is_delete' => 0]);
        if ($search) {
            $model->andWhere('user_code LIKE :user_code: OR user_simple_name LIKE :user_simple_name: 
            OR user_real_name LIKE :user_real_name:',  $parameters = [
                'user_code' => '%'. $search. '%',
                'user_simple_name' => '%'. $search. '%',
                'user_real_name' => '%'. $search. '%',
            ]);
        }
        //TODO 暂时获取总的列表数量
        $count = clone $model;
        $count = $count->columns('id')->execute()->toArray();
        //获取列表数据
        $data = $model->columns('id,user_code, user_real_name, user_simple_name, user_status, is_delete, create_at, rule')
            ->orderBy('id desc')
            ->limit($limit, ($pageNum-1)*$limit)
            ->execute()
            ->toArray();
        foreach ($data as $key => &$val) {
            $val['create_at'] =  $val['create_at'] ? date('Y-m-d H:i:s',  $val['create_at']) : '--';
        }
        return $this->toSuccess($data, ['pageNum'=> $pageNum, 'total' => count($count), 'pageSize' => $limit]);
    }

    /**
     * 新增客户信息
     */
    public function CreateAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'user_code', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'user_real_name', 'type' => 'string', 'parameter' => ['default' => true, 'max' => 50,
                'regex' => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u']],
            ['key' => 'user_simple_name', 'type' => 'string', 'parameter' => ['default' => true, 'max' => 10,
                'regex' => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u']],
            ['key' => 'rule', 'type' => 'string', 'parameter' => ['default' => true, ]],
            ['key' => 'user_status', 'type' => 'number', 'parameter' => ['default' => true, 'in' => [1, 2]]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
           return $this->toError(500, $message[0]);
        }
        $data['create_at'] = time();
        $data['update_at'] = 0;
        $data['is_delete'] = 0;
        $data['secret_key'] = WarrantyCustomer::secret();
        $model = new WarrantyCustomer();
        if ($model->save($data) === false) {
            $messages = $model->getMessages();
            $msg = '';
            foreach ($messages as $message) {
                $msg = $message->getMessage();
            }
            return $this->toError('500', $msg);
        }
        return $this->toSuccess();
    }
    /**
     * 编辑客户信息
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function UpdateAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'user_real_name', 'type' => 'string', 'parameter' => ['default' => false, ]],
            ['key' => 'user_simple_name', 'type' => 'string', 'parameter' => ['default' => false, ]],
            ['key' => 'user_status', 'type' => 'number', 'parameter' => ['default' => true, 'in' => [1, 2]]],
            ['key' => 'rule', 'type' => 'string', 'parameter' => ['default' => false, ]],
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true],]
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $result['content']['update_at'] = time();
        $model = WarrantyCustomer::findFirst($data['id']);
        if ($model->save($result['content']) === false) {
            $messages = $model->getMessages();
            $msg = '';
            foreach ($messages as $message) {
                $msg = $message->getMessage();
            }
            return $this->toError('500', $msg);
        }
        return $this->toSuccess();
    }
    /**
     * 删除客户信息
     */
    public function DeleteAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'id', 'type' => 'number', 'parameter' => ['default' => true],]
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data['update_at'] = time();
        $data['is_delete'] = 1;
        $model = WarrantyCustomer::findFirst($data['id']);
        $res = $model->save($data);
        if (!$res) {
            return $this->toError(500, '保存失败');
        }
        return $this->toSuccess();
    }
    /**
     *  客户状态启用禁用
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function StatusAction($id)
    {
        $data = $this->request->getJsonRawBody(true);
        $fields = [
            ['key' => 'user_status', 'type' => 'number', 'parameter' => ['default' => true, 'in' => [1, 2]]],
        ];
        $validate = $this->validate;
        $result = $validate->myValidation($fields,$data);
        $message = $validate->messages($result['content']);
        if (isset($message[0])) {
            return $this->toError(500, $message[0]);
        }
        $data['update_at'] = time();
        $model = WarrantyCustomer::findFirst($id);
        $res = $model->save($data);
        if (!$res) {
            return $this->toError(500, '保存失败');
        }
        return $this->toSuccess();
    }
    /**
     * 获取客户的秘钥
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function SecretAction($id)
    {
        $model = WarrantyCustomer::findFirst($id);
        if (!$model) {
            return $this->toError(200, '未找到该数据');
        }
        return $this->toSuccess(['secret_key' => $model->secret_key]);
    }
    /**
     * 更新秘钥
     * @param $id
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function UpdateSecretAction($id)
    {
        $model = WarrantyCustomer::findFirst($id);
        if (!$model) {
            return $this->toError(200, '未找到该数据');
        }
        if ($model->is_delete == 1) {
            return $this->toError(200, '该数据已删除');
        }
        $data['update_at'] = time();
        $data['secret_key'] = WarrantyCustomer::secret();
        $res = $model->save($data);
        if (!$res) {
            return $this->toError(500, '保存失败');
        }
        return $this->toSuccess();
    }
}
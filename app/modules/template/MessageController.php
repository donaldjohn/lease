<?php
namespace app\modules\template;

use app\models\service\MessageTemplate;
use app\models\service\AppEvent;
use app\modules\BaseController;
use Phalcon\Application\Exception;

class MessageController extends BaseController {

    /**
     * 新增模板消息
     * @param string template_sn 消息模板编号
     * @param string template_name 模板名称
     * @param string template_type 模板类型
     * @param string notice_type 通知类型
     * @param string notice_andriod 安卓通知地址
     * @param string notice_ios ios通知地址
     * @param string notice_url 通知地址
     * @param string template_status 模板状态
     * @param string template_pic 模板图片
     * @param string template_text 模板内容
     * @param string template_need_button 是否按钮
     * @param string template_button 按钮
     * @return mixed
     */
    public function AddAction()
    {
        $request = $this->request->getJsonRawBody();
        $query = new MessageTemplate();

        // 必传参数
        if (!isset($request->template_sn)) {
            return $this->toError(500, '消息模板编号不能为空');
        }
        // 判断app编号重复性
        $result = MessageTemplate::query()->where("template_sn = :template_sn:", $params = ['template_sn' => $request->template_sn])->execute()->toArray();
        if (count($result) > 0) {
            return $this->toError(500, '模板代码重复，请重新提交');
        }

        $query->template_sn = $request->template_sn;

        if (!isset($request->template_name)) {
            return $this->toError(500, '消息模板名称不能为空');
        }
        $query->template_name = $request->template_name;

        if (!isset($request->template_type)) {
            return $this->toError(500, '消息模板类型不能为空');
        }
        $query->template_type = $request->template_type;

        if (!isset($request->notice_type)) {
            return $this->toError(500, '消息模板通知类型不能为空');
        }
        $query->notice_type = $request->notice_type;

        if ($request->notice_type == 1) {
            if (!isset($request->notice_andriod) || !isset($request->notice_ios)) {
                return $this->toError(500, '安卓、苹果消息地址不能为空');
            }
            $query->notice_andriod = $request->notice_andriod;
            $query->notice_ios = $request->notice_ios;
        } else {
            if (!isset($request->notice_url)) {
                return $this->toError(500, '消息地址不能为空');
            }
            $query->notice_url = $request->notice_url;
        }

        // 非必传参数
        $query->template_status = isset($request->template_status) ? $request->template_status : 2;
        $query->template_pic = isset($request->template_pic) ? $request->template_pic : '';
        $query->template_text = isset($request->template_text) ? $request->template_text : '';
        $query->template_need_button = isset($request->template_need_button) ? $request->template_need_button : 2;
        $query->template_button = isset($request->template_button) ? $request->template_button : '';
        $query->create_at = time();

        $result = $query->save();
        if ($result) {
            return $this->toSuccess('新增模板成功');
        } else {
            return $this->toError(500, '新增模板失败');
        }
    }

    /**
     * 修改模板消息
     * @param string id 消息模板ID
     * @param string template_sn 消息模板编号
     * @param string template_name 模板名称
     * @param string template_type 模板类型
     * @param string notice_type 通知类型
     * @param string notice_andriod 安卓通知地址
     * @param string notice_ios ios通知地址
     * @param string notice_url 通知地址
     * @param string template_status 模板状态
     * @param string template_pic 模板图片
     * @param string template_text 模板内容
     * @param string template_need_button 是否按钮
     * @param string template_button 按钮
     * @return mixed
     */
    public function EditAction()
    {
        $request = $this->request->getJsonRawBody();
        $templateId = isset($request->id) ? $request->id : 0;
        if ($templateId == 0) {
            return $this->toError(500, '模板ID不能为空');
        }
        $query = MessageTemplate::query()->where("id = $templateId")->execute();
        $data = [];



        // 修改参数过滤
        if (isset($request->template_sn)) {
            $data['template_sn'] = $request->template_sn;
        }

        if (isset($request->template_name)) {
            $data['template_name'] = $request->template_name;
        }

        if (isset($request->template_type)) {
            $data['template_type'] = $request->template_type;
        }

        if (isset($request->notice_type)) {
            $data['notice_type'] = $request->notice_type;
        }

        if (isset($request->notice_andriod)) {
            $data['notice_andriod'] = $request->notice_andriod;
        }

        if (isset($request->notice_ios)) {
            $data['notice_ios'] = $request->notice_ios;
        }

        if (isset($request->notice_url)) {
            $data['notice_url'] = $request->notice_url;
        }

        if (isset($request->template_status)) {
            $data['template_status'] = $request->template_status;

            if ($data['template_status'] == 2) {
                //判断当前模板是否已经在使用中.使用中禁止更新.
                $appEvent = AppEvent::findFirst(['conditions' => 'template_id = :template_id: and is_delete = 1','bind' => ['template_id' => $templateId]]);
                if($appEvent) {
                    return $this->toError(500,'当前模板正在使用中,禁止禁用!');
                }

            }

        }

        if (isset($request->template_pic)) {
            $data['template_pic'] = $request->template_pic;
        }

        if (isset($request->template_text)) {
            $data['template_text'] = $request->template_text;
        }

        if (isset($request->template_need_button)) {
            $data['template_need_button'] = $request->template_need_button;
        }

        if (isset($request->template_button)) {
            $data['template_button'] = $request->template_button;
        }

        $data['update_at'] = time();

        $result = $query->update($data);
        if ($result) {
            return $this->toSuccess('模板修改成功');
        } else {
            return $this->toError(500, '模板修改失败');
        }
    }

    /**
     * 消息模板删除
     * @param int id 模板编号ID
     * @return mixed
     */
    public function DeleteAction()
    {
        // 请求参数验证
        $request = $this->request->getJsonRawBody();
        $templateId = isset($request->id) ? $request->id : 0;
        if ($templateId == 0) {
            return $this->toError(500, '模板ID不能为空');
        }

        // 删除对应记录
        $template = MessageTemplate::query()->where("id = $templateId")->execute();
        $result = $template->update(['is_delete' => 2]);
        if ($result) {
            return $this->toSuccess('删除模板成功');
        } else {
            return $this->toError(500, '删除模板失败');
        }
    }

    /**
     * 获取消息模板列表
     * @param int pageSize 每页展示数量
     * @param int pageNum 起始记录编号
     * @return mixed
     */
    public function ListAction()
    {
        // 获取请求参数
        $request = $this->request->get();
        $pageSize = isset($request['pageSize']) ? $request['pageSize'] : 20;
        $pageNum = isset($request['pageNum']) ? $request['pageNum'] : 1;

        $templateSn = isset($request['template_sn']) ? $request['template_sn'] : null;
        $templateName = isset($request['template_name']) ? $request['template_name'] : null;
        $templateStatus = isset($request['template_status']) ? $request['template_status'] : null;

        // 数据获取
        $query = MessageTemplate::query();
        $query->where('is_delete = :is_delete:', [ 'is_delete' => 1]);
        if (!empty($templateName)) {
            $query = $query->andWhere('template_name LIKE :template_name:', $parameters = [
                'template_name' => "%".$templateName."%",
            ]);
        }
        if (!empty($templateSn)) {
            $query = $query->andWhere('template_sn LIKE :template_sn:', $parameters = [
                'template_sn' => "%".$templateSn."%",
            ]);
        }
        if (!empty($templateStatus)) {
            $query = $query->andWhere('template_status = :template_status:', $parameters = [
                'template_status' => $templateStatus,
            ]);
        }

        $query_tmp = clone $query;
        $total = count($query_tmp->execute()->toArray()); // 总记录数
//var_dump($total);exit();
        $result = $query->limit($pageSize, ($pageNum - 1) * $pageSize)->orderBy('create_at desc')->execute()->toArray();
        $pageInfo = [
            'total' => (int)$total,
            'pageSize' => (int)$pageSize,
            'pageNum' => (int)$pageNum
        ];

        // 返回数据
        return $this->toSuccess($result, $pageInfo);
    }


    /**
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     *
     */

    public function SearchAction()
    {
        /**
         * 不分页
         */
        $template_type = $this->request->getQuery('template_type','int',null,true);
        $notice_type = $this->request->getQuery('notice_type','int',null,true);
        $template_sn= $this->request->getQuery('template_sn','string',null,true);
        $template_name= $this->request->getQuery('template_name','string',null,true);

        $template = MessageTemplate::query()
            ->columns('id,template_sn,template_name,template_type')
            ->andWhere('template_status = 1 and is_delete = 1');
        if ($template_type != null) {
            $template->andWhere('template_type = :template_type:',['template_type' => $template_type]);
        }
        if ($notice_type != null) {
            $template->andWhere('notice_type = :notice_type:',['notice_type' => $notice_type]);
        }

        if ($template_sn != null) {
            $template->andWhere('template_sn like :template_sn:',['template_sn' => '%'.$template_sn.'%']);
        }
        if ($template_name != null) {
            $template->andWhere('template_name like :template_name:',['template_name' => '%'.$template_name.'%']);
        }
        $result = $template->execute()->toArray();

        return $this->toSuccess($result);



    }

}
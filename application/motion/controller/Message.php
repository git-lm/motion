<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Message as messageModel;

/**
 * 留言管理
 * Class Message
 * @package app\motion\controller
 * @author Anyon 
 * @date 2017/02/15 18:12
 */
class Message extends BasicAdmin {

    public function initialize() {
        $this->messageModel = new messageModel();
    }

    /**
     * 渲染类型页面
     */
    public function index() {
        $this->assign('title', '留言列表');
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }
        $this->assign('lid', $lid);
        $lists = $this->get_lists($lid);
        $this->assign('lists', $lists);
        $this->read_message($lid);
        return $this->fetch();
    }

    /**
     * 获取留言信息
     * @param int $id 记录ID
     */
    public function get_lists($id = 0) {
        $where['p_id'] = $id;
        $order['create_time'] = 'desc';
        $lists = $this->messageModel->get_messages($where, $order);
        return $lists;
    }

    /**
     * 发布留言
     */
    public function add_info() {
        $content = request()->has('content', 'post') ? request()->post('content/s') : null;
        //记录ID
        $lid = request()->has('lid', 'post') ? request()->post('lid/d') : 0;
        if (!$lid) {
            $this->error('请正确选择记录');
        }
        if (!$content) {
            $this->error('请填写留言');
        }
        $data['content'] = $content;
        $validate = $this->messageModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        //获取教练信息
        $list = $this->get_lession_data($lid);
        if (empty($list)) {
            $this->error('无此记录');
        }
        $data['c_id'] = $list['coach_id'];
        $data['p_id'] = $lid;
        $data['is_check'] = 1;
        $code = $this->messageModel->add($data);
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 点击消息后 全部已读
     */
    public function read_message($id = 0) {
        $where['p_id'] = $id;
        $data['is_check'] = 1;
        $code = $this->messageModel->edit($data, $where);
        if ($code) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取记录信息
     */
    public function get_lession_data($id = 0) {

        $lessonModel = new \app\motion\model\Lesson();
        $where['id'] = $id;
        $list = $lessonModel->get_arrange_list($where);
        return $list;
    }

}

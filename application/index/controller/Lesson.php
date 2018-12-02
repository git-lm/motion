<?php

namespace app\index\controller;

use think\Controller;
use app\motion\model\Lesson as lessonModel;
use app\motion\model\Message as messageModel;
use app\motion\model\Motion as motionModel;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Lesson extends Controller {

    public function initialize() {
        session('motion_member.id', 6);
        // 登录状态检查
        if (!session('motion_member')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('@login')];
            return request()->isAjax() ? json($msg) : redirect($msg['url']);
        }
        $this->m_id = session('motion_member.id');
        $this->lessonModel = new lessonModel();
        $this->messageModel = new messageModel();
        $this->motionModel = new motionModel();
    }

    /**
     * 获取未到时间的
     */
    public function get_arranges() {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $type = request()->has('type', 'get') ? request()->get('type/s') : 'upcomming';
        $where[] = ['l.m_id', '=', $this->m_id];
        $where[] = ['l.status', '=', 1];
        if ($type == 'upcomming') {
            //说明获取未到时间的
            $where[] = ['l.class_time', '>', time()];
        } else {
            //说明获取已过时间的
            $where[] = ['l.class_time', '<', time()];
        }

        $order['l.class_time'] = 'desc';
        $lists = $this->lessonModel->get_arrange_lists($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_arrange_lists($where));
        //获取记录留言
        foreach ($lists as &$list) {
            //获取留言
            $message = $this->get_messages($list['id']);
            //获取动作
            $course = $this->get_course($list['id']);
            $list['message'] = $message;
            $list['course'] = $course;
            $list['class_time_show'] = date('Y-m-d', $list['class_time']);
            $list['month_e'] = date('M', $list['class_time']);
            $list['day'] = date('d', $list['class_time']);
        }
        $pages = $this->get_pages($count, $limit);
        ajax_list_return($lists, $pages);
    }

    /**
     * 记录详情
     */
    public function detile() {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 1;
        if (!$id) {
            $this->error('请正确选择');
        }
        //判断是否是登录人的记录
        $where[] = ['id', '=', $id];
        $where[] = ['m_id', '=', $this->m_id];
        $list = $this->lessonModel->get_arrange_list($where);
        if (empty($list)) {
            $this->error('无权查看');
        }
        $list['class_time_show'] = date('Y-m-d', $list['class_time']);
        $list['course'] = $this->get_course($id);
        $list['message'] = $this->get_messages($id);
        $list['warmup_motions'] = $this->get_motion($list['warmup_mids']);
        $list['colldown_motions'] = $this->get_motion($list['colldown_mids']);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function message_add() {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $message = request()->has('message', 'post') ? request()->post('message/s') : '';
        if (!$message) {
            $this->error('请填写记录');
        }
        $where[] = ['id', '=', $id];
        $data ['message'] = $message;
        $code = $this->lessonModel->little_edit($data, $where);
        if ($code) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 获取总页面
     */
    public function get_pages($count = 0, $limit = 0) {
        return ceil($count / $limit);
    }

    /**
     * 获取记录
     */
    public function history() {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $lwhere['id'] = $id;
        $little = $this->lessonModel->get_little_course($lwhere);
        $m_ids = $little['m_ids'];
        $lists = $this->lessonModel->get_history($m_ids);

        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 获取留言
     */
    public function get_messages($lid = 0) {
        $mwhere[] = ['p_id', '=', $lid];
        $mwhere[] = ['type', '=', 1];
        $mwhere[] = ['m_id', '=', 'null'];
        $morder['create_time'] = 'desc';
        $messages = $this->messageModel->get_messages($mwhere, $morder);
        return $messages;
    }

    /**
     * 获取动作
     */
    public function get_course($lid = 0) {
        $cwhere [] = ['l_id', '=', $lid];
        $cwhere [] = ['status', '=', 1];
        $corder['create_time'] = 'desc';
        $course = $this->lessonModel->get_little_courses($cwhere, $corder);
        foreach ($course as &$c) {
            $motions = $this->get_motion($c['m_ids']);
            $c['motions'] = $motions;
        }
        return $course;
    }

    /**
     * 获取视频
     */
    public function get_motion($ids = '') {
        $vwhere [] = ['mb.id', 'in', $ids];
        $vwhere [] = ['mb.status', '=', 1];
        $motion = $this->motionModel->get_motions($vwhere);
        return $motion;
    }

}

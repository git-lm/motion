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
        // 登录状态检查
        if (!session('motion_member')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('/login')];
            return request()->isAjax() ? json($msg) : $this->redirect($msg['url']);
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

        $order['l.class_time'] = 'asc';
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
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //判断是否是登录人的记录
        $where[] = ['id', '=', $id];
        $where[] = ['m_id', '=', $this->m_id];
        $order['create_time'] = 'asc';
        $list = $this->lessonModel->get_arrange_list($where);
        if (empty($list)) {
            $this->error('无权查看');
        }
        $list['class_time_show'] = date('Y-m-d', $list['class_time']);
        //获取小动作
        $list['course'] = $this->get_course($id);
        //获取留言
        $list['message'] = $this->get_messages($id);
        // 获取热身视频
        $list['warmup_motions'] = $this->get_motion($list['warmup_mids']);
        //获取冷身视频
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
        if (empty($little['m_ids'])) {
            $this->error('无此相关记录');
        }
        $m_ids = $little['m_ids'];
        $lists = $this->lessonModel->get_history($m_ids , $this->m_id);

        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 操作动作完成情况
     */
    public function handleCourse() {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $state = request()->has('state', 'post') ? request()->post('state/d') : 0;
        $lwhere['id'] = $id;
        $little = $this->lessonModel->get_little_course($lwhere);
        if (empty($little)) {
            $this->error('无此相关记录');
        }
        $data['state'] = $state;
        $code = $this->lessonModel->little_edit($data, $lwhere);
        if ($code) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 操作动作完成情况
     */
    public function handleMotion() {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $lwhere['id'] = $id;
        $little = $this->lessonModel->get_arrange_list($lwhere);
        if (empty($little)) {
            $this->error('无此相关记录');
        }
        $data['state'] = 1;
        $code = $this->lessonModel->edit($data, $lwhere);
        if ($code) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 发送记录信息
     */
    public function message() {
        $messageModel = new \app\motion\model\Message();
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $messsage = request()->has('message', 'post') ? request()->post('message/s') : '';
        if (!$id) {
            $this->error('请正确选择');
        }
        $data['content'] = $messsage;
        $validate = $messageModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['p_id'] = $id;
        $data['m_id'] = $this->m_id;
        $code = $messageModel->add($data);
        if ($code) {
            $this->success('发送成功');
        } else {
            $this->error('发送失败');
        }
    }

    /**
     * 上传课程文件记录
     */
    public function file_add() {
        $file = request()->has('fileurl', 'post') ? request()->post('fileurl/s') : '';
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $data['lc_id'] = $id;
        $data['url'] = $file;
        $code = $this->lessonModel->file_add($data);
        if ($code) {
            $pathinf = pathinfo($file, PATHINFO_EXTENSION);
            echo json_encode(['code' => 1, 'msg' => $pathinf, 'lcfid' => $code]);
        } else {
            echo json_encode(['code' => 0, 'msg' => '上传失败']);
        }
    }

    public function file_del() {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('删除失败');
        }
        $data['status'] = 0;
        $where['id'] = $id;
        $code = $this->lessonModel->file_edit($data, $where);
        if ($code) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取留言
     */
    public function get_messages($lid = 0) {
        $mwhere[] = ['m.p_id', '=', $lid];
        $mwhere[] = ['m.type', '=', 1];
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
        $corder['create_time'] = 'asc';
        $course = $this->lessonModel->get_little_courses($cwhere, $corder);
        foreach ($course as &$c) {
            //获取小动作的视频
            $motions = $this->get_motion($c['m_ids']);
            $c['motions'] = $motions;
            //获取课程记录
            $fwhere ['lc_id'] = $c['id'];
            $fwhere ['status'] = 1;
            $forder ['create_time'] = 'desc';
            $file = $this->lessonModel->get_course_file($fwhere, $forder);
            if (!empty($file)) {
                $pathinf = pathinfo($file['url'], PATHINFO_EXTENSION);
                $c['pathinf'] = $pathinf;
            } else {
                $c['pathinf'] = '';
            }
            $c['file'] = $file;
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

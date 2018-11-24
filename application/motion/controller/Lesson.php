<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Lesson as lessonModel;
use app\motion\model\Member as memberModel;
use app\motion\model\Course as courseModel;

class Lesson extends BasicAdmin {

    public function initialize() {
        $this->lessonModel = new lessonModel();
        $this->memberModel = new memberModel();
        $this->courseModel = new courseModel();
    }

    /**
     * 渲染会员首页
     */
    public function index() {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 获取会员列表信息
     */
    public function get_member_lists() {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $cname = request()->has('cname', 'get') ? request()->get('cname/s') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($cname) {
            $where[] = ['t.cname', 'like', '%' . $cname . '%'];
        }
        if ($expire_time) {
            $et = explode(' - ', $expire_time);
            if (!empty($et[0]) && validate()->isDate($et[0])) {
                $begin_time = strtotime($et[0] . ' 00:00:00');
                $where[] = ['expire_time', '>=', $begin_time];
            }
            if (!empty($et[1]) && validate()->isDate($et[1])) {
                $end_time = strtotime($et[1] . ' 23:59:59');
                $where[] = ['expire_time', '<=', $end_time];
            }
        }
        $where[] = ['status', '<>', 0];
        $order['create_time'] = 'desc';
        $lists = $this->memberModel->get_members($where, $order, $page, $limit);
        $count = count($this->memberModel->get_members($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 查看排课信息
     */
    public function arrange() {
        $this->assign('title', '排课列表');
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请选择会员');
        }
        //验证会员是否存在
        $this->check_member_data($id);
        $this->assign('mid', $id);
        return $this->fetch();
    }

    /**
     * 获取排课信息
     */
    public function get_arrange_lists() {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $cname = request()->has('cname', 'get') ? request()->get('cname/s') : '';
        $class_time = request()->has('class_time', 'get') ? request()->get('class_time/s') : '';
        if (!$id) {
            $this->error('请选择会员');
        }
        if ($cname) {
            $where[] = ['c.name', 'like', '%' . $cname . '%'];
        }
        if ($class_time) {
            $ct = explode(' - ', $class_time);
            if (!empty($ct[0]) && validate()->isDate($ct[0])) {
                $begin_time = strtotime($ct[0] . ' 00:00:00');
                $where[] = ['class_time', '>=', $begin_time];
            }
            if (!empty($ct[1]) && validate()->isDate($ct[1])) {
                $end_time = strtotime($ct[1] . ' 23:59:59');
                $where[] = ['class_time', '<=', $end_time];
            }
        }
        
        //验证会员是否存在
        $this->check_member_data($id);
        //获取排课信息
        $where[] = ['p.m_id', '=', $id];
        $where[] = ['p.status', '=', 1];
        $order['p.create_time'] = 'desc';
        $lists = $this->lessonModel->get_arrange_lists($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_arrange_lists($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add() {
        //获取获取ID
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->assign('mid', $mid);
        //获取一级课程
        $courses = $this->get_courses();
        $this->assign('courses', $courses);
        return $this->fetch();
    }

    /**
     * 新增会员动作信息
     */
    public function add_info() {
        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/d') : 0;
        $class_time = request()->has('class_time', 'post') ? request()->post('class_time/s') : '';
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        if (!$c_id) {
            $this->error('请正确选择动作');
        }
        if (!$class_time) {
            $this->error('请正确选择上课时间');
        }
        //获取该会员的信息
        $where['id'] = $mid;
        $member = $this->memberModel->get_member($where);
        if (empty($member) || empty($member['c_id'])) {
            $this->error('无此会员或者该会员无教练');
        }
        //验证数据有效性
        $data['c_id'] = $c_id;
        $data['class_time'] = $class_time;
        $data['coach_id'] = $member['c_id'];
        $data['m_id'] = $mid;
        $validate = $this->lessonModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['class_time'] = strtotime($class_time);
        $code = $this->lessonModel->add($data);
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 渲染编辑窗口
     */
    public function edit() {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $list = $this->check_arrange_data($id);
        $this->assign('list', $list);
        //获取一级课程
        $courses = $this->get_courses();
        $this->assign('courses', $courses);
        return $this->fetch();
    }

    /**
     * 编辑会员动作信息
     */
    public function edit_info() {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/d') : 0;
        $class_time = request()->has('class_time', 'post') ? request()->post('class_time/s') : '';
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }

        if (!$c_id) {
            $this->error('请正确选择动作');
        }
        if (!$class_time) {
            $this->error('请正确选择上课时间');
        }
        //判断排课信息
        $list = $this->check_arrange_data($id);

        //验证数据有效性
        $data['c_id'] = $c_id;
        $data['class_time'] = $class_time;
        $validate = $this->lessonModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['class_time'] = strtotime($class_time);
        $where['id'] = $id;
        $code = $this->lessonModel->edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 删除课程
     */
    public function del() {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择会员动作');
        }
        $this->check_arrange_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取课程
     * @return type
     */
    public function get_courses($pid = 0) {

        $where['pid'] = $pid;
        $where['status'] = 1;
        $order['create_time'] = 'desc';
        $courses = $this->courseModel->get_courses($where, $order);
        return $courses;
    }

    /**
     * 判断会员是否存在
     */
    public function check_member_data($id = 0) {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->memberModel->get_member($where);
        if (empty($list)) {
            $this->error('无此会员');
        }
        return $list;
    }

    /**
     * 判断排课是否存在
     */
    public function check_arrange_data($id = 0) {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->lessonModel->get_arrange_list($where);
        if (empty($list)) {
            $this->error('无此排课');
        }
        if ($list['class_time'] < time()) {
            $this->error('该课程已结束');
        }
        $list['class_time_show'] = date('Y-m-d', $list['class_time']);
        return $list;
    }

}

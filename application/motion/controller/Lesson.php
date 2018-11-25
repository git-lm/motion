<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Lesson as lessonModel;
use app\motion\model\Member as memberModel;
use app\motion\model\Course as courseModel;
use app\motion\model\Coach as coachModel;

class Lesson extends BasicAdmin {

    public function initialize() {
        $this->lessonModel = new lessonModel();
        $this->memberModel = new memberModel();
        $this->courseModel = new courseModel();
        $this->coachModel = new coachModel();
    }

    /**
     * 渲染会员首页
     */
    public function index() {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 我的会员
     */
    public function my() {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 获取所有会员信息
     */
    public function get_all_lists() {
        echo $this->get_member_lists();
    }

    /**
     * 获取我的会员信息
     */
    public function get_my_lists() {
        $uid = session('user.id');
        //获取所属uid 的教练
        $where['u_id'] = $uid;
        $where['status'] = 1;
        $coach = $this->coachModel->get_coach($where);
        if (empty($coach)) {
            $this->error('该账号未绑定教练');
        }
        $lwhere[] = ['c_id', '=', $coach['id']];
        echo $this->get_member_lists($lwhere);
    }

    /**
     * 获取会员列表信息
     */
    public function get_member_lists($where = []) {
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
        return $this->tableReturn($lists, $count);
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

        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/s') : '';
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
        if (empty($member['expire_time']) || $member['expire_time'] < time()) {
            $this->error('该会员已过期');
        }
        $c_ids = explode(',', $c_id);
        foreach ($c_ids as $val) {
            //验证数据有效性
            $data['c_id'] = $val;
            $data['class_time'] = $class_time;
            $data['coach_id'] = $member['c_id'];
            $data['m_id'] = $mid;
            $validate = $this->lessonModel->validate($data);
            if ($validate) {
                $this->error($validate);
            }
            $data['class_time'] = strtotime($class_time);
            $code = $this->lessonModel->add($data);
            if (!$code) {
                $this->error('保存失败');
            }
        }
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
     * 查看小动作
     */
    public function little() {
        $this->assign('title', '添加小动作');
        //排课ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //判断排课是否存在
        $this->check_arrange_data($id);
        $this->assign('lid', $id);
        return $this->fetch();
    }

    /**
     * 获取小动作列表信息
     */
    public function get_little_courses() {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $where[] = ['lc.l_id', '=', $lid];
        $where[] = ['lc.status', '=', 1];
        $order['create_time'] = 'desc';
        $lists = $this->lessonModel->get_little_courses($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_little_courses($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 添加小动作
     */
    public function little_add() {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }
        $this->assign('lid', $lid);
        //验证排课是否存在
        $list = $this->check_arrange_data($lid);
        //获取大分类下的小分类
        $courses = $this->get_courses($list['c_id']);
        $this->assign('courses', $courses);
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function little_add_info() {
        $lid = request()->has('lid', 'post') ? request()->post('lid/d') : 0;
        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/d') : 0;
        $remark = request()->has('remark', 'post') ? request()->post('remark/s') : '';
        if (!$lid) {
            $this->error('请正确选择');
        }
        if (!$c_id) {
            $this->error('请选择分类');
        }
        //判断排课是否存在
        $list = $this->check_arrange_data($lid);
        //判断所选的课程是否是该排课的课程分类子类
        $where['pid'] = $list['c_id'];
        $where['id'] = $c_id;
        $where['status'] = 1;
        $course = $this->courseModel->get_courses($where);
        if (empty($course)) {
            $this->error('请正确选择分类');
        }
        $data['l_id'] = $lid;
        $data['c_id'] = $c_id;
        $data['remark'] = $remark;
        $validate = $this->lessonModel->course_validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->lessonModel->little_add($data);
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }

    public function little_edit() {
        //小动作ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //验证小动作是否存在
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        //获取大分类下的小分类
        $courses = $this->get_courses($arrange['c_id']);
        $this->assign('courses', $courses);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function little_edit_info() {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/d') : 0;
        $remark = request()->has('remark', 'post') ? request()->post('remark/s') : '';
        if (!$id) {
            $this->error('请正确选择');
        }
        if (!$c_id) {
            $this->error('请选择分类');
        }
        //验证小动作是否存在
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        //判断所选的课程是否是该排课的课程分类子类
        $where['pid'] = $arrange['c_id'];
        $where['id'] = $c_id;
        $where['status'] = 1;
        $course = $this->courseModel->get_courses($where);
        if (empty($course)) {
            $this->error('请正确选择分类');
        }
        $data['c_id'] = $c_id;
        $data['remark'] = $remark;
        $validate = $this->lessonModel->course_validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $lwhere['id'] = $id;
        $code = $this->lessonModel->little_edit($data, $lwhere);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 删除小动作
     */
    public function little_del() {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->little_edit($data, $where);
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
        //判断排课是否存在
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

    /**
     * 验证小动作是否存在
     */
    public function check_little_data($id = 0) {
        $where['id'] = $id;
        $list = $this->lessonModel->get_little_course($where);
        if (empty($list)) {
            $this->error('无此小动作');
        }
        return $list;
    }

}

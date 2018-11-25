<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Course as courseModel;

class Course extends BasicAdmin {

    public function initialize() {
        $this->courseModel = new courseModel();
    }

    /**
     * 渲染课程首页
     */
    public function index() {
        $this->assign('title', '课程列表');
        return $this->fetch();
    }

    /**
     * 获取课程列表信息
     */
    public function get_lists() {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        $where[] = ['status', '<>', 0];
        $order['create_time'] = 'desc';
        $lists = $this->courseModel->get_courses($where, $order, $page, $limit);
        $lists = $this->courseModel->get_level_courses($lists);
        $count = count($this->courseModel->get_courses($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add() {
        $pid = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $this->assign('pid', $pid);
        //获取一级课程
        $where['pid'] = 0;
        $where['status'] = 1;
        $order['create_time'] = 'desc';
        $courses = $this->courseModel->get_courses($where, $order);
        $this->assign('courses', $courses);
        //获取类型
        $lists = $this->get_motion_types();
        $this->assign('types', $lists);
        return $this->fetch();
    }

    /**
     * 新增课程数据
     */
    public function add_info() {
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $warmup = request()->has('warmup', 'post') ? request()->post('warmup/s') : '';
        $colldown = request()->has('colldown', 'post') ? request()->post('colldown/s') : '';
        $t_id = request()->has('t_id', 'post') ? request()->post('t_id/d') : 0;
        $p_id = request()->has('p_id', 'post') ? request()->post('p_id/d') : 0;
        if (!$t_id) {
            $this->error('请正确选择类型');
        }
        //验证数据有效性
        $data['name'] = $name;
        $data['warmup'] = $warmup;
        $data['colldown'] = $colldown;
        $data['t_id'] = $t_id;
        $data['pid'] = $p_id;
        $validate = $this->courseModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->courseModel->add($data);
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
            $this->error('请正确选择课程');
        }
        //判断课程是否存在
        $list = $this->check_data($id);
        $this->assign('list', $list);
        //获取一级课程
        $where['pid'] = 0;
        $where['status'] = 1;
        $order['create_time'] = 'desc';
        $courses = $this->courseModel->get_courses($where, $order);
        $this->assign('courses', $courses);
        //获取类型
        $lists = $this->get_motion_types();
        $this->assign('types', $lists);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info() {
        //获取数据
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $warmup = request()->has('warmup', 'post') ? request()->post('warmup/s') : '';
        $colldown = request()->has('colldown', 'post') ? request()->post('colldown/s') : '';
        $t_id = request()->has('t_id', 'post') ? request()->post('t_id/d') : 0;
        $p_id = request()->has('p_id', 'post') ? request()->post('p_id/d') : 0;
        $id = request()->has('t_id', 'post') ? request()->post('id/d') : 0;
        if (!$t_id) {
            $this->error('请正确选择类型');
        }
        if (!$id) {
            $this->error('请正确选择课程');
        }
        //判断类型是否存在
        $this->check_data($id);
        //验证数据
        $data['name'] = $name;
        $data['warmup'] = $warmup;
        $data['colldown'] = $colldown;
        $data['t_id'] = $t_id;
        $data['pid'] = $p_id;
        $validate = $this->courseModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $where['id'] = $id;
        $code = $this->courseModel->edit($data, $where);
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
            $this->error('请正确选择课程');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->courseModel->edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 禁用/启用  
     */
    public function handle() {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $value = request()->has('value', 'post') ? request()->post('value/d') : -1;
        if (!$id) {
            $this->error('请正确选择课程');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = $value;
        $code = $this->courseModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 渲染动作库
     */
    public function file() {
        //获取数据
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择课程');
        }
        $list = $this->check_data($id);
        //获取视频
        $motions = $this->get_motions();
        $this->assign('motions', $motions);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 课程添加动作库
     */
    public function file_info() {
        $b_ids = request()->has('b_ids', 'post') ? request()->post('b_ids/s') : '';
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (empty($id)) {
            $this->error('请正确选择要添加的课程');
        }
        $where['id'] = $id;
        $data['b_ids'] = $b_ids;
        $code = $this->courseModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 判断类型是否存在
     */
    public function check_data($id = 0) {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->courseModel->get_course($where);
        if (empty($list)) {
            $this->error('无此会员');
        }
        return $list;
    }

    /**
     * 获取类型
     */
    public function get_motion_types() {
        $typeModel = new \app\motion\model\MotionType;
        $twhere['status'] = 1;
        $torder['create_time'] = 'desc';
        $lists = $typeModel->get_motion_types($twhere, $torder);
        return $lists;
    }

    /**
     * 获取动作库
     */
    public function get_motions() {
        $motionModel = new \app\motion\model\Motion;
        $where['status'] = 1;
        $order['create_time'] = 'desc';
        $lists = $motionModel->get_motions($where, $order);
        return $lists;
    }

}

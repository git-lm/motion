<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Coach as coachModel;

class Coach extends BasicAdmin {

    public function initialize() {
        $this->coachModel = new coachModel();
    }

    /**
     * 渲染会员首页
     */
    public function index() {
        $this->assign('title', '教练列表');
        return $this->fetch();
    }

    /**
     * 获取会员列表信息
     */
    public function get_lists() {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/d') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }

        $where[] = ['status', '<>', 0];
        $order['create_time'] = 'desc';
        $lists = $this->coachModel->get_coachs($where, $order, $page, $limit);
        $count = count($this->coachModel->get_coachs($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add() {
        return $this->fetch();
    }

    /**
     * 新增会员数据
     */
    public function add_info() {
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        //验证数据有效性
        $data['name'] = $name;
        $data['phone'] = $phone;
        $validate = $this->coachModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->coachModel->add($data);
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
            $this->error('请正确选择类型');
        }
        //判断类型是否存在
        $list = $this->check_data($id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info() {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        if (!$id) {
            $this->error('请正确选择会员');
        }
        //判断类型是否存在
        $this->check_data($id);
        //验证数据
        $data['name'] = $name;
        $data['phone'] = $phone;
        $validate = $this->coachModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $where['id'] = $id;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 删除会员
     */
    public function del() {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择会员');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->coachModel->edit($data, $where);
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
            $this->error('请正确选择会员');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = $value;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 渲染添加时间页面
     */
    public function time() {
        //获取数据
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //获取现在时间
        $list = $this->check_data($mid);
        $list['expire_time_show'] = date('Y-m-d H:i', $list['expire_time']);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 会员添加到期时间
     */
    public function time_info() {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $expire_time = request()->has('expire_time', 'post') ? request()->post('expire_time/s') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        if (!$expire_time) {
            $this->error('请正确选择添加时间');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['expire_time'] = strtotime($expire_time);
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('修改成功', '');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 判断类型是否存在
     */
    public function check_data($id = 0) {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->coachModel->get_coach($where);
        if (empty($list)) {
            $this->error('无此教练');
        }
        return $list;
    }

}

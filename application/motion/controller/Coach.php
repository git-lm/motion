<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Coach as coachModel;
use app\motion\model\Lesson as lessonModel;
use app\pt\model\CourseModel;
use app\pt\model\CourseExpensesModel;

class Coach extends BasicAdmin
{

    public function initialize()
    {
        $this->coachModel = new coachModel();
        $this->lessonModel = new lessonModel();
    }

    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '教练列表');
        return $this->fetch();
    }

    /**
     * 获取会员列表信息
     */
    public function get_lists()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        if ($name) {
            $where[] = ['c.name', 'like', '%' . $name . '%'];
        }

        $where[] = ['c.status', '<>', 0];
        $lists = $this->coachModel->get_coachs($where, [], $page, $limit);
        $count = count($this->coachModel->get_coachs($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 新增会员数据
     */
    public function add_info()
    {
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
    public function edit()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选教练');
        }
        //判断类型是否存在
        $list = $this->check_data($id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        if (!$id) {
            $this->error('请正确选择教练');
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
    public function del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择教练');
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
    public function handle()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $value = request()->has('value', 'post') ? request()->post('value/d') : -1;
        if (!$id) {
            $this->error('请正确选择教练');
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
     * 教练授权账号
     */
    public function auth()
    {
        //获取数据
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;

        if (!$id) {
            $this->error('请正确选择教练');
        }
        $list = $this->check_data($id);
        //获取未授权的账号
        $uid = empty($list['u_id']) ? 0 : $list['u_id'];
        $users = $this->coachModel->get_user($uid);

        //教练所属账户
        $this->assign('u_id', $uid);
        $this->assign('id', $id);
        $this->assign('users', $users);
        return $this->fetch();
    }

    /**
     * 授权信息
     */
    public function auth_info()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $uid = request()->has('uid', 'post') ? request()->post('uid/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        if (!$uid) {
            $this->error('请选择授权账号');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['u_id'] = $uid;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }
    /**
     * 判断教练是否存在
     */
    public function check_data($id = 0)
    {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->coachModel->get_coach($where);
        if (empty($list)) {
            $this->error('无此教练');
        }
        return $list;
    }
}

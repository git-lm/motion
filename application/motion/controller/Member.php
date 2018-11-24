<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Member as memberModel;

class Member extends BasicAdmin {

    public function initialize() {
        $this->memberModel = new memberModel();
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
    public function get_lists() {
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
        $birthday = request()->has('birthday', 'post') ? request()->post('birthday/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        $sex = request()->has('sex', 'post') ? request()->post('sex/d') : 1;
        //验证数据有效性
        $data['name'] = $name;
        $data['birthday'] = $birthday;
        $data['phone'] = $phone;
        $data['sex'] = $sex;
        $validate = $this->memberModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->memberModel->add($data);
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
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择类型');
        }
        //判断类型是否存在
        $list = $this->check_data($mid);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info() {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $birthday = request()->has('birthday', 'post') ? request()->post('birthday/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        $sex = request()->has('sex', 'post') ? request()->post('sex/d') : 1;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //判断类型是否存在
        $this->check_data($mid);
        //验证数据
        $data['name'] = $name;
        $data['birthday'] = $birthday;
        $data['phone'] = $phone;
        $data['sex'] = $sex;
        $validate = $this->memberModel->validate($data);
        if (!$validate) {
            $this->error($validate);
        }
        $where['id'] = $mid;
        $code = $this->memberModel->edit($data, $where);
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
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['status'] = 0;
        $code = $this->memberModel->edit($data, $where);
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
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $value = request()->has('value', 'post') ? request()->post('value/d') : -1;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['status'] = $value;
        $code = $this->memberModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 重置密码
     */
    public function pas() {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['password'] = md5('123456');
        $code = $this->memberModel->edit($data, $where);
        if ($code) {
            $this->success('重置成功，密码123456', '');
        } else {
            $this->error('重置失败');
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
        $code = $this->memberModel->edit($data, $where);
        if ($code) {
            $this->success('修改成功', '');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 分配会员-教练
     */
    public function dis() {
        //获取数据
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $list = $this->check_data($mid);
        //获取所有教练
        $coachModel = new \app\motion\model\Coach;
        $where['status'] = 1;
        $oders['create_time'] = 'desc';
        $coachs = $coachModel->get_coachs($where, $oders);
        $this->assign('list', $list);
        $this->assign('coachs', $coachs);
        return $this->fetch();
    }

    /**
     * 分配教练信息
     */
    public function dis_info() {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //如果存在教练ID 说明是更换或者新增
        //不存在说明是解绑
        if ($c_id) {
            //判断教练是否存在
            $coachModel = new \app\motion\model\Coach;
            $cwhere['id'] = $c_id;
            $cwhere['status'] = 1;
            $coach = $coachModel->get_coach($cwhere);
            if (empty($coach)) {
                $this->error('该教练不存在，请重新选择');
            }
        }
        //判断会员是否存在
        $this->check_data($mid);

        //分配给教练
        $code = $this->memberModel->dis($mid, $c_id);
        if ($code) {
            $this->success('修改成功', '');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 判断类型是否存在
     */
    public function check_data($mid = 0) {
        //判断类型是否存在
        $where['id'] = $mid;
        $list = $this->memberModel->get_member($where);
        if (empty($list)) {
            $this->error('无此会员');
        }
        return $list;
    }

}

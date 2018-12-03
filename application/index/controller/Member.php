<?php

namespace app\index\controller;

use think\Controller;
use app\motion\model\Member as memberModel;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Member extends Controller {

    public function initialize() {
        session('motion_member.id', 6);
        // 登录状态检查
        if (!session('motion_member')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('@login')];
            return request()->isAjax() ? json($msg) : redirect($msg['url']);
        }
        $this->m_id = session('motion_member.id');
        $this->memberModel = new MemberModel();
    }

    /**
     *  首页
     */
    public function index() {
        $where [] = ['m.id', '=', $this->m_id];
        $list = $this->memberModel->get_member_info($where);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑或者新增会员信息
     */
    public function info() {
        if (empty(request()->post())) {
            $where [] = ['m.id', '=', $this->m_id];
            $list = $this->memberModel->get_member_info($where);
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $data = request()->post();
            $valiedate = $this->memberModel->validate_info($data);
            if ($valiedate) {
                $this->error($valiedate);
            }
            $data['age'] = request()->has('age', 'post') ? request()->post('age/d') : 20;
            $data['is_email'] = request()->has('is_email', 'post') ? request()->post('is_email/d') : 0;
            $data['is_wechat'] = request()->has('is_wechat', 'post') ? request()->post('is_wechat/d') : 0;
            $code = $this->memberModel->info($data, $this->m_id);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 渲染修改密码页面
     * @return type
     */
    public function pas() {
        if (!request()->post()) {
            return $this->fetch();
        } else {
            $pwd = request()->has('password', 'post') ? request()->post('password/s') : '';
            $oldPwd = request()->has('oldPwd', 'post') ? request()->post('oldPwd/s') : '';
            if (!$pwd) {
                $this->error('请正确填写');
            }
            $where [] = ['id', '=', $this->m_id];
            $where [] = ['password', '=', md5($oldPwd)];
            $list = $this->memberModel->get_member($where);
            if (empty($list)) {
                $this->error('原密码错误');
            }
            $ewhere['id'] = $this->m_id;
            $data['password'] = md5($pwd);
            $code = $this->memberModel->edit($data, $ewhere);
            if ($code) {
                $this->success('修改成功，请重新登录', '');
            } else {
                $this->error('修改失败');
            }
        }
    }

    /**
     * 更换头像
     */
    public function picture_add() {
        $picture = request()->has('picture', 'post') ? request()->post('picture/s') : '';
        $data['picture'] = $picture;
        $code = $this->memberModel->info($data, $this->m_id);
        if ($code) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 验证密码
     */
    public function check_pwd() {
        $pwd = request()->has('oldPwd', 'post') ? request()->post('oldPwd/s') : '';
        if (!$pwd) {
            $this->error('请填写原密码');
        }
        $where [] = ['id', '=', $this->m_id];
        $where [] = ['password', '=', md5($pwd)];
        $list = $this->memberModel->get_member($where);
        if (!empty($list)) {
            $this->success('验证成功');
        } else {
            $this->error('原密码不正确');
        }
    }

}

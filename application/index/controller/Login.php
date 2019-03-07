<?php

namespace app\index\controller;

use app\index\controller\MobileBase;
use app\motion\model\Member as memberModel;
use service\WechatService;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Login extends MobileBase
{

    public function initialize()
    {
        parent::initialize();
        $this->memberModel = new memberModel();
    }

    /**
     *  首页
     */
    public function index()
    {

        return $this->fetch();
    }

    public function login()
    {
        $username = request()->has('username', 'post') ? request()->post('username/s') : '';
        $password = request()->has('password', 'post') ? request()->post('password/s') : '';
        if (!$username || !$password) {
            $this->error('请完整输入');
        }
        $where[] = ['name|phone', '=', $username];
        $where[] = ['status', '<>', 0];
        $member = $this->memberModel->get_member($where);
        empty($member) && $this->error('登录账号不存在，请重新输入!');
        if ($member['password'] != md5($password)) {
            $this->error('登录密码错误，请重新输入!');
        }
        if ($member['status'] == -1) {
            $this->error('账号已经被禁用，请联系管理员!');
        }
        session('motion_member', $member);

        if (is_weixin()) {
            $this->wxinfo();
            // $this->success('登录成功，正在进入系统...', $urlString);
        } else {
            $this->memberModel->write('登录系统', '用户登录系统成功', $member['name'], $member['id']);
            $this->success('登录成功，正在进入系统...', '/list.html');
        }
    }

    public function wxinfo()
    {
        $WechatService =  new WechatService();
        $appid = $WechatService->getAppid();
        $openid = session("{$appid}_openid");;
        if (!empty($openid)) {
            //更新用户绑定信息
            //获取关注用户信息
            $fwhere['openid'] = $openid;
            $fans = Db('wechat_fans')->where($fwhere)->find();
            if (empty($fans)) {
                $this->redirect('/list');
                return;
            }
            //获取用户信息
            $where['m_id'] = session('motion_member.id');
            $memberinfo = $this->memberModel->get_member_info($where);
            if (empty($memberinfo)) {
                //新增用户信息
                $idata['m_id'] = session('motion_member.id');
                $idata['f_id'] = $fans['id'];
                $this->memberModel->add_info($idata);
            } else {
                //更新用户信息
                $data['f_id'] = $fans['id'];
                $where['m_id'] = session('motion_member.id');
                $this->memberModel->edit_info($data, $where);
            }
        }
        if (!session('motion_member')) {
            if (request()->isAjax()) {
                $this->success('登录失败，请重新登录...', '/login.html');
            } else {
                $this->redirect('/login');
            }
        } else {
            $this->memberModel->write('登录系统', '用户登录系统成功', session('motion_member.name'), session('motion_member.id'), $openid);
            if (request()->isAjax()) {
                $this->success('登录成功，正在进入系统...', '/list.html');
            } else {
                $this->redirect('/list');
            }
        }
    }



    /**
     * 退出登录
     */
    public function out()
    {
        $member = session('motion_member');
        session('motion_member', null);
        $this->memberModel->write('退出系统', '用户退出系统成功', $member['name'], $member['id']);
        $this->success('退出登录成功！', '/login.html');
    }


    /**
     * 解除绑定微信
     */
    public function untying()
    {
        $member = session('motion_member');
        $where['m_id'] = $member['id'];
        $data['f_id'] = 0;
        $this->memberModel->edit_info($data, $where);
        session('motion_member', null);
        $this->memberModel->write('解绑微信', '用户解绑微信成功', $member['name'], $member['id']);
        $this->success('解绑成功！请重新登录', '/login.html');
    }
}

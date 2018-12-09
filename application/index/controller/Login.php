<?php

namespace app\index\controller;

use think\Controller;
use app\motion\model\Member as memberModel;
use service\WechatService;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Login extends Controller
{

    public function initialize()
    {
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
        if (!$username || !$password)
        {
            $this->error('请完整输入');
        }
        $where[] = ['name|phone', '=', $username];
        $member = $this->memberModel->get_member($where);
        empty($member) && $this->error('登录账号不存在，请重新输入!');
        if ($member['password'] != md5($password))
        {
            $this->error('登录密码错误，请重新输入!');
        }
        if ($member['status'] == -1)
        {
            $this->error('账号已经被禁用，请联系管理员!');
        }
        session('motion_member', $member);
        $this->memberModel->write('登录系统', '用户登录系统成功', $member['name'], $member['id']);
        if ($this->is_weixin())
        {
            $urlString = WechatService::WeChatOauth()->getOauthRedirect(url('/index/login/wxinfo', '', true, true));
            $this->success('登录成功，正在进入系统...', $urlString);
        } else
        {
            $this->success('登录成功，正在进入系统...', '/list.html');
        }
    }

    public function wxinfo()
    {

        if (request()->has('code', 'get'))
        {
            $this->redirect('/list');
            return;
        }
        $info = WechatService::WeChatOauth()->getOauthAccessToken();
        if (!empty($info) && !empty($info['openid']))
        {
            //更新用户绑定信息
            //获取关注用户信息
            $fwhere['openid'] = $info['openid'];
            $fans = Db('wechat_fans')->where($fwhere)->find();
            if (empty($fans))
            {
                $this->redirect('/list');
                return;
            }
            //获取用户信息
            $where['m_id'] = session('motion_member.id');
            $memberinfo = $this->memberModel->get_member_info($where);
            if (empty($memberinfo))
            {
                //新增用户信息
                $idata['m_id'] = session('motion_member.id');
                $idata['f_id'] = $fans['id'];
                $this->memberModel->add_info($idata);
            } else
            {
                //更新用户信息
                $data['f_id'] = $fans['id'];
                $where['m_id'] = session('motion_member.id');
                $this->memberModel->edit_info($data, $where);
            }
        }
        if (!session('motion_member'))
        {
            $this->redirect('/login');
        } else
        {
            $this->redirect('/list');
        }
    }

    /**
     * 判断是否微信
     * @return boolean
     */
    function is_weixin()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'],
                        'MicroMessenger') !== false)
        {
            return true;
        }
        return false;
    }

    /**
     * 退出登录
     */
    public function out()
    {
        $member = session('motion_member');
        session('motion_member', NULL);
        $this->memberModel->write('退出系统', '用户退出系统成功', $member['name'], $member['id']);
        $this->success('退出登录成功！', '/login.html');
    }

}

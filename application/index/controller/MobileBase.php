<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use service\WechatService;
use app\motion\model\Member;

class MobileBase extends Controller
{
    public $session_id;
    public $weixin_config;

    /*
     * 初始化操作
     */
    public function initialize()
    {
        $appid = empty(sysconf('wechat_appid')) ? '' : sysconf('wechat_appid');
        if (session('motion_member')) { //说明已经登录了直接返回
            $this->headimg(session('motion_member.id'));
            return;
        }
        //微信浏览器
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            if (empty(session("{$appid}_openid"))) {
                if (sysconf('wechat_appid')) {

                    $openid = WechatService::webOauth(request()->url(true), 0);
                    $this->get_member($openid);
                }
            } else {
                $this->get_member(session("{$appid}_openid"));
            }
        }
    }
    /**
     * 获取用户信息并保存session
     */
    public function get_member($openid)
    {
        if (empty($openid)) {
            return;
        }
        session('member_openid' , $openid);
        $memberModel = new Member();
        $where['openid'] = $openid;

        $memberInfo = $memberModel->get_member_info($where);
        if (!empty($memberInfo['m_id'])) {
            $mwhere['m.id'] = $memberInfo['m_id'];
            $member = $memberModel->get_member($mwhere);
            if (empty($member) || $member['status'] != 1 || $member['end_time'] < time()) {
                if (request()->controller() != 'Login' && request()->action() != 'login') {
                    $this->redirect('/login');
                } else {
                    return;
                }
            }
            session('motion_member', $member);
            $this->headimg(session('motion_member.id'));
            $memberModel->write('登录系统', '用户登录系统成功', $member['name'], $member['id'], $openid);
            //$this->redirect('/list');
        } else {
            if (request()->controller() != 'Login' && request()->action() != 'login') {
                $this->redirect('/login');
            }
        }
    }

    public function headimg($m_id = 0)
    {
        $memberModel = new Member();
        $where[] = ['m.id', '=', $m_id];
        $list = $memberModel->get_member_info($where);
        if (empty($list['picture'])) {
            if (empty($list['headimgurl'])) {
                $headimg = sysconf('site_logo');
            } else {
                $headimg = $list['headimgurl'];
            }
        } else {
            $headimg = get_thumb($list['picture']);
        }
        $this->assign('heading', $headimg);
    }
}

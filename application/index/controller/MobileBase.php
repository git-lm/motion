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
        $memberModel = new Member();
        $where['openid'] = $openid;

        $memberInfo = $memberModel->get_member_info($where);
        if (!empty($memberInfo['m_id'])) {
            $mwhere['id'] = $memberInfo['m_id'];
            $member = $memberModel->get_member($mwhere);
            session('motion_member', $member);
            $this->memberModel->write('登录系统', '用户登录系统成功', $member['name'], $member['id'], $openid);
            $this->redirect('/list');
        } else {
            if (request()->controller() != 'Login' && request()->action() != 'login')
                $this->redirect('/login');
        }
    }
}

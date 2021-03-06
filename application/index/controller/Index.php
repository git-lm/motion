<?php

namespace app\index\controller;

use app\index\controller\MobileBase;
use WeChat\Script;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Index extends MobileBase
{

    public function initialize()
    {
        parent::initialize();
        // 登录状态检查
        if (!session('motion_member')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('/login')];
            return request()->isAjax() ? json_encode($msg) : $this->redirect($msg['url']);
        }
    }

    /**
     *  首页
     */
    public function index()
    {


        $type = request()->has('type', 'get') ? request()->get('type/s') : '';
        if ($type) {
            $this->assign('type', $type);
        } else {
            $this->assign('type', 'upcomming');
        }
        return $this->fetch();
    }


    public function getJsSign()
    {
        $options['appid'] = sysconf('wechat_appid');
        $options['appsecret'] = sysconf('wechat_appkey');
        $script = new Script($options);
        $sign = $script->getJsSign(url('index/classes/affirm', '', true, true));
        return $sign;
    }
}

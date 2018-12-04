<?php

namespace app\index\controller;

use think\Controller;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Index extends Controller {

    public function initialize() {
        // 登录状态检查
        if (!session('motion_member')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('/index/login')];
            return request()->isAjax() ? json_encode($msg) : $this->redirect($msg['url']);
        }
    }

    /**
     *  首页
     */
    public function index() {
        $type = request()->has('type', 'get') ? request()->get('type/s') : '';
        if ($type) {
            $this->assign('type', $type);
        } else {
            $this->assign('type', 'upcomming');
        }
        return $this->fetch();
    }

}

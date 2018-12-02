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
        $where [] = ['id', '=', $this->m_id];
        $list = $this->memberModel->get_member($where);
        $this->assign('list', $list);
        return $this->fetch();
    }

}

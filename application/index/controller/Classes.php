<?php

namespace app\index\controller;

use app\index\controller\MobileBase;
use app\pt\model\ClassesModel;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Classes extends MobileBase
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
        return $this->fetch();
    }
    public function indexAjax()
    {
        $member = session('motion_member');
        $cm = new ClassesModel();

        $lists  = $cm->lists(array('coach_id' => $member['coach_id']));
        $this->assign('lists', $lists);
        return json(array('data' => $this->fetch(), 'pages' => $lists->lastPage()));
    }
    //获取当个课程
    public function detile()
    {

        $member = session('motion_member');
        $class_id = input('get.class_id/d', 0);
        $list = ClassesModel::with('classesPrivate')->where(array('status' => 1, 'id' => $class_id, 'coach_id' => $member['coach_id']))->find();
        $this->assign('list', $list);
        return $this->fetch();
    }
}

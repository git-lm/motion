<?php

namespace app\index\controller;

use app\index\controller\MobileBase;
use app\pt\model\ClassesModel;
use app\pt\model\CoachModel;
use app\pt\model\ClassesPrivateModel;

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

    /**
     * 获取会员
     */
    public function get_members()
    {
        $coach_id = session('motion_member.coach_id');
        $cm = new CoachModel();
        $cm->setCoachId($coach_id);
        $members = $cm->coachProductMember();
        return json($members);
    }

    /**
     * 设置课程会员
     */
    public function begin()
    {
        $coach_id = session('motion_member.coach_id');
        $param['class_id'] = input('post.class_id/d', 0);
        $param['member_id'] = input('post.member_id/d', 0);
        $param['begin_at'] = date('Y-m-d H:i:s');
        $class = ClassesModel::where(array('id' => $param['class_id'], 'status' => 1, 'coach_id' => $coach_id))->find();
        if (empty($class)) {
            $this->error('无此课程');
        }
        if (date('Y-m-d H:i:s') < $class['begin_at'] || date('Y-m-d H:i:s') > $class['end_at']) {
            $this->error('不在上课时间内');
        }
        $cpm = new ClassesPrivateModel();
        $cpm->add($param);
        if ($cpm->error) {
            $this->error($cpm->error);
        } else {
            $this->success('课程开始');
        }
    }

    /**
     * 结束课程
     */
    public function end()
    {
        $coach_id = session('motion_member.coach_id');
        $class_id = input('post.class_id/d', 0);
        $class = ClassesModel::where(array('id' => $class_id, 'status' => 1, 'coach_id' => $coach_id))->find();
        if (empty($class)) {
            $this->error('无此课程');
        }
        $classesPrivate = ClassesPrivateModel::where(array('class_id' => $class_id, 'status' => 1))->find();
        if (empty($classesPrivate)) {
            $this->error('该课程未开始');
        }
        if (!empty($classesPrivate['end_at'])) {
            $this->error('该课程已结束');
        }
        $code = $classesPrivate->update(array('end_at' => date('Y-m-d H:i:s')), array('id' => $classesPrivate['id']));
        if (!empty($code)) {
            $this->success('更新成功');
        } else {
            $this->success('更新失败');
        }
    }
}

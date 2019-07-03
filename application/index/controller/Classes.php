<?php

namespace app\index\controller;

use app\index\controller\MobileBase;
use app\pt\model\ClassesModel;
use app\pt\model\CoachModel;
use app\pt\model\ClassesPrivateModel;
use WeChat\Script;

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
        $type = request()->has('type', 'get') ? request()->get('type/s') : '';
        if ($type) {
            $this->assign('type', $type);
        } else {
            $this->assign('type', 'upcomming');
        }
        return $this->fetch();
    }
    public function indexAjax()
    {
        $member = session('motion_member');
        $cm = new ClassesModel();
        $type = request()->has('type', 'get') ? request()->get('type/s') : 'upcomming';
        if ($type == 'upcomming') {
            //说明获取未到时间的
            $order['class_at'] = 'asc';
            $where[] = ['class_at', '>', date('Y-m-d H:i:s')];
        } else {
            $order['class_at'] = 'desc';
            //说明获取已过时间的
            $where[] = ['class_at', '<', date('Y-m-d H:i:s')];
        }
        $param['coach_id'] =  $member['coach_id'];
        $lists  = $cm->setWhere($where)->setOrder($order)->lists($param);
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
    /**
     * 确认上课信息
     */
    public function affirm()
    {

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
    /**
     * 获取距离
     */
    public function getDistance()
    {
        $latitude1 = input('post.latitude/s', '');
        $longitude1 = input('post.longitude/s', '');
        $coordinate = sysconf('coordinate');
        $distance = sysconf('distance');

        if (empty($latitude1) || empty($longitude1)) {
            $this->error('请先打开定位，获取允许获取位置');
        }
        if (empty($coordinate)) {
            $this->error('未配置场馆位置，请联系管理员');
        }
        if (empty($distance)) {
            $this->error('未配置距离限制');
        }
        list($latitude2, $longitude2) = explode(',', $coordinate);
        if (empty($latitude2) || empty($longitude2)) {
            $this->error('场馆位置配置错误，请联系管理员');
        }
        $position = getdistance($longitude1, $latitude1, $longitude2, $latitude2);
        $data['position'] =  round($position, 2);
        $data['distance'] =  $distance;
        $this->success('获取成功', '', $data);
    }
}

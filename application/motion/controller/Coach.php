<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Coach as coachModel;
use app\motion\model\Lesson as lessonModel;

class Coach extends BasicAdmin
{

    public function initialize()
    {
        $this->coachModel = new coachModel();
        $this->lessonModel = new lessonModel();
    }

    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '教练列表');
        return $this->fetch();
    }

    /**
     * 获取会员列表信息
     */
    public function get_lists()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/d') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        if ($name) {
            $where[] = ['c.name', 'like', '%' . $name . '%'];
        }

        $where[] = ['c.status', '<>', 0];
        $order['create_time'] = 'desc';
        $lists = $this->coachModel->get_coachs($where, $order, $page, $limit);
        $count = count($this->coachModel->get_coachs($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 新增会员数据
     */
    public function add_info()
    {
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        //验证数据有效性
        $data['name'] = $name;
        $data['phone'] = $phone;
        $validate = $this->coachModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->coachModel->add($data);
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 渲染编辑窗口
     */
    public function edit()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选教练');
        }
        //判断类型是否存在
        $list = $this->check_data($id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        if (!$id) {
            $this->error('请正确选择教练');
        }
        //判断类型是否存在
        $this->check_data($id);
        //验证数据
        $data['name'] = $name;
        $data['phone'] = $phone;
        $validate = $this->coachModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $where['id'] = $id;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 删除会员
     */
    public function del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择教练');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 禁用/启用  
     */
    public function handle()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $value = request()->has('value', 'post') ? request()->post('value/d') : -1;
        if (!$id) {
            $this->error('请正确选择教练');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = $value;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 教练授权账号
     */
    public function auth()
    {
        //获取数据
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;

        if (!$id) {
            $this->error('请正确选择教练');
        }
        $list = $this->check_data($id);
        //获取未授权的账号
        $uid = empty($list['u_id']) ? 0 : $list['u_id'];
        $users = $this->coachModel->get_user($uid);

        //教练所属账户
        $this->assign('u_id', $uid);
        $this->assign('id', $id);
        $this->assign('users', $users);
        return $this->fetch();
    }

    /**
     * 授权信息
     */
    public function auth_info()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $uid = request()->has('uid', 'post') ? request()->post('uid/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        if (!$uid) {
            $this->error('请选择授权账号');
        }
        $this->check_data($id);
        $where['id'] = $id;
        $data['u_id'] = $uid;
        $code = $this->coachModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 添加教练计划
     */
    public function add_lesson()
    {
        $where[] = ['c.status', '<>', 0];
        $where[] = ['m.coach_id', 'not null', ''];
        $order['create_time'] = 'desc';
        $coachs = $this->coachModel->get_coachs($where, $order);
        $this->assign('coachs', $coachs);
        $motionModel = new  \app\motion\model\Motion();
        $types = $motionModel->get_type_motions();
        $this->assign('types', $types);
        $is_coach = 1;
        $this->assign('is_coach', $is_coach);
        return $this->fetch('lesson/add');
    }
    /**
     * 编辑教练计划
     */
    public function edit_lesson()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择教练计划');
        }

        $lessonWhere['status'] = ['=', 1];
        $lessonWhere['id'] = ['=', $id];
        $lesson = $this->lessonModel->get_coach_lesson($lessonWhere);
        if (empty($lesson)) {
            $this->error('您选择的教练计划不存在');
        }
        $this->assign('list', $lesson);
        $where[] = ['c.status', '<>', 0];
        $where[] = ['m.coach_id', 'not null', ''];
        $order['create_time'] = 'desc';
        $coachs = $this->coachModel->get_coachs($where, $order);
        $this->assign('coachs', $coachs);
        $motionModel = new  \app\motion\model\Motion();
        $types = $motionModel->get_type_motions();
        $this->assign('types', $types);
        $is_coach = 1;
        $this->assign('is_coach', $is_coach);

        return $this->fetch('lesson/edit');
    }
    /**
     * 删除教练计划
     */
    public function del_lesson()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择教练计划');
        }

        $where['id'] = ['=', $id];
        $coach_lesson = $this->lessonModel->get_coach_lesson($where);
        if (empty($coach_lesson)) {
            $this->error('无此教练计划');
        }
        $data['status'] = 0;
        $code = $this->lessonModel->edit_coach_lesson($data, $where);
        //如果已经分发  删除计划信息
        if (!empty($coach_lesson['is_dispense'])) {
            $lesson_ids = $coach_lesson['lesson_ids'];
            $lesson_ids_arr = explode(',', $lesson_ids);
            foreach ($lesson_ids_arr as $v) {
                $lwhere['id'] = $v;
                $this->lessonModel->edit($data, $lwhere);
            }
        }
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }



    /**
     * 渲染教练计划列表
     */
    public function lessonList()
    {
        $this->assign('title', '教练课程');
        return $this->fetch();
    }

    /**
     * 获取教练计划
     */
    public function get_coach_lesson()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $class_time = request()->has('class_time', 'get') ? request()->get('class_time/s') : '';
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($class_time) {
            $ct = explode(' - ', $class_time);
            if (!empty($ct[0]) && validate()->isDate($ct[0])) {
                $begin_time = strtotime($ct[0] . ' 00:00:00');
                $where[] = ['class_time', '>=', $begin_time];
            }
            if (!empty($ct[1]) && validate()->isDate($ct[1])) {
                $end_time = strtotime($ct[1] . ' 23:59:59');
                $where[] = ['class_time', '<=', $end_time];
            }
        }
        $where[] = ['status', '=', 1];
        $order['create_time'] = 'asc';

        $lists = $this->lessonModel->get_coach_lessons($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_coach_lessons($where));
        echo $this->tableReturn($lists, $count);
    }
    /**
     * 教练计划详情
     */
    public function lesson_little()
    {
        $this->assign('title', '动作详情');
        //获取数据
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择教练计划');
        }

        $where['id'] = ['=', $id];
        $coach_lesson = $this->lessonModel->get_coach_lesson($where);
        if (empty($coach_lesson)) {
            $this->error('无此教练计划');
        }
        $this->assign('lid', $id);
        return $this->fetch();
    }
    /**
     * 编辑教练计划详情
     */
    public function little_edit()
    {

        //小动作ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }

        $where['id'] = ['=', $id];
        $list =  $this->lessonModel->get_coach_little_course($where);
        $this->assign('list', $list);
        //获取所有分组视频
        $motionModel = new \app\motion\model\Motion();
        $types = $motionModel->get_type_motions();
        $this->assign('types', $types);
        $this->assign('is_coach', 1);
        return $this->fetch('lesson/little_edit');
    }

    /**
     * 删除教练计划详情
     */
    public function little_del()
    {
        //小动作ID
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //获取详情

        $llwhere['id'] = $id;
        $little = $this->lessonModel->get_coach_little_course($llwhere);
        if (empty($little)) {
            $this->erro('无此教练计划详情');
        }
        $lwhere['id'] = $little['l_id'];
        $lesson =  $this->lessonModel->get_coach_lesson($lwhere);
        $where['id'] = $id;
        $data['status'] = 0;
        $code =   $this->lessonModel->coach_little_edit($data, $where);
        if (!empty($lesson['is_dispense'])) {
            $lesson_course_ids_arr =  explode(',', $little['lesson_course_ids']);
            foreach ($lesson_course_ids_arr as $v) {
                $where['id'] = $v;
                $ldata['status'] = 0;
                $this->lessonModel->little_edit($data, $where);
            }
        }
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取教练计划详情
     */
    public function get_little_courses()
    {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $where[] = ['l_id', '=', $lid];
        $where[] = ['status', '=', 1];
        $order['create_time'] = 'asc';

        $lists = $this->lessonModel->get_coach_little_courses($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_coach_little_courses($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 分发教练计划
     */
    public function lesson_dispense()
    {   //排课ID
        $lid = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }
        $lessonWhere['id'] = ['=', $lid];
        $lesson = $this->lessonModel->get_coach_lesson($lessonWhere);
        $littleWhere['l_id'] = ['=', $lid];
        $little = $this->lessonModel->get_coach_little_courses($littleWhere);
        if (empty($little)) {
            $this->error('未添加计划详情');
        }

        //获取教练信息
        $lessonIdArr = array(); //定义一个空数据数组 记录每次添加的自增ID
        if (!empty($lesson['member_ids'])) {
            $member_ids = explode(',', $lesson['member_ids']);
            unset($lesson['id']); //去除ID 防止添加时ID字段
            foreach ($member_ids as $v) {
                $lesson['m_id'] = $v;
                $code = $this->lessonModel->add($lesson);
                $lessonIdArr[] = $code;
            }
            $lessonids = implode(',', $lessonIdArr);
            $where['id'] = $lid;
            $data['lesson_ids'] = $lessonids;
            $data['is_dispense'] = 1;
            $this->lessonModel->edit_coach_lesson($data, $where);
        }

        $data = array(); //初始化 data 数据
        foreach ($little as $l) {
            $littleIdArr = array(); //定义一个空数据数组 记录每次添加的自增ID
            $little_id = $l['id'];
            unset($l['id']); //去除ID 防止添加时ID字段
            foreach ($lessonIdArr as $v) {
                $l['l_id'] = $v;
                $code = $this->lessonModel->little_add($l);
                $littleIdArr[] = $code;
            }
            $littleids = implode(',', $littleIdArr);
            $where['id'] = $little_id;
            $data['lesson_course_ids'] = $littleids;
            $this->lessonModel->coach_little_edit($data, $where);
        }

        if ($code) {
            $this->success('分发成功', '');
        } else {
            $this->error('分发失败');
        }
    }


    /**
     * 判断教练是否存在
     */
    public function check_data($id = 0)
    {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->coachModel->get_coach($where);
        if (empty($list)) {
            $this->error('无此教练');
        }
        return $list;
    }
}

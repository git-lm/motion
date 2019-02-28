<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Lesson as lessonModel;
use app\motion\model\Member as memberModel;
use app\motion\model\Course as courseModel;
use app\motion\model\Coach as coachModel;
use app\motion\model\Motion as motionModel;
use think\facade\Request;

class Lesson extends BasicAdmin
{

    public function initialize()
    {
        $this->lessonModel = new lessonModel();
        $this->memberModel = new memberModel();
        $this->courseModel = new courseModel();
        $this->coachModel = new coachModel();
        $this->motionModel = new motionModel();
    }

    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 我的会员
     */
    public function my()
    {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 获取所有会员信息
     */
    public function get_all_lists()
    {
        echo $this->get_member_lists();
    }

    /**
     * 获取我的会员信息
     */
    public function get_my_lists()
    {
        $uid = session('user.id');
        //获取所属uid 的教练
        $where['u_id'] = $uid;
        $where['status'] = 1;
        $coach = $this->coachModel->get_coach($where);
        if (empty($coach)) {
            $this->error('该账号未绑定教练');
        }
        $lwhere[] = ['c_id', '=', $coach['id']];
        echo $this->get_member_lists($lwhere);
    }

    /**
     * 获取会员列表信息
     */
    public function get_member_lists($where = [])
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $cname = request()->has('cname', 'get') ? request()->get('cname/s') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($cname) {
            $where[] = ['t.cname', 'like', '%' . $cname . '%'];
        }
        if ($expire_time) {
            $et = explode(' - ', $expire_time);
            if (!empty($et[0]) && validate()->isDate($et[0])) {
                $begin_time = strtotime($et[0] . ' 00:00:00');
                $where[] = ['expire_time', '>=', $begin_time];
            }
            if (!empty($et[1]) && validate()->isDate($et[1])) {
                $end_time = strtotime($et[1] . ' 23:59:59');
                $where[] = ['expire_time', '<=', $end_time];
            }
        }
        $where[] = ['m.status', '<>', 0];
        $order['c.id'] = 'desc';
        $order['m.create_time'] = 'desc';
        $lists = $this->memberModel->get_members($where, $order, $page, $limit);
        $count = count($this->memberModel->get_members($where));
        return $this->tableReturn($lists, $count);
    }

    /**
     * 查看排课信息
     */
    public function arrange()
    {
        $this->assign('title', '计划列表');
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请选择会员');
        }
        //验证会员是否存在
        $this->check_member_data($id);
        //获取会员信息
        $where['m_id'] = $id;
        $memberinfo = $this->memberModel->get_member_info($where);
        $this->assign('memberinfo', $memberinfo);
        $this->assign('mid', $id);
        return $this->fetch();
    }

    /**
     * 获取排课信息
     */
    public function get_arrange_lists()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $class_time = request()->has('class_time', 'get') ? request()->get('class_time/s') : '';
        if (!$id) {
            $this->error('请选择会员');
        }
        if ($name) {
            $where[] = ['l.name', 'like', '%' . $name . '%'];
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

        //验证会员是否存在
        $this->check_member_data($id);
        //获取排课信息
        $where[] = ['l.m_id', '=', $id];
        $where[] = ['l.status', '=', 1];
        $order['l.create_time'] = 'asc';
        $lists = $this->lessonModel->get_arrange_lists($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_arrange_lists($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        //获取获取ID
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //获取该会员的信息
        $this->check_member_time($mid);
        $this->assign('mid', $mid);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 新增会员动作信息
     */
    public function add_info()
    {

        $colldown_mids = request()->has('colldown_mids', 'post') ? request()->post('colldown_mids/s') : null;
        $colldown = request()->has('colldown', 'post') ? request()->post('colldown/s') : null;
        $warmup_mids = request()->has('warmup_mids', 'post') ? request()->post('warmup_mids/s') : null;
        $warmup = request()->has('warmup', 'post') ? request()->post('warmup/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $class_time = request()->has('class_time', 'post') ? request()->post('class_time/s') : null;
        $mids = request()->has('mid', 'post') ? request()->post('mid/s') : 0;
        $is_coach = request()->has('is_coach', 'post') ? request()->post('is_coach/d') : 0;
        $mid_array = explode(',', $mids);
        if (!$mids) {
            $this->error('请正确选择会员');
        }
        if (!$name) {
            $this->error('请正确填写名称');
        }
        if (!$class_time) {
            $this->error('请正确选择上课时间');
        }
        foreach ($mid_array as $mid) {
            $member = $this->check_member_data($mid);
        }

        //获取该会员时间的信息
        foreach ($mid_array as $mid) {
            $this->check_member_time($mid);
        }

        //验证数据有效性
        $data['name'] = $name; // 课程名称
        $data['warmup'] = $warmup;  //热身语
        $data['warmup_mids'] = $warmup_mids; //热身视频
        $data['colldown'] = $colldown;  //冷身语
        $data['colldown_mids'] = $colldown_mids;    //冷身视频
        $data['class_time'] = $class_time;


        $validate = $this->lessonModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['class_time'] = strtotime($class_time . ' 23:59:59');
        if (!empty($is_coach)) {
            $data['member_ids'] = $mids;
            $code = $this->lessonModel->add_coach_lesson($data);
        } else {
            $data['m_id'] = $mids;
            $data['coach_id'] = $member['c_id'];
            $code = $this->lessonModel->add($data);
        }
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
            $this->error('请正确选择');
        }
        $list = $this->check_arrange_data($id);
        $this->assign('list', $list);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 编辑会员动作信息
     */
    public function edit_info()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $colldown_mids = request()->has('colldown_mids', 'post') ? request()->post('colldown_mids/s') : null;
        $colldown = request()->has('colldown', 'post') ? request()->post('colldown/s') : null;
        $warmup_mids = request()->has('warmup_mids', 'post') ? request()->post('warmup_mids/s') : null;
        $warmup = request()->has('warmup', 'post') ? request()->post('warmup/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $class_time = request()->has('class_time', 'post') ? request()->post('class_time/s') : null;
        $is_coach = request()->has('is_coach', 'post') ? request()->post('is_coach/d') : 0;
        $mids = request()->has('mid', 'post') ? request()->post('mid/s') : 0;
        $mid_array = explode(',', $mids);
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }
        if (!$name) {
            $this->error('请填写动作名称');
        }
        if (!$class_time) {
            $this->error('请正确选择上课时间');
        }
        foreach ($mid_array as $mid) {
            $member = $this->check_member_data($mid);
        }
        //获取该会员时间的信息
        foreach ($mid_array as $mid) {
            $this->check_member_time($mid);
        }
        if (!$is_coach) {
            //判断排课信息
            $list = $this->check_arrange_data($id);
        }
        //验证数据有效性
        $data['name'] = $name; // 课程名称
        $data['warmup'] = $warmup;  //热身语
        $data['warmup_mids'] = $warmup_mids; //热身视频
        $data['colldown'] = $colldown;  //冷身语
        $data['colldown_mids'] = $colldown_mids;    //冷身视频
        $data['class_time'] = $class_time;
        $validate = $this->lessonModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['class_time'] = strtotime($class_time . ' 23:59:59');

        if ($is_coach) {
            $data['member_ids'] = $mids;
            $code = $this->edit_coach_lesson($data, $id);
            // $code = $this->lessonModel->edit_coach_lesson($data, $where);
        } else {
            $where['id'] = $id;
            $code = $this->lessonModel->edit($data, $where);
        }

        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 编辑教练计划
     * @param $data 数据
     * @param $id motion_coach_lesson 主键ID
     */
    public function edit_coach_lesson($data, $id)
    {
        $where['id'] = $id;
        $coach_lesson = $this->lessonModel->get_coach_lesson();
        $this->lessonModel->edit_coach_lesson($data, $where);
        if (!empty($coach_lesson['is_dispense'])) {
            //说明已经分发了  还要修改计划信息
            unset($data['member_ids']);
            $lesson_ids = $coach_lesson['lesson_ids'];
            $lesson_ids_arr = explode(',', $lesson_ids);
            foreach ($lesson_ids_arr as $v) {
                $lwhere['id'] = $v;
                $code =  $this->lessonModel->edit($data, $lwhere);
            }
        }
        return $code;
    }

    /**
     * 删除课程
     */
    public function del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择会员动作');
        }
        $this->check_arrange_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 查看小动作
     */
    public function little()
    {
        $this->assign('title', '动作详情');
        //排课ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //判断排课是否存在
        $this->check_arrange_data($id);
        $this->assign('lid', $id);
        return $this->fetch();
    }

    /**
     * 获取小动作列表信息
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
        $lists = $this->lessonModel->get_little_courses($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_little_courses($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 添加小动作
     */
    public function little_add()
    {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        $is_coach = request()->has('is_coach', 'get') ? request()->get('is_coach/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }

        $this->assign('lid', $lid);
        //验证排课是否存在
        if (!$is_coach) {
            $list = $this->check_arrange_data($lid);
            $this->check_member_time($list['m_id']);
        } else {
            $where['id'] = $lid;
            $coach_lesson = $this->lessonModel->get_coach_lesson($where);
            if (empty($coach_lesson)) {
                $this->error('该教练计划不存在');
            }
        }

        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        $this->assign('is_coach', $is_coach); //是否计划
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function little_add_info()
    {
        $lid = request()->has('lid', 'post') ? request()->post('lid/d') : 0;
        $m_ids = request()->has('m_ids', 'post') ? request()->post('m_ids/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $remark = request()->has('remark', 'post') ? request()->post('remark/s') : '';
        $num = request()->has('num', 'post') ? request()->post('num/s') : '';
        $is_coach = request()->has('is_coach', 'post') ? request()->post('is_coach/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }
        if (!$name) {
            $this->error('请填写动作名称');
        }
        //判断排课是否存在
        if (!$is_coach) {
            $list = $this->check_arrange_data($lid);
        } else {
            $where['id'] = $lid;
            $coach_lesson = $this->lessonModel->get_coach_lesson($where);
            if (empty($coach_lesson)) {
                $this->error('该教练计划不存在');
            }
        }
        $data['l_id'] = $lid;
        $data['name'] = $name;
        $data['m_ids'] = $m_ids;
        $data['remark'] = $remark;
        $data['num'] = $num;
        $validate = $this->lessonModel->course_validate($data);
        if ($validate) {
            $this->error($validate);
        }
        if ($is_coach) {
            $code = $this->coach_little_add_info($data, $lid);
            // $code = $this->lessonModel->coach_little_add($data);
        } else {
            $code = $this->lessonModel->little_add($data);
        }
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }
    /**
     * 添加教练计划详情
     */
    public function coach_little_add_info($data, $lid)
    {

        $lwhere['id'] = $lid;
        $lesson = $this->lessonModel->get_coach_lesson($lwhere);
        if (!empty($lesson['is_dispense'])) {
            //说明已经分发了
            $lesson_ids_arr = explode(',', $lesson['lesson_ids']);
            $littleIdsArr = array();  //定义一个空数组  存放自增ID
            foreach ($lesson_ids_arr as $v) {
                $data['l_id'] = $v; //l_id 是计划的ID
                $code = $this->lessonModel->little_add($data);
                $littleIdsArr[] = $code;
            }
            $lesson_course_ids = implode(',', $littleIdsArr);
            $data['lesson_course_ids'] = $lesson_course_ids;
            $data['l_id'] = $lid;  // l_id  是教练计划ID
            $code = $this->lessonModel->coach_little_add($data);
            return $code;
        }
    }

    /**
     * 编辑小动作
     * @return type
     */
    public function little_edit()
    {
        //小动作ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }


        //验证小动作是否存在
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        $this->assign('list', $list);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function little_edit_info()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $is_coach = request()->has('is_coach', 'post') ? request()->post('is_coach/d') : 0;
        $m_ids = request()->has('m_ids', 'post') ? request()->post('m_ids/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $remark = request()->has('remark', 'post') ? request()->post('remark/s') : '';
        $num = request()->has('num', 'post') ? request()->post('num/s') : '';
        if (!$id) {
            $this->error('请正确选择');
        }
        if (!$name) {
            $this->error('请填写动作名称');
        }
        if (!$is_coach) {
            //验证小动作是否存在
            $list = $this->check_little_data($id);
            //验证排课是否存在
            $arrange = $this->check_arrange_data($list['l_id']);
        }
        $data['name'] = $name;
        $data['m_ids'] = $m_ids;
        $data['remark'] = $remark;
        $data['num'] = $num;
        $validate = $this->lessonModel->course_validate($data);
        if ($validate) {
            $this->error($validate);
        }

        if ($is_coach) {
            $this->coach_little_edit_info($data, $id);
            // $code = $this->lessonModel->coach_little_edit($data, $lwhere);
        } else {
            $lwhere['id'] = $id;
            $code = $this->lessonModel->little_edit($data, $lwhere);
        }

        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }
    /**
     * 编辑教练计划
     */
    public function coach_little_edit_info($data, $id)
    {
        $llwhere['id'] = ['=', $id];
        $little = $this->lessonModel->get_coach_little_course($llwhere); //获取教练计划详情
        $lwhere['id'] = ['=', $little['l_id']];
        $lesson = $this->lessonModel->get_coach_lesson($lwhere); //获取教练计划
        $this->lessonModel->coach_little_edit($data, $llwhere);
        if (!empty($lesson['is_dispense'])) {
            //说明分发了
            $lesson_course_ids_arr = explode(',', $little['lesson_course_ids']);
            foreach ($lesson_course_ids_arr as $v) {
                $where['id'] = $v;
                $this->lessonModel->little_edit($data, $where);
            }
        }
        $this->success('修改成功');
    }

    /**
     * 删除小动作
     */
    public function little_del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->little_edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 发送消息
     */
    public function send()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $array = $this->check_arrange_data($id);
        $member = $this->check_member_data($array['m_id']);
        $where['m_id'] = $member['id'];
        $memberinfo = $this->memberModel->get_member_info($where);
        if (empty($memberinfo)) {
            $this->error('无该会员信息，不能发送，请先完善');
        }
        if (!$memberinfo['is_email'] && !$memberinfo['is_wechat']) {
            $this->error('该会员不接收任何信息通知');
        }
        //发送邮件消息
        if ($memberinfo['is_email']) {
            //暂不处理
        }
        //发送微信消息
        if ($memberinfo['is_wechat']) {
            $res = $this->lessonModel->send_lesson($id);
            if ($res['errcode'] == 0) {
                //更新记录信息
                $data['is_send'] = 1;
                $where['id'] = $id;
                $this->lessonModel->edit($data, $where);
                $this->success('发送成功', '');
            } else if ($res['errcode'] = -1) {
                $this->error($res['msg']);
            } else {
                $this->error('发送失败');
            }
        }
    }

    /**
     * 获取视频地址
     */
    public function get_motion_url()
    {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择');
        }
        $where['id'] = $mid;
        $list = $this->motionModel->get_motion($where);
        if (!empty($list)) {
            preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $list['url'], $matched);
            $list['url'] = $matched[1];
        }
        echo $this->success($list);
    }

    /**
     * 获取课程
     * @return type
     */
    public function get_courses($pid = 0)
    {

        $where['pid'] = $pid;
        $where['status'] = 1;
        $order['create_time'] = 'desc';
        $courses = $this->courseModel->get_courses($where, $order);
        return $courses;
    }

    /**
     * 计划统计
     */
    public function statistics()
    {
        $mid = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $end_time = request()->has('search_time', 'get') ? strtotime(request()->get('search_time/s') . ' 23:59:59') : time();
        $begin_time =  strtotime('-7 day', $end_time);

        $where[] = ['m.id', '=', $mid];
        $where[] = ['l.status', '=', 1];
        $where[] = ['class_time', '>=', $begin_time];
        $where[] = ['class_time', '<=', $end_time];
        $order['class_time'] =  'asc';
        $lesson = $this->lessonModel->get_arrange_lists($where, $order);

        // $little_array = array();
        foreach ($lesson as $k => $l) {
            $littleWhere  = array();
            $littleWhere[] = ['status', '=', 1];
            $littleWhere[] = ['l_id', '=', $l['id']];
            $little = $this->lessonModel->get_little_courses($littleWhere);
            $lesson[$k]['little'] = $little;
            // $little_array[$l['id']] = $little;
        }
        // $this->assign('little', $little_array);
        $this->assign('lesson', $lesson);
        return $this->fetch();
    }



    /**
     * 判断会员是否存在
     */
    public function check_member_data($id = 0)
    {
        //判断类型是否存在
        $where['id'] = $id;
        $list = $this->memberModel->get_member($where);
        if (empty($list)) {
            $this->error('无此会员');
        }
        return $list;
    }

    /**
     * 验证会员是否在时间内
     * @param type $id
     */
    public function check_member_time($id = 0)
    {
        $where[] = ['id', '=', $id];
        $member = $this->memberModel->get_member($where);
        if (empty($member) || empty($member['c_id'])) {
            $this->error('无此会员或者该会员无教练');
        }

        $twhere[] = ['mt.m_id', '=', $id];
        $twhere[] = ['mt.status', '=', 1];
        $twhere[] = ['end_time|begin_time', '>', time()];
        $time = $this->memberModel->get_member_time($twhere);
        if (empty($time)) {
            $this->error('该会员未开通或未到时间');
        }
    }

    /**
     * 判断排课是否存在
     */
    public function check_arrange_data($id = 0)
    {
        //判断排课是否存在
        $where['id'] = $id;
        $list = $this->lessonModel->get_arrange_list($where);
        if (empty($list)) {
            $this->error('无此排课');
        }

        $list['class_time_show'] = date('Y-m-d', $list['class_time']);
        return $list;
    }

    /**
     * 验证小动作是否存在
     */
    public function check_little_data($id = 0)
    {
        $where['id'] = $id;
        $list = $this->lessonModel->get_little_course($where);
        if (empty($list)) {
            $this->error('无此小动作');
        }
        return $list;
    }
}

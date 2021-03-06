<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Member as memberModel;
use app\motion\model\MemberData as memberDataModel;
use app\motion\model\Template;
use think\facade\Request;

class Member extends BasicAdmin
{

    public function initialize()
    {
        $this->memberModel = new memberModel();
        $this->memberDataModel = new memberDataModel();
    }

    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '会员列表');
        $types = $this->memberModel->getMemberTypes();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 获取会员列表信息
     */
    public function get_lists()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $cname = request()->has('cname', 'get') ? request()->get('cname/s') : '';
        $phone = request()->has('phone', 'get') ? request()->get('phone/s') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        $type_id = request()->has('type_id', 'get') ? request()->get('type_id/d') : 0;
        if ($name) {
            $where[] = ['m.name', 'like', '%' . $name . '%'];
        }
        if ($type_id) {
            $where[] = ['m.type_id', '=', $type_id];
        }
        if ($cname) {
            $where[] = ['c.name', 'like', '%' . $cname . '%'];
        }
        if ($phone) {
            $where[] = ['m.phone', '=', $phone];
        }
        if ($expire_time) {
            $et = explode(' - ', $expire_time);
            if (!empty($et[0]) && validate()->isDate($et[0])) {
                $begin_time = strtotime($et[0] . ' 00:00:00');
                $where[] = ['end_time', '>=', $begin_time];
            }
            if (!empty($et[1]) && validate()->isDate($et[1])) {
                $end_time = strtotime($et[1] . ' 23:59:59');
                $where[] = ['end_time', '<=', $end_time];
            }
        }
        $where[] = ['m.status', '<>', 0];
        $order['create_time'] = 'desc';
        $lists = $this->memberModel->get_members($where, $order, $page, $limit);
        $count = count($this->memberModel->get_members($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        $coachModel = new \app\motion\model\Coach;
        $coachs =  $coachModel->get_coachs_for_member();
        $types =  $this->memberModel->getMemberTypes();
        $this->assign('types', $types);
        $this->assign('coachs', $coachs);
        return $this->fetch();
    }

    /**
     * 新增会员数据
     */
    public function add_info()
    {
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        $coach_id = request()->has('coach_id', 'post') ? request()->post('coach_id/d') : 0;
        $type_id = request()->has('type_id', 'post') ? request()->post('type_id/d') : 0;
        //验证数据有效性
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['coach_id'] = $coach_id;
        $data['type_id'] = $type_id;
        $validate = $this->memberModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->memberModel->add($data);
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
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择类型');
        }
        //判断类型是否存在
        $list = $this->check_data($mid);
        $this->assign('list', $list);
        $coachModel = new \app\motion\model\Coach;
        $coachs =  $coachModel->get_coachs_for_member($mid);
        $this->assign('coachs', $coachs);
        $types =  $this->memberModel->getMemberTypes();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info()
    {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $phone = request()->has('phone', 'post') ? request()->post('phone/s') : '';
        $coach_id = request()->has('coach_id', 'post') ? request()->post('coach_id/d') : 0;
        $type_id = request()->has('type_id', 'post') ? request()->post('type_id/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //判断类型是否存在
        $this->check_data($mid);
        //验证数据
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['coach_id'] = $coach_id;
        $data['type_id'] = $type_id;
        $data['mid'] = $mid;
        $validate = $this->memberModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        unset($data['mid']);
        $where['id'] = $mid;
        $code = $this->memberModel->edit($data, $where);
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
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['status'] = 0;
        $data['coach_id'] = 0;
        $code = $this->memberModel->edit($data, $where);
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
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $value = request()->has('value', 'post') ? request()->post('value/d') : -1;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['status'] = $value;
        $code = $this->memberModel->edit($data, $where);
        if ($code) {
            $this->success('操作成功', '');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 重置密码
     */
    public function pas()
    {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        $where['id'] = $mid;
        $data['password'] = md5('123456');
        $code = $this->memberModel->edit($data, $where);
        if ($code) {
            $this->success('重置成功，密码123456', '');
        } else {
            $this->error('重置失败');
        }
    }

    /**
     * 渲染添加时间页面
     */
    public function time_add()
    {
        //获取数据
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //获取会员信息
        $list = $this->check_data($mid);
        $this->assign('list', $list);
        //获取最大时间
        $where['m_id'] = $mid;
        $time = $this->memberModel->get_max_time($where);
        $time['end_time_show'] = empty($time['end_time']) ? '' : date('Y-m-d', $time['end_time']);
        $this->assign('time', $time);
        return $this->fetch();
    }

    /**
     * 会员添加到期时间
     */
    public function time_add_info()
    {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $expire_time = request()->has('expire_time', 'post') ? request()->post('expire_time/s') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        if ($expire_time) {
            $et = explode(' - ', $expire_time);
            if (empty($et[0]) || !validate()->isDate($et[0]) || empty($et[1]) || !validate()->isDate($et[1])) {
                $this->error('请正确选择时间');
            } else {
                $data['begin_time'] = strtotime($et[0]);
                $data['end_time'] = strtotime($et[1]);
            }
        } else {
            $this->error('请选择时间');
        }
        $data['m_id'] = $mid;
        $this->check_data($mid);
        $code = $this->memberModel->time_add($data);
        if ($code) {
            $this->success('添加成功', '');
        } else {
            $this->error('添加失败');
        }
    }

    /**
     * 编辑会员时间
     */
    public function time_edit()
    {
        //获取数据
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择会员时间');
        }
        $list = $this->check_time_data($id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 会员添加到期时间
     */
    public function time_edit_info()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $expire_time = request()->has('expire_time', 'post') ? request()->post('expire_time/s') : 0;
        if (!$id) {
            $this->error('请正确选择会员时间');
        }
        if ($expire_time) {
            $et = explode(' - ', $expire_time);
            if (empty($et[0]) || !validate()->isDate($et[0]) || empty($et[1]) || !validate()->isDate($et[1])) {
                $this->error('请正确选择时间');
            } else {
                $data['begin_time'] = strtotime($et[0]);
                $data['end_time'] = strtotime($et[1]);
            }
        } else {
            $this->error('请选择时间');
        }
        $this->check_time_data($id);
        $where['mt.id'] = $id;
        $code = $this->memberModel->time_edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 删除会员时间
     */
    public function time_del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择会员时间');
        }
        $this->check_time_data($id);
        $data['status'] = 0;
        $where['mt.id'] = $id;
        $code = $this->memberModel->time_edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 渲染会员时间页面
     */
    public function time()
    {
        $this->assign('title', '会员时间列表');
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择');
        }
        $this->assign('mid', $mid);
        return $this->fetch();
    }

    /**
     * 获取会员时间
     */
    public function get_member_times()
    {
        $mid = request()->has('mid') ? request()->param('mid/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/d') : '';
        if (!$mid) {
            $this->error('');
        }
        if ($expire_time && validate()->isDate($expire_time)) {
            $where[] = ['end_time|begin_time', '>', strtotime($expire_time)];
        }
        $this->check_data($mid);
        $where[] = ['m_id', '=', $mid];
        $where[] = ['mt.status', '=', 1];
        if (request()->isPost('mid')) {
            $where[] = ['end_time', '>', time()];
        }
        $order['end_time'] = 'desc';
        $lists = $this->memberModel->get_member_times($where, $order, $page, $limit);
        echo $this->tableReturn($lists);
    }

    /**
     * 分配会员-教练
     */
    public function dis()
    {
        //获取数据
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $list = $this->check_data($mid);
        //获取所有教练
        $coachModel = new \app\motion\model\Coach;
        $coachs = $coachModel->get_coachs();
        $this->assign('list', $list);
        $this->assign('coachs', $coachs);
        return $this->fetch();
    }

    /**
     * 分配教练信息
     */
    public function dis_info()
    {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        $c_id = request()->has('c_id', 'post') ? request()->post('c_id/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //如果存在教练ID 说明是更换或者新增
        //不存在说明是解绑
        if ($c_id) {
            //判断教练是否存在
            $coachModel = new \app\motion\model\Coach;
            $cwhere['id'] = $c_id;
            $cwhere['status'] = 1;
            $coach = $coachModel->get_coach($cwhere);
            if (empty($coach)) {
                $this->error('该教练不存在，请重新选择');
            }
        }
        //判断会员是否存在
        $this->check_data($mid);
        //分配给教练
        $code = $this->memberModel->dis($mid, $c_id);
        if ($code) {
            $this->success('修改成功', '');
        } else {
            $this->error('修改失败');
        }
    }
    /**
     * 渲染身体变化
     */
    public function physical()
    {
        return $this->fetch();
    }
    /**
     * 加载身体变化数据
     */
    public function getPhysical()
    {
        $mid = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $end_time = request()->has('search_time', 'get') ? strtotime(request()->get('search_time/s') . ' 23:59:59') : time();
        if (Request()->isMobile()) {
            $begin_time =  strtotime('-7 day', $end_time);
        } else {
            $begin_time =  strtotime('-1 month', $end_time);
        }

        $where[] = ['m_id', '=', $mid];
        $where[] = ['create_time', '>=', $begin_time];
        $where[] = ['create_time', '<=', $end_time];
        $order['create_time'] = 'desc';
        $memberData = $this->memberDataModel->get_member_datas($where, $order);
        $dataInfo = $this->memberDataModel->dataHandle($memberData);
        echo json_encode($dataInfo);
    }
    /**
     * 初始化课程
     */
    public function initializeClass()
    {
        $mid = request()->has('mid') ? request()->param('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        $this->check_data($mid);
        if (request()->isGet()) {
            $this->assign('mid', $mid);
            $template = Template::where(['status' => 1])->select();
            $this->assign('template', $template);
            return $this->fetch();
        }
        $expire_time = request()->has('expire_time', 'post') ? request()->post('expire_time/s') : '';
        if (!$expire_time) {
            $this->error('请正确选择时间');
        }
        $template_id = input('post.template_id/d', 0);
        if (!$template_id) {
            $this->error('请正确选择模板');
        }
        if (isset($expire_time) && $expire_time !== '') {
            list($start, $end) = explode(' - ', $expire_time);
        }
        if (!$start || !$end) {
            $this->error('请正确选择时间');
        }
        $code = $this->memberModel->initializeClass($mid,  $template_id, $start, $end);
        if ($code) {
            $this->success('添加成功', '');
        } else {
            $this->error('添加失败');
        }
    }


    /**
     * 判断会员是否存在
     */
    public function check_data($mid = 0)
    {
        //判断类型是否存在
        $where['m.id'] = $mid;
        $list = $this->memberModel->get_member($where);
        if (empty($list)) {
            $this->error('无此会员');
        }
        return $list;
    }

    /**
     * 判断会员时间是否存在
     */
    public function check_time_data($id = 0)
    {
        //判断类型是否存在
        $where['mt.id'] = $id;
        $list = $this->memberModel->get_member_time($where);
        if (empty($list)) {
            $this->error('无此会员时间');
        }
        return $list;
    }

    /**
     * 获取所属教练的信息
     */
    public function get_coach()
    {
        $coach_id = request()->has('coach_id', 'post') ? request()->post('coach_id/d') : 0;
        $coachModel = new \app\motion\model\Coach;
        $where['id'] = $coach_id;
        $where['status'] = 1;
        $coach = $coachModel->get_coach($where);
        $this->success($coach);
    }
}

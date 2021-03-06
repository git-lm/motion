<?php

namespace app\index\controller;

use app\index\controller\MobileBase;
use app\motion\model\Member as memberModel;
use app\pt\model\ClassesModel;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Member extends MobileBase
{

    public function initialize()
    {
        parent::initialize();
        // 登录状态检查
        if (!session('motion_member')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('/login.html')];
            return request()->isAjax() ? json($msg) : $this->redirect($msg['url']);
        }
        $this->motion_member = session('motion_member');
        $this->m_id = session('motion_member.id');
        $this->memberModel = new MemberModel();
    }

    /**
     *  首页
     */
    public function index()
    {
        $where[] = ['m.id', '=', $this->m_id];
        $list = $this->memberModel->get_member_info($where);
        if (empty($list['picture'])) {
            if (empty($list['headimgurl'])) {
                $list['picture'] = sysconf('site_logo');
            } else {
                $list['picture'] = $list['headimgurl'];
            }
        }
        $this->assign('list', $list);
        //获取最新的照片
        $pwhere[] = ['m_id', '=', $this->m_id];
        $order['create_time'] = 'desc';
        $photo = $this->memberModel->get_member_photo($pwhere, $order);
        $this->assign('photo', $photo);
        //正在上课的信息
        $classes = ClassesModel::withJoin(['coach', 'course'], 'left')
            ->where(array('classes_model.coach_id' => $this->motion_member['coach_id']))
            ->where(array('classes_model.status' => 1))
            ->whereTime('class_at', 'today')
            ->select();
        $this->assign('classes', $classes);
        return $this->fetch();
    }

    /**
     * 编辑或者新增会员信息
     */
    public function info()
    {
        if (empty(request()->post())) {
            $where[] = ['m.id', '=', $this->m_id];
            $list = $this->memberModel->get_member_info($where);
            if (empty($list['picture'])) {
                if (empty($list['headimgurl'])) {
                    $list['picture_show'] = sysconf('site_logo');
                } else {
                    $list['picture_show'] = $list['headimgurl'];
                }
            } else {
                $list['picture_show'] = get_thumb($list['picture']);
            }
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $data = request()->post();
            $valiedate = $this->memberModel->validate_info($data);
            if ($valiedate) {
                $this->error($valiedate);
            }
            $data['age'] = request()->has('age', 'post') ? request()->post('age/d') : 20;
            $data['is_email'] = request()->has('is_email', 'post') ? request()->post('is_email/d') : 0;
            $data['is_wechat'] = request()->has('is_wechat', 'post') ? request()->post('is_wechat/d') : 0;
            $code = $this->memberModel->info($data, $this->m_id);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 渲染修改密码页面
     * @return type
     */
    public function pas()
    {
        if (!request()->post()) {
            return $this->fetch();
        } else {
            $pwd = request()->has('password', 'post') ? request()->post('password/s') : '';
            $oldPwd = request()->has('oldPwd', 'post') ? request()->post('oldPwd/s') : '';
            if (!$pwd) {
                $this->error('请正确填写');
            }
            $where[] = ['m.id', '=', $this->m_id];
            $where[] = ['m.password', '=', md5($oldPwd)];
            $list = $this->memberModel->get_member($where);
            if (empty($list)) {
                $this->error('原密码错误');
            }
            $ewhere['id'] = $this->m_id;
            $data['password'] = md5($pwd);
            $code = $this->memberModel->edit($data, $ewhere);
            if ($code) {
                $this->success('修改成功，请重新登录', '');
            } else {
                $this->error('修改失败');
            }
        }
    }

    /**
     * 更换头像
     */
    public function picture_add()
    {
        $picture = request()->has('picture', 'post') ? request()->post('picture/s') : '';
        $data['picture'] = $picture;
        $code = $this->memberModel->info($data, $this->m_id);
        if ($code) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 验证密码
     */
    public function check_pwd()
    {
        $pwd = request()->has('oldPwd', 'post') ? request()->post('oldPwd/s') : '';
        if (!$pwd) {
            $this->error('请填写原密码');
        }
        $where[] = ['id', '=', $this->m_id];
        $where[] = ['password', '=', md5($pwd)];
        $list = $this->memberModel->get_member($where);
        if (!empty($list)) {
            $this->success('验证成功');
        } else {
            $this->error('原密码不正确');
        }
    }

    /**
     * 会员照片
     */
    public function photo()
    {
        //获取未上传完成的照片
        $notwhere[] = ['front_photo|back_photo|side_photo', 'null', ''];
        $notwhere[] = ['m_id', '=', $this->m_id];
        $notphoto = $this->memberModel->get_member_photo($notwhere);
        $this->assign('notphoto', $notphoto);
        $allwhere[] = ['front_photo&back_photo&side_photo', 'not null', ''];
        $allwhere[] = ['m_id', '=', $this->m_id];
        $count = count($this->memberModel->get_member_photos($allwhere));
        $this->assign('pages', $count);
        return $this->fetch();
    }

    /**
     * 获取会员已完成的照片
     */
    public function get_all_photo()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 1;
        $order['create_time'] = 'desc';
        //获取所有上传完成的照片
        $allwhere[] = ['front_photo&back_photo&side_photo', 'not null', ''];
        $allwhere[] = ['m_id', '=', $this->m_id];
        $allphotos = $this->memberModel->get_member_photos($allwhere, $order, $page, $limit);

        $this->assign('allphotos', $allphotos);

        return $this->fetch('photo_ajax');
    }

    /**
     * 添加会员照片
     */
    public function photo_add()
    {

        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $photo = request()->has('photo', 'post') ? request()->post('photo/s') : '';
        if (!$name && $name != 'front' && $name != 'back' && $name != 'side') {
            $this->error('请正确选择位置');
        }
        if (!$photo) {
            $this->error('请上传照片');
        }
        $data['name'] = $name;
        $data['photo'] = $photo;
        $data['m_id'] = $this->m_id;
        $code = $this->memberModel->photo_check_add($data);
        if ($code) {
            //验证是否有未完成的  没有则更新
            //获取未上传完成的照片
            $notwhere[] = ['front_photo|back_photo|side_photo', 'null', ''];
            $notphoto = $this->memberModel->get_member_photo($notwhere);
            if ($notphoto) {
                echo json_encode(array('code' => 2, 'msg' => '上传成功'));
            } else {
                echo json_encode(array('code' => 1, 'msg' => '上传成功'));
            }
        } else {
            $this->error('上传失败');
        }
    }
    /**
     * 会员运动数据
     */
    public function data()
    {
        $where = " FROM_UNIXTIME(create_time , '%Y-%m-%d') like '" . date('Y-m-d') . "' and m_id = " . $this->m_id;
        $lists = $this->memberModel->get_today_data_info($where);
        $this->assign('list', !empty($lists) ? $lists[0] : array());
        return $this->fetch('data');
    }
    /**
     * 添加会员运动数据
     */
    public function data_add()
    {
        //获取今日是否保存过
        $where = " FROM_UNIXTIME(create_time , '%Y-%m-%d') like '" . date('Y-m-d') . "' and m_id = " . $this->m_id;
        $memberData = $this->memberModel->get_member_data($where);
        if (empty($memberData)) {
            //说明没有保存则直接保存
            $data['create_time'] = time();
            $data['m_id'] = $this->m_id;
            $code = $this->memberModel->data_add($data);
            if (!$code) {
                $this->error('保存失败');
            }
            $memberDataId = $code;
        } else {
            $data['update_time'] = time();
            $code = $this->memberModel->data_edit($data, array('id' => $memberData['id']));
            if (!$code) {
                $this->error('保存失败');
            }
            //说明已经保存获取保存的ID 
            $memberDataId = $memberData['id'];
        }
        //判断是否保存了运动数据详情
        $infoWhere[] = ['d_id', '=', $memberDataId];
        $memberDataInfo = $this->memberModel->get_member_data_info($infoWhere);
        if (!empty($memberDataInfo)) {
            //今日运动数据详情 不为空则修改
            $post = request()->post();
            $code = $this->memberModel->data_info_edit($post, array('d_id' => $memberDataId));
            if ($code) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            //为空则新增
            $post = request()->post();
            $post['d_id'] = $memberDataId;
            $code = $this->memberModel->data_info_add($post);
            if ($code) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }
    /**
     * 获取一周数据
     */
    public function getPhysical()
    {
        //获取当前时间的前一周时间
        $beforeSeven = strtotime(date('Y-m-d 00:00:00', strtotime('-7 days')));
        //获取当前时间的前一天
        $beforeOne = strtotime(date('Y-m-d 23:59:59', strtotime('-1 days')));

        $where[] = ['create_time', '>', $beforeSeven];
        $where[] = ['create_time', '<', $beforeOne];
        $where[] = ['m_id', '=', $this->m_id];
        $order = 'create_time asc';
        $memberData = new \app\motion\model\MemberData();
        $lists = $memberData->get_member_datas($where, $order);
        $dataInfo = $memberData->dataHandle($lists);
        echo json_encode($dataInfo);
    }
}

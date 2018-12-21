<?php

namespace app\wx\controller;

use think\Controller;
use app\motion\model\Member as memberModel;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Member extends Controller
{

    public function initialize()
    {
        // 登录状态检查
        if (!session('motion_member'))
        {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('/login.html')];
            return request()->isAjax() ? json($msg) : $this->redirect($msg['url']);
        }
        $this->m_id = session('motion_member.id');
        $this->memberModel = new MemberModel();
    }

    /**
     *  首页
     */
    public function index()
    {
        $where [] = ['m.id', '=', $this->m_id];
        $list = $this->memberModel->get_member_info($where);
        if (empty($list['picture']))
        {
            if (empty($list['headimgurl']))
            {
                $list['picture'] = sysconf('site_logo');
            } else
            {
                $list['picture'] = $list['headimgurl'];
            }
        }
        $this->assign('list', $list);
        //获取最新的照片
        $pwhere [] = ['m_id', '=', $this->m_id];
        $order['create_time'] = 'desc';
        $photo = $this->memberModel->get_member_photo($pwhere, $order);
        $this->assign('photo', $photo);
        return $this->fetch();
    }

    /**
     * 编辑或者新增会员信息
     */
    public function info()
    {
        if (empty(request()->post()))
        {
            $where [] = ['m.id', '=', $this->m_id];
            $list = $this->memberModel->get_member_info($where);
            if (empty($list['picture']))
            {
                if (empty($list['headimgurl']))
                {
                    $list['picture'] = sysconf('site_logo');
                } else
                {
                    $list['picture'] = $list['headimgurl'];
                }
            }
            $this->assign('list', $list);
            return $this->fetch();
        } else
        {
            $data = request()->post();
            $valiedate = $this->memberModel->validate_info($data);
            if ($valiedate)
            {
                $this->error($valiedate);
            }
            $data['age'] = request()->has('age', 'post') ? request()->post('age/d') : 20;
            $data['is_email'] = request()->has('is_email', 'post') ? request()->post('is_email/d') : 0;
            $data['is_wechat'] = request()->has('is_wechat', 'post') ? request()->post('is_wechat/d') : 0;
            $code = $this->memberModel->info($data, $this->m_id);
            if ($code)
            {
                $this->success('保存成功', '');
            } else
            {
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
        if (!request()->post())
        {
            return $this->fetch();
        } else
        {
            $pwd = request()->has('password', 'post') ? request()->post('password/s') : '';
            $oldPwd = request()->has('oldPwd', 'post') ? request()->post('oldPwd/s') : '';
            if (!$pwd)
            {
                $this->error('请正确填写');
            }
            $where [] = ['id', '=', $this->m_id];
            $where [] = ['password', '=', md5($oldPwd)];
            $list = $this->memberModel->get_member($where);
            if (empty($list))
            {
                $this->error('原密码错误');
            }
            $ewhere['id'] = $this->m_id;
            $data['password'] = md5($pwd);
            $code = $this->memberModel->edit($data, $ewhere);
            if ($code)
            {
                $this->success('修改成功，请重新登录', '');
            } else
            {
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
        if ($code)
        {
            $this->success('修改成功');
        } else
        {
            $this->error('修改失败');
        }
    }

    /**
     * 验证密码
     */
    public function check_pwd()
    {
        $pwd = request()->has('oldPwd', 'post') ? request()->post('oldPwd/s') : '';
        if (!$pwd)
        {
            $this->error('请填写原密码');
        }
        $where [] = ['id', '=', $this->m_id];
        $where [] = ['password', '=', md5($pwd)];
        $list = $this->memberModel->get_member($where);
        if (!empty($list))
        {
            $this->success('验证成功');
        } else
        {
            $this->error('原密码不正确');
        }
    }

    /**
     * 会员照片
     */
    public function photo()
    {
        //获取未上传完成的照片
        $notwhere [] = ['front_photo|back_photo|side_photo', 'null', ''];
        $notphoto = $this->memberModel->get_member_photo($notwhere);
        $this->assign('notphoto', $notphoto);
        $allwhere [] = ['front_photo&back_photo&side_photo', 'not null', ''];
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
        $order ['create_time'] = 'desc';
        //获取所有上传完成的照片
        $allwhere [] = ['front_photo&back_photo&side_photo', 'not null', ''];
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
        if (!$name && $name != 'front' && $name != 'back' && $name != 'side')
        {
            $this->error('请正确选择位置');
        }
        if (!$photo)
        {
            $this->error('请上传照片');
        }
        $data['name'] = $name;
        $data['photo'] = $photo;
        $data['m_id'] = $this->m_id;
        $code = $this->memberModel->photo_check_add($data);
        if ($code)
        {
            //验证是否有未完成的  没有则更新
            //获取未上传完成的照片
            $notwhere [] = ['front_photo|back_photo|side_photo', 'null', ''];
            $notphoto = $this->memberModel->get_member_photo($notwhere);
            if ($notphoto)
            {
                echo json_encode(array('code' => 2, 'msg' => '上传成功'));
            } else
            {
                echo json_encode(array('code' => 1, 'msg' => '上传成功'));
            }
        } else
        {
            $this->error('上传失败');
        }
    }

}

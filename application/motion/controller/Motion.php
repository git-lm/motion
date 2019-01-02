<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\Motion as motionModel;

/**
 * 系统用户管理控制器
 * Class User
 * @package app\admin\controller
 * @author Anyon 
 * @date 2017/02/15 18:12
 */
class Motion extends BasicAdmin
{

    public function initialize()
    {
        $this->motionModel = new motionModel();
    }

    /**
     * 渲染类型页面
     */
    public function index()
    {
        $this->assign('title', '动作库列表');
        $typemodel = new \app\motion\model\MotionType();
        $where[] = ['status', '<>', 0];
        $order['create_time'] = 'desc';
        $types = $typemodel->get_motion_types($where, $order);
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 类型页面获取所有数据
     */
    public function get_lists()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $mtid = request()->has('mtid', 'get') ? request()->get('mtid/s') : 0;
        if ($name)
        {
            $where[] = ['mb.name', 'like', '%' . $name . '%'];
        }
        if ($mtid)
        {
            $where[] = ['mb.tid', 'in', $mtid];
        }
        $where[] = ['mb.status', '<>', 0];
        $order['mb.create_time'] = 'desc';
        $lists = $this->motionModel->get_motions($where, $order, $page, $limit);
        foreach ($lists as &$list)
        {
            preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $list['url'], $matched);
            $list['url_show'] = $matched[1];
        }
        $count = count($this->motionModel->get_motions($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        $where[] = ['status', '<>', 0];
        $order['create_time'] = 'desc';
        //获取所有类型
        $motioType = new \app\motion\model\MotionType;
        $lists = $motioType->get_motion_types($where, $order);
        $this->assign('types', $lists);
        return $this->fetch();
    }

    /**
     * 新增类型数据
     */
    public function add_info()
    {
        //获取数据
        $tid = request()->has('tid', 'post') ? request()->post('tid/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $url = request()->has('url', 'post') ? request()->post('url/s') : '';
        if (!$tid)
        {
            $this->error('请选择类型');
        }
        preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $url, $matched);
        $src = $matched[1];
        //验证数据
        $data['tid'] = $tid;
        $data['name'] = $name;
        $data['src'] = $src;
        $validate = $this->motionModel->validate($data);
        if ($validate)
        {
            $this->error($validate);
        }
        unset($data['src']);
        $data['url'] = $url;
        $code = $this->motionModel->add($data);
        if ($code)
        {
            $this->success('保存成功', '');
        } else
        {
            $this->error('保存失败');
        }
    }

    /**
     * 渲染编辑窗口
     */
    public function edit()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id)
        {
            $this->error('请正确选择类型');
        }
        //判断类型是否存在
        $list = $this->check_data($id);
        $where[] = ['status', '<>', 0];
        $order['create_time'] = 'desc';
        //获取所有类型
        $motioType = new \app\motion\model\MotionType;
        $lists = $motioType->get_motion_types($where, $order);
        $this->assign('types', $lists);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑类型数据
     */
    public function edit_info()
    {
        //获取数据
        $tid = request()->has('tid', 'post') ? request()->post('tid/d') : 0;
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $url = request()->has('url', 'post') ? request()->post('url/s') : '';
        if (!$tid)
        {
            $this->error('请选择类型');
        }
        if (!$id)
        {
            $this->error('请正确选择动作库');
        }
        preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $url, $matched);
        $src = $matched[1];
        //判断类型是否存在
        $this->check_data($id);
        //验证数据
        $data['tid'] = $tid;
        $data['name'] = $name;
        $data['src'] = $src;
        $validate = $this->motionModel->validate($data);
        if ($validate)
        {
            $this->error($validate);
        }
        $where['id'] = $id;
        unset($data['src']);
        $data['url'] = $url;
        $code = $this->motionModel->edit($data, $where);
        if ($code)
        {
            $this->success('编辑成功', '');
        } else
        {
            $this->error('编辑失败');
        }
    }

    public function del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id)
        {
            $this->error('请正确选择类型');
        }
        $list = $this->check_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->motionModel->edit($data, $where);
        if ($code)
        {
            $this->success('删除成功', '');
        } else
        {
            $this->error('删除失败');
        }
    }

    /**
     * 判断动作库是否存在
     */
    public function check_data($tid = 0)
    {
        $where['id'] = $tid;
        $list = $this->motionModel->get_motion($where);
        if (empty($list))
        {
            $this->error('无此动作库');
        }
        return $list;
    }

}

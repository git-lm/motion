<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\MotionType as motionTypeModel;
use service\ToolsService;

/**
 * 系统用户管理控制器
 * Class User
 * @package app\admin\controller
 * @author Anyon 
 * @date 2017/02/15 18:12
 */
class MotionType extends BasicAdmin
{

    public function initialize()
    {
        $this->motionTypeModel = new motionTypeModel();
    }

    /**
     * 渲染类型页面
     */
    public function index()
    {
        $this->assign('title', '类型列表');
        return $this->fetch();
    }

    /**
     * 类型页面获取所有数据
     */
    public function get_lists()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/d') : '';
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        $where[] = ['status', '<>', 0];
        $order['sort'] = 'asc';
        $order['create_time'] = 'desc';
        $lists = $this->motionTypeModel->get_motion_types($where, $order, $page, $limit);
        $lists = ToolsService::arr2table($lists, 'id', 'parent_id');
        $count = count($this->motionTypeModel->get_motion_types($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        $where[] = ['status', '<>', 0];
        $where[] = ['parent_id', '=', 0];
        $order['sort'] = 'asc';
        $order['create_time'] = 'desc';
        $parent_id = input('get.parent_id/d', 0);
        //获取所有菜单
        $lists = $this->motionTypeModel->get_motion_types($where, $order);
        //按级别获取菜单
        $types = $this->motionTypeModel->get_level_types($lists);
        $this->assign('types', $types);
        $this->assign('parent_id', $parent_id);
        return $this->fetch();
    }

    /**
     * 新增类型数据
     */
    public function add_info()
    {
        //获取数据
        $parent_id = request()->has('parent_id', 'post') ? request()->post('parent_id/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $sort = request()->has('sort', 'post') ? request()->post('sort/d') : '';
        //验证数据
        $data['parent_id'] = $parent_id;
        $data['name'] = $name;
        $data['sort'] = $sort;
        $validate = $this->motionTypeModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $code = $this->motionTypeModel->add($data);
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
        $tid = request()->has('tid', 'get') ? request()->get('tid/d') : 0;
        if (!$tid) {
            $this->error('请正确选择类型');
        }
        //判断类型是否存在
        $list = $this->check_data($tid);
        $order['sort'] = 'asc';
        $where[] = ['status', '<>', 0];
        $where[] = ['parent_id', '=', 0];
        $order['create_time'] = 'desc';
        //获取所有菜单
        $lists = $this->motionTypeModel->get_motion_types($where, $order);
        //按级别获取菜单
        $types = $this->motionTypeModel->get_level_types($lists);
        $this->assign('types', $types);
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
        $parent_id = request()->has('parent_id', 'post') ? request()->post('parent_id/d') : 0;
        $name = request()->has('name', 'post') ? request()->post('name/s') : '';
        $sort = request()->has('sort', 'post') ? request()->post('sort/d') : '';
        if (!$tid) {
            $this->error('请正确选择类型');
        }
        //判断类型是否存在
        $this->check_data($tid);
        //验证数据
        $data['parent_id'] = $parent_id;
        $data['name'] = $name;
        $data['sort'] = $sort;
        $validate = $this->motionTypeModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }

        $where['id'] = $tid;
        $code = $this->motionTypeModel->edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    public function del()
    {
        //获取数据
        $tid = request()->has('tid', 'post') ? request()->post('tid/d') : 0;
        if (!$tid) {
            $this->error('请正确选择类型');
        }
        $list = $this->check_data($tid);
        $where['id'] = $tid;
        $data['status'] = 0;
        $code = $this->motionTypeModel->edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 判断类型是否存在
     */
    public function check_data($tid = 0)
    {
        //判断类型是否存在
        $twhere['id'] = $tid;
        $list = $this->motionTypeModel->get_motion_type($twhere);
        if (empty($list)) {
            $this->error('无此类型');
        }
        return $list;
    }
}

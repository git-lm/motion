<?php

namespace app\motion\controller;

use controller\BasicAdmin;
use app\motion\model\MemberType as MemberTypeModel;

class MemberType extends BasicAdmin
{



    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '会员列表');
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
        $order['create_at'] = 'desc';
        // $lists = $this->motionTypeModel->get_motion_types($where, $order, $page, $limit);
        // $count = count($this->motionTypeModel->get_motion_types($where));

        $list =  MemberTypeModel::where($where)->paginate($limit, false, ['query' => $this->request->param(), 'page' => $page]);
        echo $this->tableReturn($list->toArray()['data'], $list->total());
    }


    /**
     * 渲染新增窗口
     */
    public function add()
    {
        if (request()->isGet()) {
            return $this->fetch();
        } else {
            $name = input('post.name/s', '');
            MemberTypeModel::create(['name' => $name]);
            $this->success('添加成功', '');
        }
    }

    /**
     * 渲染新增窗口
     */
    public function edit()
    {
        if (request()->isGet()) {
            $type_id = input('get.type_id/d', 0);
            $list = MemberTypeModel::get($type_id);
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $name = input('post.name/s', '');
            $type_id = input('post.tid/d', 0);
            MemberTypeModel::get($type_id)->save(['name' => $name]);
            $this->success('编辑成功', '');
        }
    }

    public function del()
    {
        $type_id = input('post.type_id/d', 0);
        MemberTypeModel::get($type_id)->save(['status' => 0]);
        $this->success('删除成功', '');
    }
}

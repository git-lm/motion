<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\MemberModel;

class Member extends BasicAdmin
{
    public function initialize()
    {
        $this->mm = new MemberModel();
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
     * 首页获取会员信息
     */
    public function get_lists()
    {
        $post = input('post.');
        $lists =  $this->mm->lists($post);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增会员
     */
    public function add()
    {
        if (request()->isGet()) {
            return $this->fetch();
        } else {
            $param['name'] = input('post.name/s');
            $param['phone'] = input('post.phone/s');
            $this->mm->add($param);
            if ($this->mm->error) {
                $this->error($this->mm->error);
            } else {
                $this->success('新增成功', '');
            }
        }
    }
    /**
     * 编辑会员
     */
    public function edit()
    {
        if (request()->isGet()) {
            $mid = input('get.mid/d', 0);
            $list = $this->mm->list(array('id' => $mid));
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $param['name'] = input('post.name/s');
            $param['phone'] = input('post.phone/s');
            $param['id'] = input('post.mid/s');
            $this->mm->edit($param);
            if ($this->mm->error) {
                $this->error($this->mm->error);
            } else {
                $this->success('编辑成功', '');
            }
        }
    }
    /**
     * 删除会员
     */

    public function del()
    {
        $mid = input('post.mid/s');
        $param['status'] = 0;
        $this->mm->updateMember($param, $mid);
        if ($this->mm->error) {
            $this->error($this->mm->error);
        } else {
            $this->success('删除成功', '');
        }
    }
    /**
     * 分配会员-教练
     */
    public function dis()
    {
        if (request()->isGet()) {
            //获取数据
            $mid = input('get.mid/s');
            if (!$mid) {
                $this->error('请正确选择会员');
            }
            $list = $this->mm->list(array('id' => $mid));
            $coachModel = new \app\motion\model\Coach;
            $where['c.status'] = 1;
            $oders['c.create_time'] = 'desc';
            $coachs = $coachModel->get_coachs($where, $oders);
            $this->assign('list', $list);
            $this->assign('coachs', $coachs);
            return $this->fetch();
        } else {
            $mid =  input('post.mid/d');
            $param['coach_id'] =  input('post.coach_id/d');
            $this->mm->updateMember($param, $mid);
            if ($this->mm->error) {
                $this->error($this->mm->error);
            } else {
                $this->success('分配成功', '');
            }
        }
    }
}

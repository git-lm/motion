<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\CourseExpensesModel;

class CourseExpense extends BasicAdmin
{
    public function initialize()
    {
        $this->cem = new CourseExpensesModel();
    }
    /**
     * 渲染团课支出首页
     */
    public function lists()
    {
        $this->assign('title', '列表');
        $cid = input('post.cid/d');
        $this->assign('course_id', $cid);
        return $this->fetch();
    }

    /**
     * 首页获取团课支出信息
     */
    public function get_lists()
    {
        $post = input('post.');
        $lists =  $this->cem->lists($post);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增团课支出
     */
    public function add()
    {
        if (request()->isGet()) {
            return $this->fetch();
        } else {
            $param['name'] = input('post.name/s');
            $this->cm->add($param);
            if ($this->cm->error) {
                $this->error($this->cm->error);
            } else {
                $this->success('新增成功', '');
            }
        }
    }
    /**
     * 编辑团课支出
     */
    public function edit()
    {
        if (request()->isGet()) {
            $cid = input('get.cid/d', 0);
            $list = $this->cm->list(array('id' => $cid));
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $param['name'] = input('post.name/s');
            $param['id'] = input('post.cid/s');
            $this->cm->edit($param);
            if ($this->cm->error) {
                $this->error($this->cm->error);
            } else {
                $this->success('编辑成功', '');
            }
        }
    }
    /**
     * 删除团课支出
     */

    public function del()
    {
        $cid = input('post.cid/s');
        $param['status'] = 1;
        $this->cm->updateCourse($param, $cid);
        if ($this->cm->error) {
            $this->error($this->cm->error);
        } else {
            $this->success('删除成功', '');
        }
    }
}

<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\CourseExpensesModel;
use app\pt\model\CourseModel;

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
        $coach_id = input('get.id/d');
        $this->assign('coach_id', $coach_id);
        return $this->fetch();
    }

    /**
     * 首页获取团课支出信息
     */
    public function get_lists()
    {
        $param = input('post.');
        $param['coach_id'] = input('get.coach_id/d');
        $lists =  $this->cem->lists($param);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增团课支出
     */
    public function add()
    {
        if (request()->isGet()) {
            $cm = new CourseModel();
            $courses = $cm->where('status', 1)->select();
            $coach_id = input('get.coach_id/d');
            $this->assign('coach_id', $coach_id);
            $this->assign('courses', $courses);
            return $this->fetch();
        } else {
            $param = input('post.');
            $this->cem->add($param);
            if ($this->cem->error) {
                $this->error($this->cem->error);
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
            $cm = new CourseModel();
            $courses = $cm->where('status', 1)->select();
            $this->assign('courses', $courses);
            $ce_id = input('get.id/d', 0);
            $list = $this->cem->list(array('id' => $ce_id));
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $param = input('post.');
            $this->cem->edit($param);
            if ($this->cem->error) {
                $this->error($this->cem->error);
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
        $ceid = input('post.id/s');
        $param['status'] = 0;
        $this->cem->updateCourseExpenses($param, $ceid);
        if ($this->cem->error) {
            $this->error($this->cem->error);
        } else {
            $this->success('删除成功', '');
        }
    }
}

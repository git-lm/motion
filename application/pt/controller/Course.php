<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\CourseModel;
use app\pt\model\CourseExpensesModel;

class Course extends BasicAdmin
{
    public function initialize()
    {
        $this->cm = new CourseModel();
    }
    /**
     * 渲染团课首页
     */
    public function index()
    {
        $this->assign('title', '团课列表');
        return $this->fetch();
    }

    /**
     * 首页获取团课信息
     */
    public function get_lists()
    {
        $post = input('post.');
        $lists =  $this->cm->lists($post);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增团课
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
     * 编辑团课
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
     * 删除团课
     */

    public function del()
    {
        $cid = input('post.cid/s');
        $param['status'] = 1;
        $this->cm->updateTable($param, $cid);
        if ($this->cm->error) {
            $this->error($this->cm->error);
        } else {
            $this->success('删除成功', '');
        }
    }
   
}

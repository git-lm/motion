<?php

namespace app\pt\controller;

use app\pt\model\ClassesModel;
use app\pt\model\CoachModel;
use app\pt\model\CommissionModel;
use app\pt\model\CourseExpensesModel;
use app\pt\model\CourseModel;
use app\pt\model\ProductExpensesModel;
use app\pt\model\ProductModel;
use controller\BasicAdmin;

class Expense extends BasicAdmin
{
    public function initialize()
    {
        $this->cem = new CourseExpensesModel();
        $this->pem = new ProductExpensesModel();
    }
    /**
     * 渲染团课支出首页
     */
    public function course()
    {
        $coach_id = input('get.id/d');
        $coach = CoachModel::where(array('id' => $coach_id))->find();
        $coach_name = !empty($coach['name']) ? $coach['name'] . '--' : '';
        $this->assign('title', $coach_name . '团课支出列表');
        $this->assign('coach_id', $coach_id);
        return $this->fetch();
    }

    /**
     * 首页获取团课支出信息
     */
    public function get_course_lists()
    {
        $param = input('post.');
        $param['coach_id'] = input('get.coach_id/d');
        $lists = $this->cem->lists($param);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增团课支出
     */
    public function addCourse()
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
    public function editCourse()
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

    public function delCourse()
    {
        $ceid = input('post.id/s');
        $param['status'] = 0;
        $this->cem->updateTable($param, $ceid);
        if ($this->cem->error) {
            $this->error($this->cem->error);
        } else {
            $this->success('删除成功', '');
        }
    }

    /**
     * 私教支出
     */
    public function product()
    {

        $coach_id = input('get.id/d');
        $coach = CoachModel::where(array('id' => $coach_id))->find();
        $coach_name = !empty($coach['name']) ? $coach['name'] . '--' : '';
        $this->assign('title', $coach_name . '私教支出列表');
        $this->assign('coach_id', $coach_id);
        return $this->fetch();
    }
    /**
     * 首页获取私教支出信息
     */
    public function get_product_lists()
    {
        $param = input('post.');
        $param['coach_id'] = input('get.coach_id/d');
        $lists = $this->pem->lists($param);
        echo $this->tableReturn($lists->all(), $lists->total());
    }

    /**
     * 新增团课支出
     */
    public function addProduct()
    {
        if (request()->isGet()) {
            $coach_id = input('get.coach_id/d');
            $pm = new ProductModel();
            $products = $pm->where('status', 1)->where('id', 'not in', function ($query) use ($coach_id) {
                $query->table('pt_product_expenses')->where(array('coach_id' => $coach_id, 'status' => 1))->field('product_id');
            })->select();
            $this->assign('coach_id', $coach_id);
            $this->assign('products', $products);
            return $this->fetch();
        } else {
            $param = input('post.');
            $this->pem->add($param);
            if ($this->pem->error) {
                $this->error($this->pem->error);
            } else {
                $this->success('新增成功', '');
            }
        }
    }
    /**
     * 编辑私教支出
     */
    public function editProduct()
    {
        if (request()->isGet()) {
            $pe_id = input('get.id/d', 0);
            $product_expenses = $this->pem->where(array('id' => $pe_id, 'status' => 1))->find();
            if (empty($product_expenses)) {
                $this->error('该私教支出信息不存在');
            }
            $pm = new ProductModel();
            $products = $pm->where('status', 1)->where('id', 'not in', function ($query) use ($product_expenses) {
                $query->table('pt_product_expenses')->where(array('coach_id' => $product_expenses['coach_id'], 'status' => 1))->where('product_id', '<>', $product_expenses['product_id'])->field('product_id');
            })->select();
            $this->assign('products', $products);

            $list = $this->pem->list(array('id' => $pe_id));
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $param = input('post.');
            $this->pem->edit($param);
            if ($this->pem->error) {
                $this->error($this->pem->error);
            } else {
                $this->success('编辑成功', '');
            }
        }
    }
    /**
     * 删除私教支出
     */

    public function delProduct()
    {
        $peid = input('post.id/s');
        $param['status'] = 0;
        $this->pem->updateTable($param, $peid);
        if ($this->pem->error) {
            $this->error($this->pem->error);
        } else {
            $this->success('删除成功', '');
        }
    }

    public function confirm()
    {
        if (request()->isGet()) {
            $cm = new ClassesModel();
            $class_id = input('get.cid/d');
            $list = $cm->with(['coach', 'course', 'commission'])->where(array('id' => $class_id, 'status' => 1))->find();
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $status = input('post.value/d', 0);
            $commission_id = input('post.id/d', 0);

            $cm = new CommissionModel();
            $cm->updateTable(array('status' => $status), $commission_id);
            if ($cm->error) {
                $this->error($cm->error);
            } else {
                $this->success('操作成功', '');
            }
        }
    }
}

<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\OrderModel;
use app\pt\model\CoachModel;
use app\pt\model\ProductModel;

class Order extends BasicAdmin
{
    public function initialize()
    {
        $this->om = new OrderModel();
    }

    /**
     * 渲染团课首页
     */
    public function index()
    {
        $mid = input('get.mid/d');
        $this->assign('mid', $mid);
        $this->assign('title', '订单列表');
        return $this->fetch();
    }
    /**
     * 首页获取订单信息
     */
    public function get_lists()
    {
        $get = input('get.');
        $get['member_id'] = input('get.mid/d', 0);
        $lists =  $this->om->lists($get);
        echo $this->tableReturn($lists->all(), $lists->total());
    }

    public function add()
    {
        if (request()->isGet()) {
            $member_id = input('get.mid/d');
            //获取教练
            $cm = new CoachModel();
            $coaches = $cm->where(array('status' => 1))->select();
            $this->assign('coaches', $coaches);
            //获取项目
            $pm = new ProductModel();
            $products = $pm->where(array('status' => 1))->select();
            $this->assign('products', $products);
            $this->assign('member_id', $member_id);
            return $this->fetch();
        } else {
            $post = input('post.');
            $this->om->addOffline($post);
            if ($this->om->error) {
                $this->error($this->om->error);
            } else {
                $this->success('添加成功', '');
            }
        }
    }
    /**
     * 删除订单
     */
    public function del()
    {
        $order_id = input('post.oid/d');
        $param['order_status'] = 0;
        $this->om->updateTable($param, $order_id);
        if ($this->om->error) {
            $this->error($this->om->error);
        } else {
            $this->success('删除成功', '');
        }
    }
}
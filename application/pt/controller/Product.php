<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\ProductModel;

class Product extends BasicAdmin
{
    public function initialize()
    {
        $this->pm = new ProductModel();
    }
    /**
     * 渲染产品首页
     */
    public function index()
    {
        $this->assign('title', '项目列表');
        return $this->fetch();
    }

    /**
     * 首页获取产品信息
     */
    public function get_lists()
    {
        $post = input('post.');
        $lists =  $this->pm->lists($post);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增产品
     */
    public function add()
    {
        if (request()->isGet()) {
            return $this->fetch();
        } else {
            $param['name'] = input('post.name/s');
            $param['price'] = input('post.price/s');
            $this->pm->add($param);
            if ($this->pm->error) {
                $this->error($this->pm->error);
            } else {
                $this->success('新增成功', '');
            }
        }
    }
    /**
     * 编辑产品
     */
    public function edit()
    {
        if (request()->isGet()) {
            $pid = input('get.pid/d', 0);
            $list = $this->pm->list(array('id' => $pid));
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $param['name'] = input('post.name/s');
            $param['price'] = input('post.price/s');
            $param['id'] = input('post.pid/s');
            $this->pm->edit($param);
            if ($this->pm->error) {
                $this->error($this->pm->error);
            } else {
                $this->success('编辑成功', '');
            }
        }
    }
    /**
     * 删除产品
     */

    public function del()
    {
        $pid = input('post.pid/s');
        $param['status'] = 0;
        $this->pm->updateProduct($param, $pid);
        if ($this->pm->error) {
            $this->error($this->pm->error);
        } else {
            $this->success('删除成功', '');
        }
    }
    
}

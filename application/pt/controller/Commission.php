<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\CommissionModel;

class Commission extends BasicAdmin
{
    public function initialize()
    {
        $this->cm = new CommissionModel();
    }
    /**
     * 渲染佣金首页
     */
    public function index()
    {
        $this->assign('title', '佣金列表');
        return $this->fetch();
    }

    /**
     * 首页获取佣金信息
     */
    public function get_lists()
    {
        $get = input('get.');
        $lists =  $this->cm->lists($get);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
}

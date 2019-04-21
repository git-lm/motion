<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\CoachModel;

class Coach extends BasicAdmin
{
    public function initialize()
    {
        $this->cm = new CoachModel();
    }
    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '教练列表');
        return $this->fetch();
    }

    /**
     * 首页获取会员信息
     */
    public function get_lists()
    {
        $get = input('get.');
        $lists =  $this->cm->lists($get);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
}

<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\CommissionModel;
use app\motion\model\Coach as CoachModel;

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

    public function my()
    {
        $this->assign('title', '课时费');
        return $this->fetch();
    }

    public function get_my_lists()
    {

        $get = input('get.');
        $user = session('user');
        $coachModel = new CoachModel();
        $coach = $coachModel->where(array('u_id' => $user['id'], 'status' => 1))->find();
        $get['coach_id'] = !empty($coach['id']) ? $coach['id'] : 0;
        $lists =  $this->cm->lists($get);
        echo $this->tableReturn($lists->all(), $lists->total());
    }
}

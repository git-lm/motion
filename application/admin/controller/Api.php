<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use app\motion\model\LoginLog;
use app\wechat\service\FansService;

/**
 * 后台首页接口
 * Class Index
 * @package app\admin\controller
 * @author Anyon 
 * @date 2017/02/15 10:41
 */
class Api extends BasicAdmin
{

    public function fans_api()
    {
        $fans = FansService::getFansJson(30);
        $data['fans'] = $fans;
        echo json_encode($data);
    }

    public function time_api()
    {
        $LoginLog = new LoginLog();
        $times = $LoginLog->echarts();
        echo json_encode($times);
    }
}

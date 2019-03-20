<?php

namespace app\admin\controller;

use controller\BasicAdmin;

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
        $fans = \app\wechat\service\FansService::getFansJson(30);
        $data['fans'] = $fans;
        echo json_encode($data);
    }

    public function time_api()
    {
        $LoginLog = new \app\motion\model\LoginLog();
        $times = $LoginLog->echarts();
        echo json_encode($times);
    }

    public function lesson_api()
    {
        $Lesson = new \app\motion\model\Lesson();
        $lesson = $Lesson->echarts();
        echo json_encode($lesson);
    }
}

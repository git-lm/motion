<?php

namespace app\admin\controller;

use controller\BasicAdmin;
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

    public function data_api()
    {
        $fans = FansService::getFansJson(30);
        $data['fans'] = $fans;
        echo json_encode($data);
    }

}

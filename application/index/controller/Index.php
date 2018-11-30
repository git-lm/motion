<?php

namespace app\index\controller;

use think\Controller;
use app\index\model\Lesson as lessonModel;

/**
 * 应用入口控制器
 * @author Anyon 
 */
class Index extends Controller {

    public function initialize() {
        session('motion_member.id', 3);
        if (!session('motion_user.id')) {
            $this->redirect('@login');
        }
        $this->lessonModel = new lessonModel();
    }

    /**
     *  首页
     */
    public function index() {
        //获取未到时间的记录
        
        return $this->fetch();
    }

}

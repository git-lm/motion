<?php
namespace app\blog\controller;

use controller\BasicAdmin;
use think\Db;

class Template extends BasicAdmin
{
    public function index()
    {
        $this->assign('title', '模板选择');
        return $this->fetch();
    }
}

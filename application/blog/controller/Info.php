<?php
namespace app\blog\controller;

use controller\BasicAdmin;
use think\Db;
use app\blog\model\InfoModel;

class Info extends BasicAdmin
{
    public function index()
    {
        $user = session('user');
        $this->assign('title', '基本信息');
        $info = InfoModel::where(array('user_id' => $user['id']))->with('coach')->find();
        $this->assign('info', $info);
        return $this->fetch();
    }
}

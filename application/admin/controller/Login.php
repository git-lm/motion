<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use service\LogService;
use service\NodeService;
use think\Db;
use think\facade\Validate;


/**
 * 系统登录控制器
 * class Login
 * @package app\admin\controller
 * @author Anyon 
 * @date 2017/02/10 13:59
 */
class Login extends BasicAdmin
{

    /**
     * 控制器基础方法
     */
    public function initialize()
    {
        //        if (session('user.id') && $this->request->action() !== 'out') {
        //            $this->redirect('@admin');
        //        }
    }

    /**
     * 用户登录
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        if ($this->request->isGet()) {
            return $this->fetch('', ['title' => '用户登录']);
        }
        // 输入数据效验
        $validate = Validate::make([
            'username' => 'require|min:4',
            'password' => 'require|min:4',
        ], [
            'username.require' => '登录账号不能为空！',
            'username.min'     => '登录账号长度不能少于4位有效字符！',
            'password.require' => '登录密码不能为空！',
            'password.min'     => '登录密码长度不能少于4位有效字符！',
        ]);
        $data = [
            'username' => $this->request->post('username', ''),
            'password' => $this->request->post('password', ''),
        ];
        $validate->check($data) || $this->error($validate->getError());
        // 用户信息验证
        $user = Db::name('SystemUser')->where(['username' => $data['username'], 'is_deleted' => '0'])->find();
        empty($user) && $this->error('登录账号不存在，请重新输入!');
        empty($user['status']) && $this->error('账号已经被禁用，请联系管理员!');
        $user['password'] !== md5($data['password']) && $this->error('登录密码错误，请重新输入!');
        $res = $this->checkIp($user);
        empty($res) && $this->error('IP限制，无权登录');
        // 更新登录信息
        Db::name('SystemUser')->where(['id' => $user['id']])->update([
            'login_at'  => Db::raw('now()'),
            'login_num' => Db::raw('login_num+1'),
        ]);
        session('user', $user);
        !empty($user['authorize']) && NodeService::applyAuthNode();
        LogService::write('系统管理', '用户登录系统成功');
        $this->success('登录成功，正在进入系统...', '@admin');
    }

    /**
     * 退出登录
     */
    public function out()
    {
        session('user') && LogService::write('系统管理', '用户退出系统成功');
        !empty($_SESSION) && $_SESSION = [];
        [session_unset(), session_destroy()];
        $this->success('退出登录成功！', '@admin/login');
    }

    public function checkIp($user = [])
    {
        if (!$user['is_ip']) {
            return true;
        }
        $ip = $this->request->ip();
        if (empty(sysconf('ip'))) {
            $allowed_ip = [];
        } else {
            $allowed_ip = explode(',', sysconf('ip'));
        }
        $check_ip_arr = explode('.', $ip); //要检测的ip拆分成数组  
        if (!in_array($ip, $allowed_ip)) {
            foreach ($allowed_ip as $val) {
                $arr = explode('.', $val);
                $bl = true; //用于记录循环检测中是否有匹配成功的 
                for ($i = 0; $i < 4; $i++) {
                    if ($arr[$i] != '*') { //不等于*  就要进来检测，如果为*符号替代符就不检查 
                        if ($arr[$i] != $check_ip_arr[$i]) {
                            $bl = false;
                            break; //终止检查本个ip 继续检查下一个ip 
                        }
                    }
                } //end for  
                if ($bl) { //如果是true则找到有一个匹配成功的就返回 
                    return true;
                }
            }
            return false;
        } else {
            return true;
        }
    }
}

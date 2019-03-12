<?php

namespace app\api\controller;

use think\Db;
use app\wechat\controller\api\Template;

/**
 * 到期消息提醒微信接口
 * Class Index
 * @package app\api\controller
 * @author Anyon 
 * @date 2018-12-10
 */
class ExpireWarn
{

    public function index()
    {
        if($_SERVER['REMOTE_ADDR'] != request()->ip()){
            echo '不是计划任务执行';
            echo request()->ip();
            return ;
        }
        //查询所有要到期的会员
        $expire_time = sysconf('wechat_expire_time') ? sysconf('wechat_expire_time') : 5;
        $wechat_expire_id = sysconf('wechat_expire_id');
        if (empty($wechat_expire_id)) {
            echo '无此模板信息';
            return;
        }
        $where[] = ['mt.end_time', '>', time()];
        $where[] = ['mt.status', '=', 1];
        $where[] = ['mi.is_wechat', '=', 1];
        $where[] = ['mt.end_time', '<', strtotime("+{$expire_time} day")];
        $where[] = ['f.openid', 'NOT NULL', ''];
        $times = Db::table('motion_member_time')
            ->alias('mt')
            ->field('mt.id ,mt.end_time ,  f.openid ,mi.m_id')
            ->where($where)
            ->leftJoin(['motion_member_info' => 'mi'], 'mi.m_id = mt.m_id')
            ->leftJoin(['wechat_fans' => ' f'], 'f.id = mi.f_id')
            ->select();
        foreach ($times as $time) {
            $data = array(
                'first' => array('value' => '时间到期提醒', 'color' => '#0000ff'),
                'name' => array('value' => '私教时间', 'color' => '#cc0000'),
                'expDate' => array('value' => date('Y-m-d', $time['end_time']), 'color' => '#cc0000'),
                'remark' => array('value' => '请注意时间，防止过期失效。', 'color' => '#cc0000'),
            );
            try {
                $touser = $time['openid'];
                $templateId = $wechat_expire_id;
                $url = '';
                Template::sendTemplateMessage($data, $touser, $templateId, $url);
                $logdata['byid'] = $time['id'];
                $logdata['data'] = json_encode($data);
                $logdata['openid'] = $time['openid'];
                $logdata['templateId'] = $wechat_expire_id;
                $logdata['create_at'] = time();
                $logdata['m_id'] = $time['m_id'];
                $logdata['error'] = '发送成功';
                $logdata['type'] = 1;
                Db::table('motion_template_log')->insertGetId($logdata);
            } catch (Exception $exc) {
                $logdata['byid'] = $time['id'];
                $logdata['data'] = json_encode($data);
                $logdata['openid'] = $time['openid'];
                $logdata['templateId'] = $wechat_expire_id;
                $logdata['create_at'] = time();
                $logdata['m_id'] = $time['m_id'];
                $logdata['error'] = $exc->getMessage();
                $logdata['type'] = 1;
                Db::table('motion_template_log')->insertGetId($logdata);
            }
        }
    }
}
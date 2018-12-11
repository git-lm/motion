<?php

namespace app\api\controller;

use controller\BasicAdmin;
use think\Db;
use app\wechat\controller\api\Template;

/**
 * 到期消息提醒微信接口
 * Class Index
 * @package app\api\controller
 * @author Anyon 
 * @date 2018-12-10
 */
class Expire extends BasicAdmin
{

    public function index()
    {
        //查询所有要到期的会员
        $expire_time = sysconf('wechat_expire_time');
        $wechat_expire_id = sysconf('wechat_expire_id');
        $where[] = ['mt.end_time', '>', time()];
        $where[] = ['mt.status', '=', 1];
        $where[] = ['mi.is_wechat', '=', 1];
        $where[] = ['mt.end_time', '<', strtotime("+{$expire_time} day")];
        $where[] = ['f.openid', 'NOT NULL', ''];
        $times = Db::table('motion_member_time')
                ->alias('mt')
                ->field('mt.id ,mt.end_time ,  f.openid')
                ->where($where)
                ->leftJoin(['motion_member_info' => 'mi'], 'mi.m_id = mt.m_id')
                ->leftJoin(['wechat_fans' => ' f'], 'f.id = mi.f_id')
                ->select();
        foreach ($times as $time)
        {
            $data = array(
                'first' => array('value' => '时间到期提醒', 'color' => '#0000ff'),
                'name' => array('value' => '私教时间', 'color' => '#cc0000'),
                'expDate' => array('value' => date('Y-m-d', $time['end_time']), 'color' => '#cc0000'),
                'remark' => array('value' => '请注意时间，防止过期失效。', 'color' => '#cc0000'),
            );
            try
            {
                $touser = $time['openid'];
                $templateId = $wechat_expire_id;
                $url = '';
                Template::sendTemplateMessage($data, $touser, $templateId, $url);
            } catch (Exception $exc)
            {
                $logdata['data'] = $data;
                $logdata['openid'] = $time['openid'];
                $logdata['templateId'] = $wechat_expire_id;
                $logdata['error'] = $exc->getMessage();
                Db::table('motion_member_time_log')->insertGetId($logdata);
            }
        }
    }

}

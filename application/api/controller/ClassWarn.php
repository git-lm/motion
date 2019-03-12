<?php

namespace app\api\controller;

use think\Db;
use app\wechat\controller\api\Template;

/**
 * 上课提醒
 * Class ClassWarn
 * @package app\api\controller
 * @author Anyon 
 * @date 2018-12-10
 */
class ClassWarn
{

    public function index()
    {
    	if($_SERVER['REMOTE_ADDR'] != request()->ip()){
    		echo '不是计划任务执行';
    		echo request()->ip();
    		return ;
    	}
        //查询所有要到期的会员
        $wechat_class_id = sysconf('wechat_class_id');
        if (empty($wechat_class_id)) {
            echo '无此模板信息';
            return;
        }
        $classes = Db::table('motion_lesson')
            ->alias('l')
            ->leftJoin('motion_member_info mi', 'mi.m_id = l.m_id')
            ->leftJoin('wechat_fans f', 'f.id = mi.f_id')
            ->leftJoin('motion_coach c', 'c.id = l.coach_id')
            ->field('f.openid, l.id, l.name , c.name cname ,l.m_id')
            ->where('to_days( FROM_UNIXTIME(l.class_time, "%Y-%m-%d")) = to_days(now())')
            ->where('f.openid is not null and l.status = 1 and l.state = 0')
            ->select();
        foreach ($classes as $class) {
            $data = array(
                'first' => array('value' => '今日未完成计划', 'color' => '#0000ff'),
                'keyword1' => array('value' => $class['name'], 'color' => '#cc0000'),
                'keyword2' => array('value' => empty($class['cname']) ? 'TreesCamp' : $class['cname'], 'color' => '#cc0000'),
                'keyword3' => array('value' => '馆中或家中', 'color' => '#0000ff'),
                'remark' => array('value' => '请按时按标准完成动作', 'color' => '#cc0000'),
            );
            try {
                $touser = $class['openid'];
                $templateId = $wechat_class_id;
                $url = '';
                Template::sendTemplateMessage($data, $touser, $templateId, $url);
                $logdata['byid'] = $class['id'];
                $logdata['data'] = json_encode($data);
                $logdata['openid'] = $class['openid'];
                $logdata['templateId'] = $wechat_class_id;
                $logdata['create_at'] = time();
                $logdata['m_id'] = $class['m_id'];
                $logdata['error'] = '发送成功';
                $logdata['type'] = 2;
                Db::table('motion_template_log')->insertGetId($logdata);
            } catch (Exception $exc) {
                $logdata['byid'] = $class['id'];
                $logdata['data'] = json_encode($data);
                $logdata['openid'] = $class['openid'];
                $logdata['templateId'] = $wechat_class_id;
                $logdata['create_at'] = time();
                $logdata['m_id'] = $class['m_id'];
                $logdata['error'] = $exc->getMessage();
                $logdata['type'] = 2;
                Db::table('motion_template_log')->insertGetId($logdata);
            }
        }
    }
}

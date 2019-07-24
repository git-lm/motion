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
        if ($_SERVER['REMOTE_ADDR'] != request()->ip()) {
            echo '不是计划任务执行';
            echo request()->ip();
            return;
        }
        //查询所有未完成计划
        $wechat_class_id = sysconf('wechat_class_id');
        if (empty($wechat_class_id)) {
            echo '无此模板信息';
            return;
        }
        $unfinished = Db::table('motion_lesson')
            ->alias('l')
            ->leftJoin('motion_member_info mi', 'mi.m_id = l.m_id')
            ->leftJoin('wechat_fans f', 'f.id = mi.f_id')
            ->leftJoin('motion_coach c', 'c.id = l.coach_id')
            ->field('f.openid, l.id, l.name , c.name cname ,l.m_id')
            ->where('to_days( FROM_UNIXTIME(l.class_time, "%Y-%m-%d")) = to_days(now())')
            ->where('f.openid is not null and l.status = 1 and l.state = 0')
            ->select();
        foreach ($unfinished as $class) {
            $data = array(
                'first' => array('value' => '今日未完成计划', 'color' => '#0000ff'),
                'keyword1' => array('value' => $class['name'], 'color' => '#cc0000'),
                'keyword2' => array('value' => empty($class['cname']) ? 'TreesCamp' : $class['cname'], 'color' => '#cc0000'),
                'keyword3' => array('value' => '馆中或家中', 'color' => '#0000ff'),
                'remark' => array('value' => '请按时按标准完成动作', 'color' => '#cc0000'),
            );
            $logdata['byid'] = $class['id'];
            $logdata['data'] = json_encode($data);
            $logdata['openid'] = $class['openid'];
            $logdata['templateId'] = $wechat_class_id;
            $logdata['create_at'] = time();
            $logdata['create_time'] = date('Y-m-d H:i:s');
            $logdata['m_id'] = $class['m_id'];
            $logdata['error'] = '发送失败';
            $logdata['type'] = 2;
            $logdata['source'] = 1;
            $log_id =  Db::table('motion_template_log')->insertGetId($logdata);
            try {
                $touser = $class['openid'];
                $templateId = $wechat_class_id;
                $url = url('/lesson/detile', ['id' => $class['id']], 'html', true);
                $res = Template::sendTemplateMessage($data, $touser, $templateId, $url);
                if ($res['errmsg'] == 'ok') {
                    Db::table('motion_template_log')->where(array('id' => $log_id))->update(array('return_info' => json_encode($res), 'status' => 1, 'error' => '发送成功'));
                }
                echo json_encode($logdata);
            } catch (Exception $exc) {
                echo json_encode($logdata);
            }
        }
        // //会员计划提前时间
        // $wechat_member_plan_time = !empty(sysconf('wechat_member_plan_time'))  ? sysconf('wechat_member_plan_time') : 30;
        // Db::table('motion_lesson')->where(array('is_send' => 0, 'status' => 1, 'state' => 0))->whereTime('class_time', '>=', time())->whereTime('class_time', '<=', strtotime("+{$wechat_member_plan_time} minute"))->select();
    }
}

<?php

namespace app\api\controller;

use think\Db;
use app\wechat\controller\api\Template;
use app\pt\model\ClassesModel;

/**
 * 教练日程提醒
 * Class ClassWarn
 * @package app\api\controller
 * @author Anyon 
 * @date 2018-12-10
 */
class ScheduleWarn
{

    public function index()
    {
        if ($_SERVER['REMOTE_ADDR'] != request()->ip()) {
            echo '不是计划任务执行';
            echo request()->ip();
            return;
        }
        $wechat_schedule_id = sysconf('wechat_schedule_id');                    //日程提醒模板
        $wechat_coach_class_id = sysconf('wechat_coach_class_id');              //课程提醒模板
        $wechat_coach_schedule_time = sysconf('wechat_coach_schedule_time');    //日程提醒时间
        $wechat_coach_pt_time = sysconf('wechat_coach_pt_time');                //私教提醒时间
        $wechat_coach_group_time = sysconf('wechat_coach_group_time');          //团课提醒时间

        $classModel = new ClassesModel();
        $classes =  $classModel->whereTime('begin_at', date('Y-m-d H:i:s'))->where(array('status' => 1))->select();

        foreach ($classes as $v) {
            $coach = $v['coach'];
            $member = $v['coach']['member'];
            $memberInfo = $v['coach']['member']['memberInfo'];
            $fans = $v['coach']['member']['memberInfo']['fans'];
            $openid = $v['coach']['member']['memberInfo']['fans']['openid'];
            $count = Db::table('motion_template_log')->where(array('byid' => $v['id'], 'status' => 1))->count();
            if ($count != 0) {
                continue;
            }
            $member = $v['coach'];
            if ($v['type'] == 1) {
                $templateId = $wechat_coach_class_id;
            } else if ($v['type'] == 2) {
                $templateId = $wechat_coach_class_id;
            } else if ($v['type'] == 3) {
                $templateId = $wechat_schedule_id;
            }
            if (empty($coach)) {
                Db::table('motion_template_log')
                    ->insert(array('coach_id' => $coach['id'], 'status' => 1, 'error' => '无此教练coach', 'create_at' => time(), 'byid' => $v['id'], 'templateId' => $templateId, 'type' => 4));
                continue;
            }
            if (empty($member)) {
                Db::table('motion_template_log')
                    ->insert(array('coach_id' => $coach['id'], 'status' => 1, 'error' => '无此教练member', 'create_at' => time(), 'byid' => $v['id'], 'templateId' => $templateId, 'type' => 4));
                continue;
            }
            if (empty($memberInfo)) {
                Db::table('motion_template_log')
                    ->insert(array('coach_id' => $coach['id'], 'status' => 1, 'error' => '无此教练memberInfo', 'create_at' => time(), 'byid' => $v['id'], 'templateId' => $templateId, 'type' => 4));
                continue;
            }
            if (empty($fans)) {
                Db::table('motion_template_log')
                    ->insert(array('coach_id' => $coach['id'], 'status' => 1, 'error' => '无此教练fans', 'create_at' => time(), 'byid' => $v['id'], 'templateId' => $templateId, 'type' => 4));
                continue;
            }
            if (empty($openid)) {
                Db::table('motion_template_log')
                    ->insert(array('coach_id' => $coach['id'], 'status' => 1, 'error' => '无此教练openid', 'create_at' => time(), 'byid' => $v['id'], 'templateId' => $templateId, 'type' => 4));
                continue;
            }
            $data['class_id'] = $v['id'];
            $data['coach_id'] = $coach['id'];
            if ($v['type'] == 1) {
                //私教课程
                if ($v['begin_at'] < date('Y-m-d H:i:s', strtotime("+ {$wechat_coach_pt_time} minute"))) {
                    $data['begin_at'] = $v['begin_at'];
                    $this->send($openid, $templateId, $data, 3);
                }
            } else if ($v['type'] == 2) {
                //团课课程
                if ($v['begin_at'] < date('Y-m-d H:i:s', strtotime("+ {$wechat_coach_group_time} minute"))) {
                    $course = $v['course'];
                    $data['begin_at'] = $v['begin_at'];
                    $data['course'] = !empty($course['name']) ? $course['name']  : "团课";
                    $this->send($openid, $templateId, $data, 4);
                }
            } else if ($v['type'] == 3) {
                //日程
                if ($v['begin_at'] < date('Y-m-d H:i:s', strtotime("+ {$wechat_coach_schedule_time} minute"))) {
                    $classesOther = $v['classesOther'];
                    $data['begin_at'] = $v['begin_at'];
                    $data['remark'] = !empty($classesOther['remark']) ? $classesOther['remark']  : "备注说明";
                    $this->send($openid, $templateId, $data, 5);
                }
            }
        }
    }


    public function send($touser, $templateId, $data,  $type, $url = '')
    {
        $template =  array(
            3 => array(
                'first' => array('value' => '私教课程提醒', 'color' => '#0000ff'),
                'keyword1' => array('value' => '私教课程', 'color' => '#cc0000'),
                'keyword2' => array('value' => $data['begin_at'], 'color' => '#cc0000'),
                'remark' => array('value' => '请尽快准备', 'color' => '#cc0000'),

            ),
            4 => array(
                'first' => array('value' => '团课课程提醒', 'color' => '#0000ff'),
                'keyword1' => array('value' => !empty($data['course']) ? $data['course'] : '', 'color' => '#cc0000'),
                'keyword2' => array('value' => $data['begin_at'], 'color' => '#cc0000'),
                'remark' => array('value' => '请尽快准备', 'color' => '#cc0000'),

            ),
            5 => array(
                'first' => array('value' => '日程提醒', 'color' => '#0000ff'),
                'keyword1' => array('value' => '个人日程提醒', 'color' => '#cc0000'),
                'keyword2' => array('value' => !empty($data['remark']) ? $data['remark'] : '', 'color' => '#cc0000'),
                'keyword3' => array('value' => $data['begin_at'], 'color' => '#cc0000'),
                'remark' => array('value' => '请尽快准备', 'color' => '#cc0000'),

            )
        );
        $url = url('index/classes/index', '', true, true);
        $logdata['byid'] = $data['class_id'];
        $logdata['data'] = json_encode($template[$type]);
        $logdata['openid'] = $touser;
        $logdata['templateId'] = $templateId;
        $logdata['create_at'] = time();
        $logdata['coach_id'] = $data['coach_id'];
        $logdata['error'] = '发送失败';
        $logdata['type'] = $type;
        try {
            $log_id = Db::table('motion_template_log')->insertGetId($logdata);
            $res =  Template::sendTemplateMessage($template[$type], $touser, $templateId, $url);
            if ($res['errmsg'] == 'ok') {
                Db::table('motion_template_log')->where(array('id' => $log_id))->update(array('status' => 1, 'error' => '发送成功'));
            }
            echo json_encode($logdata); //18151487535 
        } catch (Exception $exc) {
            echo json_encode($logdata);
        }
    }
}

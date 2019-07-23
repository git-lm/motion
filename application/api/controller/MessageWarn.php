<?php

namespace app\api\controller;

use think\Db;
use app\wechat\controller\api\Template;

/**
 * 发送消息提醒
 * Class MessageWarn
 * @package app\api\controller
 * @author Anyon 
 * @date 2018-12-10
 */
class MessageWarn
{
    private $templateId = 'IjlLMjJSlvrBVylvvVXU1thoB04hAOFTy0VPcFuMnrk';
    public $touser = '';
    public $msg = array();
    public $byid  = 0;
    public $m_id = 0;
    public function send()
    {
        if ($_SERVER['REMOTE_ADDR'] != request()->ip()) {
            echo '不是计划任务执行';
            echo request()->ip();
            return;
        }

        $data = array(
            'first' => array('value' => $this->msg['first'], 'color' => '#0000ff'),
            'keyword1' => array('value' => date('Y-m-d H:i:s'), 'color' => '#cc0000'),
            'keyword2' => array('value' => $this->msg['keyword2'], 'color' => '#cc0000'),
            'remark' => array('value' => $this->msg['remark'], 'color' => '#cc0000'),
        );
        dump($data);
        exit;
        try {
            $logdata['byid'] = $this->byid;
            $logdata['data'] = json_encode($data);
            $logdata['openid'] = $this->touser;
            $logdata['templateId'] = $this->templateId;
            $logdata['create_at'] = time();
            $logdata['m_id'] = $this->m_id;
            $logdata['error'] = '发送成功';
            $logdata['type'] = 3;
            $log_id = Db::table('motion_template_log')->insertGetId($logdata);
            $touser = $this->touser;
            $templateId = $this->templateId;
            $url = '';
            $res = Template::sendTemplateMessage($data, $touser, $templateId, $url);
            if ($res['errmsg'] == 'ok') {
                Db::table('motion_template_log')->where(array('id' => $log_id))->update(array('status' => 1, 'error' => '发送成功'));
            }
            echo json_encode($logdata);
        } catch (Exception $exc) {
            $logdata['byid'] = $this->byid;
            $logdata['data'] = json_encode($data);
            $logdata['openid'] = $this->touser;
            $logdata['templateId'] = $this->templateId;
            $logdata['create_at'] = time();
            $logdata['m_id'] = $this->m_id;
            $logdata['type'] = 3;
            Db::table('motion_template_log')->insertGetId($logdata);
            echo json_encode($logdata);
        }


        // ---------------------------------------------
        //获取会员信息
        $memberModel = new \app\motion\model\Member();
        $where[] = ['m.id', '=', $this->m_id];
        $member_info = $memberModel->get_member_info($where);
        //获取教练信息

        if (!empty($member_info['openid'])) {
            $message = new \app\api\controller\MessageWarn();
            $message->touser = $member_info['openid'];
            $message->byid = $id;
            $message->m_id = $this->m_id;
            $message->msg = ['remark' => $messsage . ' ！请及时回复', 'keyword2' => $member_info['mname'], 'first' => '会员留言通知'];
            $message->send();
        }
        dump($member_info['openid']);
        exit;
    }
}

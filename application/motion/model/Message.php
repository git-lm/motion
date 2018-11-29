<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Message extends Model {

    //动作库表
    protected $table = 'motion_message';

    /**
     * 时间获取器
     * @param type $val  转换数据
     * @param type $type 转换类型  d 天   h 小时  m 分钟  s秒
     */
    protected function getDateAttr($val, $type = 'd') {
        if ($type == 'd') {
            return date('Y-m-d', $val);
        } else if ($type == 'h') {
            return date('Y-m-d H', $val);
        } else if ($type == 'm') {
            return date('Y-m-d H:i', $val);
        } else if ($type == 's') {
            return date('Y-m-d H:i:s', $val);
        } else {
            return date('Y-m-d', $val);
        }
    }

    /**
     * 状态获取器
     * @param type $val  转换数据
     */
    protected function getStatusAttr($val) {
        if ($val == 1) {
            return '正常';
        } else if ($val == 0) {
            return '删除';
        } else if ($val == -1) {
            return '禁用';
        } else {
            return '异常';
        }
    }

    /**
     * 获取动作库
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_messages($where = [], $order = [], $page = 0, $limit = 0) {
        $db = Db::table($this->table)
                ->alias('m')
                ->field('m.* , c.name cname , mm.name mname')
                ->leftJoin(['motion_coach' => 'c'], 'c.id = m.c_id')
                ->leftJoin(['motion_member' => 'mm'], 'mm.id = m.m_id');

        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            if (!empty($list['cname'])) {
                $list['sendname'] = $list['cname'];
            } else {
                $list['sendname'] = $list['mname'];
            }
        }
        return $lists;
    }

    /**
     * 获取单个动作库
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_message($where = [], $order = []) {
        $list = DbService::queryOne($this->table, $where, $order);

        return $list;
    }

    /**
     * 新增留言
     * @param type $data 保存的数据
     */
    public function add($data = []) {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '回复留言');
        $code = DbService::save($this->table, $data);
        return $code;
    }

    /**
     * 编辑信息
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = []) {
        $member = $this->get_messages($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($member), json_encode($data), json_encode($where), '编辑会员');
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 验证留言数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data) {
        $rule = [
            'content' => 'require|max:1000',
        ];
        $message = [
            'content.require' => '内容必填',
            'content.max' => '内容不多于一千字',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

}

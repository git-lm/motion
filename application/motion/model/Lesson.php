<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Lesson extends Model {

    protected $table = 'motion_lesson';

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
     * 状态获取器
     * @param type $val  转换数据
     */
    protected function getStateAttr($val) {
        if ($val == 1) {
            return '已完成';
        } else if ($val == 0) {
            return '未完成';
        } else {
            return '异常';
        }
    }

    /**
     * 获取排课列表
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_arrange_lists($where = [], $order = [], $page = 0, $limit = 0) {
        $db = Db::table($this->table);
        $db->alias('p');
        $db->leftJoin('motion_member m', 'p.m_id=m.id');
        $db->leftJoin('motion_course c', 'p.c_id=c.id');
        $db->leftJoin('motion_coach coach', 'p.coach_id=coach.id');
        $db->field(['p.*', 'm.name' => 'mname', 'c.name' => 'cname', 'coach.name' => 'coach_name']);
        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['class_time_show'] = $this->getDateAttr($list['class_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
            $list['state_show'] = $this->getStateAttr($list['state']);
        }
        return $lists;
    }

    /**
     * 获取单个课程
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_arrange_list($where = [], $order = []) {
        $list = DbService::queryOne($this->table, $where, $order);
        return $list;
    }

    /**
     * 新增会员动作
     * @param type $data 保存的数据
     */
    public function add($data = []) {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        if (empty($data['password'])) {
            $data['password'] = md5('123456');
        }
        $code = DbService::save($this->table, $data);
        return $code;
    }

    /**
     * 编辑会员动作
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = []) {
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }

        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data) {
        $rule = [
            'class_time' => 'require|date',
        ];
        $message = [
            'class_time.require' => '上课时间必填',
            'class_time.date' => '请正确选择时间',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

}

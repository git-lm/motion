<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Motion extends Model {

    //动作库表
    protected $table = 'motion_bank';

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
    public function get_motions($where = [], $order = [], $page = 0, $limit = 0) {
        $db = Db::table($this->table);
        $db->alias('mb');
        $buildSql = Db::table('motion_type')->field('id mtid, name mtname')->where('status', '=', 1)->buildSql();
        $db->leftJoin([$buildSql => 'mt'], 'mt.mtid = mb.tid');

        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
            if (empty($list['mtname'])) {
                $list['mtname'] = '异常状态';
            }
        }
        return $lists;
    }

    /**
     * 获取单个动作库
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_motion($where = [], $order = []) {
        $list = DbService::queryOne($this->table, $where, $order);

        return $list;
    }

    /**
     * 新增动作库
     * @param type $data 保存的数据
     */
    public function add($data = []) {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增动作库');
        $code = DbService::save($this->table, $data);
        return $code;
    }

    /**
     * 编辑动作库
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = []) {
        $motion = $this->get_motion($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($motion), json_encode($data), json_encode($where), '编辑动作库');
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data) {
        $rule = [
            'tid' => 'require',
            'name' => 'require|max:10|min:2',
            'url' => 'require|url',
        ];
        $message = [
            'tid.require' => '所属类型必选',
            'name.require' => '类型名称必填',
            'name.max' => '类型名称最多不超过十个字',
            'name.min' => '类型名称最少不小于两个字',
            'url.require' => '视频地址必填',
            'url.url' => '请正确填写视频地址',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

}

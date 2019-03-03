<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

/**
 * 动作库模型
 */
class MotionType extends Model
{

    //动作库类型数据库
    protected $table = 'motion_type';

    /**
     * 时间获取器
     * @param type $val  转换数据
     * @param type $type 转换类型  d 天   h 小时  m 分钟  s秒
     */
    protected function getDateAttr($val, $type = 'd')
    {
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
    protected function getStatusAttr($val)
    {
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
     * 获取动作库类型
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_motion_types($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL($this->table, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
        }
        return $lists;
    }

    /**
     * 获取动作库类型
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_motion_type($where = [], $order = [])
    {
        $list = DbService::queryOne($this->table, $where, $order);

        return $list;
    }

    /**
     *  按级别获取数据
     * @param Array $data   待处理数据
     * @param type $level   获取几级数据
     */
    public function get_level_types($data, $pid = 0, $level = 0, $num = 2)
    {
        static $lists = array();
        foreach ($data as $key => $val) {
            if ($val['parent_id'] == $pid) { //&& $level < $num
                $level++;
                $lists[] = $val;
                unset($data[$key]);
                $this->get_level_types($data, $val['id'], $level);
            }
        }
        return $lists;
    }

    /**
     * 新增类型
     * @param type $data 保存的数据
     */
    public function add($data = [])
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增动作类型', $this->table);
        $code = DbService::save($this->table, $data);
        return $code;
    }

    /**
     * 编辑类型
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = [])
    {
        $type = $this->get_motion_type($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($type), json_encode($data), json_encode($where), '编辑动作类型', $this->table);
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'parent_id' => 'require',
            'name' => 'require|max:50|min:2',
        ];
        $message = [
            'parent_id.require' => '所属类型必选',
            'name.require' => '类型名称必填',
            'name.max' => '类型名称最多不超过五十个字',
            'name.min' => '类型名称最少不小于两个字',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

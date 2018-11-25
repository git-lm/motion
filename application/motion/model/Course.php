<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Course extends Model {

    protected $table = 'motion_course';

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
     * 获取课程列表
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_courses($where = [], $order = [], $page = 0, $limit = 0) {
        $db = Db::table($this->table);
        $db->alias('c');
        $buildSql = Db::table('motion_type')->field('id tid, name tname')->where('status', '=', 1)->buildSql();
        $db->leftJoin([$buildSql => 't'], 't.tid = c.t_id');
        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
        }
        return $lists;
    }

    /**
     * 获取单个课程
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_course($where = [], $order = []) {
        $list = DbService::queryOne($this->table, $where, $order);
        return $list;
    }

    /**
     * 新增课程
     * @param type $data 保存的数据
     */
    public function add($data = []) {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        if (empty($data['password'])) {
            $data['password'] = md5('123456');
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增课程');
        $code = DbService::save($this->table, $data);
        return $code;
    }

    /**
     * 编辑课程
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = []) {
        $course = $this->get_course($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($course), json_encode($data), json_encode($where), '编辑课程');
        DbService::save_log('motion_log', '', json_encode($data), '', '新增课程');
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     *  实现菜单有序排列
     * @staticvar array $menus  静态返回数组 防止覆盖
     * @param type $menu         要排序的数组
     * @param type $id            父类ID
     * @param type $level       分类级别
     * @return string、         返回排序好的数组
     */
    public function get_level_courses($course, $pid = 0, $level = 0) {
        //静态数组
        static $courses = array();
        //循环菜单
        foreach ($course as $key => &$value) {
            //如果是父类的子节点则继续  如果不是则等待下次循环
            if ($value['pid'] == $pid) {
                //给这个子节点加上标识
                $value['level'] = $level + 1;
                //加上页面标识  方便查看
                if ($level == 0) {
                    $value['str'] = '';
                } else if ($level == 1) {
                    $value['str'] = '|--';
                } else if ($level == 2) {
                    $value['str'] = '|--|-- ';
                } else if ($level == 3) {
                    $value['str'] = '|--|--|-- ';
                } else {
                    $value['str'] = '|??';
                }
                //删除已处理的数据  
                unset($course[$key]);
                //把子节点放入数组中
                $courses[] = $value;
                //继续调用
                $this->get_level_courses($course, $value['id'], $value['level']);
            }
        }
        return $courses;
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data) {
        $rule = [
            'name' => 'require|max:10|min:2',
            'warmup' => 'require|max:1000',
            'colldown' => 'require|max:1000',
            't_id' => 'require|number'
        ];
        $message = [
            'name.require' => '会员名称必填',
            'name.min' => '会员名称最少两个字',
            'name.max' => '会员名称最多十个字',
            'warmup.require' => '热身语必填',
            'warmup.max' => '热身语最多不超过一千个字',
            'colldown.require' => '结束语必填',
            'colldown.max' => '结束语最多不超过一千个字',
            't_id.require' => '请选择类型',
            't_id.number' => '请正确选择类型',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

}

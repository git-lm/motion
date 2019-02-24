<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Coach extends Model
{

    protected $table = 'motion_coach';

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
     * 获取教练列表
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_coachs($where = [], $order = [], $page = 0, $limit = 0)
    {
        $db = Db::table($this->table);
        $db->alias('c');
        $db->leftJoin(['system_user' => 'u'], 'u.id=c.u_id');
        $db->field('c.* , u.id uid , u.username');
        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
        }
        return $lists;
    }

    /**
     * 获取单个教练
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_coach($where = [], $order = [])
    {
        $list = DbService::queryOne($this->table, $where, $order);
        return $list;
    }


    /**
     * 获取教练和会员的关系
     */
    public function get_coachs_for_member($m_id  = 0)
    {

        $db = Db::table($this->table);
        $db->alias('c');
        $db->leftJoin(['motion_member' => 'm'], 'm.coach_id=c.id');
        $db->field('c.* , m.id mid');
        $db->where('c.status', 1);
        $db->where('m.id', 'null');
        if ($m_id) {
            $db->whereOr('m.id', $m_id);
        }
        $lists = DbService::queryALL($db);
        return $lists;
    }

    /**
     * 新增教练
     * @param type $data 保存的数据
     */
    public function add($data = [])
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        if (empty($data['password'])) {
            $data['password'] = md5('123456');
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增教练');
        $code = DbService::save($this->table, $data);

        return $code;
    }

    /**
     * 编辑教练
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = [])
    {
        $coach = $this->get_coach($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($coach), json_encode($data), json_encode($where), '编辑教练');
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 获取未授权的账号  和  已选择的账户
     * @param int $uid 账户ID
     * 
     */
    public function get_user($uid = 0)
    {

        $db = Db::table('system_user');
        $db->alias('u');
        $db->leftJoin(['motion_coach' => 'c'], 'c.u_id = u.id');
        $db->field('u.id uid ,u.username');
        //        $db->whereNull('c.id', ' is null');
        //        $db->whereOr('u.id', '=', $uid);
        //        $db->where('is_admin', '<>', 1);
        $db->where("(c.id is null or u.id = {$uid}) and u.is_admin <> 1");
        $lists = DbService::queryALL($db);
        return $lists;
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'name' => 'require|max:5|min:2|chsAlpha',
            'phone' => 'require|mobile'
        ];
        $message = [
            'name.require' => '会员名称必填',
            'name.min' => '会员名称最少两个字',
            'name.max' => '会员名称最多五个字',
            'name.chsAlpha' => '会员名称只能汉子和字母',
            'phone.require' => '手机号码必填',
            'phone.mobile' => '手机号码格式不正确',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

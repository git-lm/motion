<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Member extends Model
{

    protected $table = 'motion_member_info';

    /**
     * 时间获取器
     * @param type $val  转换数据
     * @param type $type 转换类型  d 天   h 小时  m 分钟  s秒
     */
    protected function getDateAttr($val, $type = 'd')
    {
        if ($type == 'd')
        {
            return date('Y-m-d', $val);
        } else if ($type == 'h')
        {
            return date('Y-m-d H', $val);
        } else if ($type == 'm')
        {
            return date('Y-m-d H:i', $val);
        } else if ($type == 's')
        {
            return date('Y-m-d H:i:s', $val);
        } else
        {
            return date('Y-m-d', $val);
        }
    }

    /**
     * 状态获取器
     * @param type $val  转换数据
     */
    protected function getStatusAttr($val)
    {
        if ($val == 1)
        {
            return '正常';
        } else if ($val == 0)
        {
            return '删除';
        } else if ($val == -1)
        {
            return '禁用';
        } else
        {
            return '异常';
        }
    }

    /**
     * 性别获取器
     * @param type $val  转换数据
     */
    public function getSexAttr($val)
    {
        if ($val == 1)
        {
            return '男';
        } else if ($val == 2)
        {
            return '女';
        } else
        {
            return '异常';
        }
    }

    /**
     * 获取会员列表
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_members_info($where = [], $order = [], $page = 0, $limit = 0)
    {
        $db = Db::table($this->table)
                ->alias('m')
                ->field('m.* ,c.name cname ,t.end_time')
                ->leftJoin(['motion_coach' => 'c'], 'c.id = m.c_id');
        $buildSql = Db::table('motion_member_time')->field(['MAX(end_time)' => 'end_time', 'm_id'])->where('status', '=', 1)->group('m_id')->buildSql();
        $db->leftJoin([$buildSql => 't'], 't.m_id = m.id');

        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list)
        {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
            $list['sex_show'] = $this->getSexAttr($list['sex']);
            if (empty($list['end_time']))
            {
                $list['end_time_show'] = '未开通';
            } else
            {
                $list['end_time_show'] = $this->getDateAttr($list['end_time']);
            }
            $list['sex_show'] = $this->getSexAttr($list['sex']);
            if (empty($list['cname']))
            {
                $list['cname'] = '无教练';
            }
        }
        return $lists;
    }

    /**
     * 新增会员
     * @param type $data 保存的数据
     */
    public function add($data = [])
    {
        if (empty($data['create_time']))
        {
            $data['create_time'] = time();
        }
        if (empty($data['password']))
        {
            $data['password'] = md5('123456');
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增会员');
        $code = DbService::save($this->table, $data);
        return $code;
    }

    /**
     * 编辑会员
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = [])
    {
        $member = $this->get_member($where);
        if (empty($data['update_time']))
        {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($member), json_encode($data), json_encode($where), '编辑会员');
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 获取会员绑定教练信息
     * @param array $where  查询条件
     */
    public function get_member_coach($where = array())
    {
        $list = DbService::queryOne('motion_member_coach', $where);
        return $list;
    }

    /**
     * 新增会员绑定教练
     * @param type $data 保存的数据
     */
    public function add_member_coach($data = [])
    {
        if (empty($data['create_time']))
        {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增会员绑定教练');
        $code = DbService::save('member_coach', $data);
        return $code;
    }

    /**
     * 更新会员绑定教练信息
     * @param array $where  更新条件
     * @param array $data   更新数据
     */
    public function edit_member_coach($data = [], $where = [])
    {
        $member_coach = $this->get_member_coach($where);
        if (empty($data['update_time']))
        {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($member_coach), json_encode($data), json_encode($where), '更新会员绑定教练信息');
        $code = DbService::update('motion_member_coach', $data, $where);
        return $code;
    }

    /**
     * 分配会员给教练
     * @param type $mid     会员ID
     * @param type $c_id    教练ID
     */
    public function dis($mid, $c_id)
    {
        Db::startTrans();
        try
        {
            $mcwhere['m_id'] = $mid;
            $mcwhere['status'] = 1;
            $member_coach = $this->get_member_coach($mcwhere);

            //存在教练ID  绑定会员教练  更新会员教练信息
            //如果之前绑定了 则先解除绑定  更新修改事件 在添加绑定信息
            if (!empty($member_coach))
            {
                $emcdata['status'] = 0;
                $emcwhere['id'] = $member_coach['id'];
                $this->edit_member_coach($emcdata, $emcwhere);
            }
            if ($c_id)
            {
                $amcdata['c_id'] = $c_id;
                $amcdata['m_id'] = $mid;
                $this->add_member_coach($amcdata);
            }
            $mdata['c_id'] = $c_id;
            $mwhere['id'] = $mid;
            $this->edit($mdata, $mwhere);
            // 提交事务
            Db::commit();
            return 1;
        } catch (\Exception $e)
        {
            // 回滚事务
            Db::rollback();
            return 0;
        }
    }

    /**
     * 添加会员时间
     * @param Array $data   要添加的数据
     */
    public function time_add($data)
    {
        if (empty($data['create_time']))
        {
            $data['create_time'] = time();
        }
        if (empty($data['u_id']))
        {
            $data['u_id'] = session('user.id');
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '添加会员时间');
        $code = DbService::save('motion_member_time', $data);
        return $code;
    }

    public function get_max_time($where = [], $order = [])
    {
        $db = Db::table('motion_member_time')
                ->field(['MAX(end_time)' => 'end_time', 'm_id'])
                ->group('m_id');
        $list = DbService::queryOne($db, $where, $order);
        return $list;
    }

    /**
     * 获取会员列表
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_member_times($where = [], $order = [], $page = 0, $limit = 0)
    {
        $db = Db::table('motion_member_time')
                ->alias('mt')
                ->field('mt.* , u.username')
                ->leftJoin(['system_user' => 'u'], 'u.id = mt.u_id');

        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list)
        {
            $list['begin_time_show'] = $this->getDateAttr($list['begin_time']);
            $list['end_time_show'] = $this->getDateAttr($list['end_time']);
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $lists;
    }

    /**
     * 获取会员时间列表
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_member_time($where = [], $order = [])
    {
        $db = Db::table('motion_member_time')
                ->alias('mt')
                ->field('mt.* , u.username')
                ->leftJoin(['system_user' => 'u'], 'u.id = mt.u_id');

        $list = DbService::queryOne($db, $where, $order);
        if (!empty($list))
        {
            $list['begin_time_show'] = $this->getDateAttr($list['begin_time']);
            $list['end_time_show'] = $this->getDateAttr($list['end_time']);
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $list;
    }

    public function time_edit($data = [], $where = [])
    {
        $member_time = $this->get_member_time($where);
        if (empty($data['update_time']))
        {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($member_time), json_encode($data), json_encode($where), '编辑会员时间');
        $code = DbService::update('motion_member_time mt', $data, $where);
        return $code;
    }

  

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'name' => 'require|max:5|min:2|chsAlpha',
            'phone' => 'require|mobile',
            'age' => 'between:1,120|number'
        ];
        $message = [
            'name.require' => '会员名称必填',
            'name.min' => '会员名称最少两个字',
            'name.max' => '会员名称最多五个字',
            'name.chsAlpha' => '会员名称只能汉子和字母',
            'phone.require' => '手机号码必填',
            'phone.mobile' => '手机号码格式不正确',
            'age.between' => '年龄在1-120之间',
            'age.number' => '正确填写年龄',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

}

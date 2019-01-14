<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Member extends Model
{

    protected $table = 'motion_member';

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
    public function get_members($where = [], $order = [], $page = 0, $limit = 0)
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
            if (empty($list['end_time']))
            {
                $list['end_time_show'] = '未开通';
            } else
            {
                $list['end_time_show'] = $this->getDateAttr($list['end_time']);
            }
            if (empty($list['cname']))
            {
                $list['cname'] = '无教练';
            }
        }
        return $lists;
    }

    /**
     * 获取单个会员
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_member($where = [], $order = [])
    {
        $list = DbService::queryOne($this->table, $where, $order);
        return $list;
    }

    /**
     * 获取单个会员信息
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_member_info($where = [], $order = [])
    {
        $db = Db::table('motion_member_info')
                ->alias('mi')
                ->leftJoin(['motion_member' => 'm'], 'm.id = mi.m_id')
                ->leftJoin(['wechat_fans' => 'f'], 'f.id = mi.f_id')
                ->field('mi.* , m.status mstatus , f.openid , f.nickname , f.sex fsex , f.country , f.province , f.city ,f.headimgurl');
        $list = DbService::queryOne($db, $where, $order);
        return $list;
    }

    /**
     * 编辑或者更新会员信息
     */
    public function info($data, $m_id)
    {
        if (!empty($data['sex_show']))
        {
            unset($data['sex_show']);
        }
        $where[] = ['m.id', '=', $m_id];
        $list = $this->get_member_info($where);
        if (empty($list))
        {
            $data['m_id'] = $m_id;
            $code = $this->add_info($data);
        } else
        {
            $iwhere [] = ['m_id', '=', $m_id];
            $code = $this->edit_info($data, $iwhere);
        }
        return $code;
    }

    /**
     * 新增会员信息
     * @param type $data 保存的数据
     */
    public function add_info($data = [])
    {
        if (empty($data['create_time']))
        {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增会员信息');
        $code = DbService::save('motion_member_info', $data);
        return $code;
    }

    /**
     * 编辑会员
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit_info($data = [], $where = [])
    {
        $member = $this->get_member_info($where);
        if (empty($data['update_time']))
        {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($member), json_encode($data), json_encode($where), '编辑会员信息');
        $code = DbService::update('motion_member_info', $data, $where);
        return $code;
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
        $code = DbService::save('motion_member_coach', $data);
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
     * 获取会员照片
     * @param type $where
     * @param type $order
     * @return type
     */
    public function get_member_photos($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL('motion_member_photo', $where, $order, $page, $limit);
        foreach ($lists as &$list)
        {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $lists;
    }

    /**
     * 获取会员照片
     * @param type $where
     * @param type $order
     * @return type
     */
    public function get_member_photo($where = [], $order = [])
    {
        $list = DbService::queryOne('motion_member_photo', $where, $order);
        if (!empty($list))
        {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $list;
    }

    /**
     * 上传照片
     * @param type $data
     */
    public function photo_check_add($data)
    {
        //判断是否存未完成的
        $notwhere [] = ['front_photo|back_photo|side_photo', 'null', ''];
        $notphoto = $this->get_member_photo($notwhere);
        //如果存在  则更新未完成的
        if ($notphoto)
        {
            $where[] = ['id', '=', $notphoto['id']];
            $data[$data['name'] . '_photo'] = $data['photo'];
            $data['update_time'] = time();
            unset($data['name']);
            unset($data['photo']);
            $code = DbService::update('motion_member_photo', $data, $where);
            DbService::save_log('motion_log', '', json_encode($data), '', '修改会员照片');
        } else //不存在 则新增一条
        {
            $data['create_time'] = time();
            $data[$data['name'] . '_photo'] = $data['photo'];
            unset($data['name']);
            unset($data['photo']);
            $code = DbService::save('motion_member_photo', $data);
            DbService::save_log('motion_log', '', json_encode($data), '', '新增会员照片');
        }

        return $code;
    }

     /**
     * 获取会员运动记录
     * @param type $where
     * @param type $order
     * @return type
     */
    public function get_member_datas($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL('motion_member_data', $where, $order, $page, $limit);
        foreach ($lists as &$list)
        {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $lists;
    }

    /**
     * 获取会员运动记录
     * @param type $where
     * @param type $order
     * @return type
     */
    public function get_member_data($where = [], $order = [])
    {
        $list = DbService::queryOne('motion_member_data', $where, $order);
        if (!empty($list))
        {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $list;
    }
    /**
     * 编辑会员运动数据记录
     */
    public function data_edit($data = [], $where = [])
    {
        $member_data = $this->get_member_data($where);
        if (empty($data['update_time']))
        {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($member_data), json_encode($data), json_encode($where), '编辑会员运动记录');
        $code = DbService::update('motion_member_data', $data, $where);
        return $code;
    }

    /**
     * 保存运动数据
     * @param Array $data   要添加的数据
     */
    public function data_add($data)
    {
        if (empty($data['create_time']))
        {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '添加会员运动记录');
        $code = DbService::save('motion_member_data', $data);
        return $code;
    }
    /**
     * 获取会员运动记录详情
     * @param type $where
     * @param type $order
     * @return type
     */
    public function get_member_data_info($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL('motion_member_data_info', $where, $order, $page, $limit);
        return $lists;
    }
    /**
     * 编辑会员运动数据记录详情
     */
    public function data_info_edit($data = [], $where = [])
    {
        $member_data_info = $this->get_member_data_info($where);
        DbService::save_log('motion_log', json_encode($member_data_info), json_encode($data), json_encode($where), '编辑会员运动记录详情');
        $code = DbService::update('motion_member_data_info', $data, $where);
        return $code;
    }
  
    /**
     * 保存运动数据详情
     * @param Array $data   要添加的数据
     */
    public function data_info_add($data)
    {
        DbService::save_log('motion_log', '', json_encode($data), '', '添加会员运动记录详情');
        $code = DbService::save('motion_member_data_info', $data);
        return $code;
    }



    /**
     * 写入操作日志
     * @param string $action
     * @param string $content
     * @return bool
     */
    public static function write($action, $content, $member_name, $member_id)
    {
        $node = strtolower(join('/', [request()->module(), request()->controller(), request()->action()]));
        $data = [
            'ip' => request()->ip(),
            'node' => $node,
            'action' => $action,
            'content' => $content,
            'username' => $member_name . '',
            'm_id' => $member_id,
            'ageni' => $_SERVER['HTTP_USER_AGENT'],
            'create_time' => time(),
            'is_mobile' => request()->isMobile()
        ];
        $code = DbService::save('motion_login_log', $data);
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

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate_info($data)
    {
        $rule = [
            'height' => 'max:10',
            'weight' => 'max:10',
            'age' => 'between:10,80',
            'first_name' => 'max:50',
            'last_name' => 'max:50',
            'email' => 'email',
            'wechat' => 'max:50',
            'location' => 'max:50',
            'birthday' => 'max:50',
        ];
        $message = [
            'height.max' => '身高字数不超过十个',
            'weight.max' => '体重字数不超过十个',
            'age.between' => '年龄在十到一百之间',
            'first_name.max' => '姓名字数不超过五十个字',
            'last_name.max' => '曾用名字数不超过五十个字',
            'email.email' => '邮箱格式不正确',
            'wechat.max' => '微信字数不超过五十个字',
            'location.max' => '地址字数不超过五十个字',
            'birthday.max' => '生日字数不超过五十个字',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

}

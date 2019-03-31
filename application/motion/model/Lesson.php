<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Lesson extends Model
{

    protected $table = 'motion_lesson';

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
     * 状态获取器
     * @param type $val  转换数据
     */
    protected function getStateAttr($val)
    {
        if ($val == 1) {
            return '已完成';
        } else if ($val == 0) {
            return '未完成';
        } else {
            return '异常';
        }
    }
    /**
     * 获取周几
     */
    public function getWeek($val)
    {
        $time = date("w", $val);
        $array = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];
        $week = $array();
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
    public function get_arrange_lists($where = [], $order = [], $page = 0, $limit = 0)
    {
        $db = Db::table($this->table);
        $db->alias('l');
        $db->leftJoin('motion_member m', 'l.m_id=m.id');
        $db->leftJoin('motion_coach coach', 'l.coach_id=coach.id');
        $db->field(['l.*', 'm.name' => 'mname', 'coach.name' => 'coach_name', 'ifnull(t.count ,0)' => 'count']);
        $buildSql = Db::table('motion_message')->field(['count(0)' => 'count', 'p_id'])->where('is_check', '=', 0)->group('p_id')->buildSql();
        $db->leftJoin([$buildSql => 't'], 't.p_id = l.id');
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
    public function get_arrange_list($where = [], $order = [])
    {
        $list = DbService::queryOne($this->table, $where, $order);
        return $list;
    }

    /**
     * 新增会员动作
     * @param type $data 保存的数据
     */
    public function add($data = [], $limit = false)
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增会员动作', $this->table);
        $code = DbService::save($this->table, $data, $limit);
        return $code;
    }
    /**
     * 新增批量动作
     * @param type $data 保存的数据
     */
    public function add_batch_lesson($data = [])
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增批量动作', 'motion_batch_lesson');
        $code = DbService::save('motion_batch_lesson', $data);
        return $code;
    }

    /**
     * 编辑批量计划
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit_batch_lesson($data = [], $where = [])
    {
        $batch_lesson = $this->get_batch_lesson($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($batch_lesson), json_encode($data), json_encode($where), '编辑批量计划', 'motion_batch_lesson');
        $code = DbService::update('motion_batch_lesson', $data, $where);
        return $code;
    }

    /**
     * 编辑会员动作
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function edit($data = [], $where = [])
    {
        $lesson = $this->get_arrange_list($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($lesson), json_encode($data), json_encode($where), '编辑会员动作', $this->table);
        $code = DbService::update($this->table, $data, $where);
        return $code;
    }

    /**
     * 查看小动作
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_little_courses($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL('motion_lesson_course', $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
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
    public function get_little_course($where = [], $order = [])
    {
        $list = DbService::queryOne('motion_lesson_course', $where, $order);
        return $list;
    }
    /**
     * 查看批量计划小动作
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_batch_littles($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL('motion_batch_lesson_course', $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
        }
        return $lists;
    }

    /**
     * 获取单个批量假话
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     */
    public function get_batch_little($where = [], $order = [])
    {
        $list = DbService::queryOne('motion_batch_lesson_course', $where, $order);
        return $list;
    }
    /**
     * 新增批量计划详情
     * @param type $data 保存的数据
     */
    public function batch_little_add($data = [])
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增批量计划详情', 'motion_batch_lesson_course');
        $code = DbService::save('motion_batch_lesson_course', $data);
        return $code;
    }

    /**
     * 编辑批量计划详情
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function batch_little_edit($data = [], $where = [])
    {
        $little = $this->get_little_course($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($little), json_encode($data), json_encode($where), '编辑批量计划详情', 'motion_batch_lesson_course');
        $code = DbService::update('motion_batch_lesson_course', $data, $where);
        return $code;
    }

    /**
     * 新增会员小动作
     * @param type $data 保存的数据
     */
    public function little_add($data = [])
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增会员小动作', 'motion_lesson_course');
        $code = DbService::save('motion_lesson_course', $data);
        return $code;
    }

    /**
     * 编辑会员小动作
     * @param type $data    保存的数据
     * @param type $where   编辑条件
     */
    public function little_edit($data = [], $where = [])
    {
        $little = $this->get_little_course($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($little), json_encode($data), json_encode($where), '编辑会员小动作', 'motion_lesson_course');
        $code = DbService::update('motion_lesson_course', $data, $where);
        return $code;
    }

    /**
     * 添加课程视频
     */
    public function file_add($data = [])
    {
        if (empty($data['create_time'])) {
            $data['create_time'] = time();
        }
        DbService::save_log('motion_log', '', json_encode($data), '', '新增会员课程记录文件', 'motion_lesson_course_file');
        $code = DbService::save('motion_lesson_course_file', $data);
        return $code;
    }

    /**
     *  获取相同的课程
     */
    public function get_history($where = 0,  $order = [], $page = 0, $limit = 0)
    {
        //获取相同视频的课程
        $whereArr[] = ['lc.state', '=', 1];
        $whereArr[] = ['l.class_time', '<', strtotime('+1 day', time())];
        $whereArr[] = ['m_id', '=', $where['m_id']];

        if (!empty($where['m_ids']) && !empty($where['name'])) {
            $m_ids =  $where['m_ids'];
            $name =  $where['name'];
            // $whereArr[] = [['m_ids', 'in', $where['m_ids']], ['lc.name', 'like', $where['name']]];
            $whereArr[] = function ($query) use ($name, $m_ids) {
                $query->where('m_ids', 'in', $m_ids)
                    ->whereOr('lc.name', 'like', '%' . $name . '%');
            };
        } else {
            if (!empty($where['m_ids'])) {
                $whereArr[] = ['m_ids', 'in', $where['m_ids']];
            }
            if (!empty($where['name'])) {
                $whereArr[] = ['lc.name', 'like', '%' . $where['name'] . '%'];
            }
        }
        // $where = ' m_ids in ( ' . $m_ids . ') and lc.state = 1 and l.class_time < "' . time() . '" and m_id = ' . $m_id;
        $order['class_time'] = 'desc';
        $db = Db::table('motion_lesson_course')
            ->alias('lc')
            ->leftJoin(['motion_lesson' => 'l'], 'l.id = lc.l_id')
            ->field('lc.* ,l.id lid ,  l.name lname , l.class_time');
        $lists = DbService::queryALL($db, $whereArr, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['class_time_show'] = $this->getDateAttr($list['class_time']);
        }
        return $lists;
    }

    /**
     * 获取课程文件记录
     */
    public function get_course_file($where = [], $order = [])
    {
        $list = DbService::queryOne('motion_lesson_course_file', $where, $order);
        return $list;
    }

    /**
     * 编辑课程文件记录
     */
    public function file_edit($data = [], $where = [])
    {
        $file = $this->get_course_file($where);
        if (empty($data['update_time'])) {
            $data['update_time'] = time();
        }
        DbService::save_log('motion_log', json_encode($file), json_encode($data), json_encode($where), '编辑会员课程文件记录', 'motion_lesson_course_file');
        $code = DbService::update('motion_lesson_course_file', $data, $where);
        return $code;
    }

    /**
     * 获取教练计划
     */
    public function get_batch_lessons($where = [], $order = [], $page = 0, $limit = 0)
    {
        $lists = DbService::queryALL('motion_batch_lesson', $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
            $list['class_time_show'] = $this->getDateAttr($list['class_time']);
            $list['status_show'] = $this->getStatusAttr($list['status']);
        }
        return $lists;
    }
    /**
     * 获取单个教练计划
     */
    public function get_batch_lesson($where = [], $order = [])
    {
        $list = DbService::queryOne('motion_batch_lesson', $where, $order);
        if (!empty($list)) {
            $list['class_time_show'] = $this->getDateAttr($list['class_time']);
        }
        return $list;
    }

    /**
     * 验证排课数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'name' => 'require|max:50',
            'class_time' => 'require|date',
            'warmup' => 'max:10000',
            'colldown' => 'max:10000',
        ];
        $message = [
            'name.require' => '课程名称必填',
            'name.max' => '课程名称最多五十个字',
            'class_time.require' => '上课时间必填',
            'class_time.date' => '请正确选择时间',
            'warmup.max' => '热身语最多一千个字',
            'colldown.max' => '冷身语最多一千个字',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

    /**
     * 验证小动作数据有效性
     * @param type $data 需要验证的数据
     */
    public function course_validate($data)
    {
        $rule = [
            'remark' => 'max:1000',
            'name' => 'require|max:50',
        ];
        $message = [
            'remark.max' => '备注不超过一千个字',
            'name.require' => '动作名称必填',
            'name.max' => '动作名称最多五十个字',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

    /**
     * 发送上课通知消息
     */
    public function send_lesson($id)
    {

        $wechat_lesson_id = sysconf('wechat_lesson_id');
        if (!$wechat_lesson_id) {
            return json_encode(array('errcode' => -1, 'msg' => '请先配置模板ID'));
        }

        //获取记录信息
        $lwhere['id'] = $id;
        $lesson = $this->get_arrange_list($lwhere);
        $title = $lesson['name'];
        //获取记录中的教练信息
        $coachModel = new \app\motion\model\Coach();
        $cwhere['id'] = $lesson['coach_id'];
        $course = $coachModel->get_coach($cwhere);
        $cname = $course['name'];
        //获取记录的上课信息
        $class_time_show = $this->getDateAttr($lesson['class_time']);
        //获取小动作信息
        $ltwhere['l_id'] = $lesson['id'];
        $littles = $this->get_little_courses($ltwhere);
        $little_string = '';
        foreach ($littles as $little) {
            $little_string .= "\n\t\t\t" . $little['num'] . $little['name'];
        }

        //获取用户openID
        $memberModel = new \app\motion\model\Member();
        $mwhere['m_id'] = $lesson['m_id'];
        $memberinfo = $memberModel->get_member_info($mwhere);
        if (empty($memberinfo['openid'])) {
            return json_encode(array('errcode' => -1, 'msg' => '该会员无微信信息 ， 不能发送'));
        }
        $openid = $memberinfo['openid'];
        $tem = new \app\wechat\controller\api\Template;
        $data = array(
            'first' => array('value' => '消息通知', 'color' => '#0A0A0A'),
            'keyword1' => array('value' => $title, 'color' => '#CCCCCC'),
            'keyword2' => array('value' => $cname, 'color' => '#CCCCCC'),
            'keyword3' => array('value' => $class_time_show, 'color' => '#CCCCCC'),
            'keyword4' => array('value' => $little_string, 'color' => '#173177'),
            'remark' => array('value' => '点击查看详情。', 'color' => '#173177'),
        );
        $touser = $openid;
        $templateId = $wechat_lesson_id;
        $url = url('/list', '', true, true);
        $res = $tem->sendTemplateMessage($data, $touser, $templateId, $url);
        return $res;
    }

    public function echarts($num = 30)
    {
        if (session('user.is_admin') == 0) {
            $where[]  = ['u_id', '=', session('user.id')];
        }
        //查看天数之前的所有数据
        //先获取当前时间之前的num天数的时间
        $before_time = strtotime("-{$num} day");
        $where[] = ['class_time', '>=', $before_time];
        $lists = Db::table('motion_lesson l ')
            ->leftjoin('motion_coach c', 'c.id = l.coach_id')
            ->where($where)
            ->field(' count(CASE l.status WHEN 1 THEN 1 END) AS finish,count(CASE l.status WHEN 0 THEN 1 END) AS unfinish, count(0) count ,FROM_UNIXTIME(class_time , "%Y-%m-%d") class_time')
            ->group('FROM_UNIXTIME(class_time , "%Y-%m-%d")')
            ->select();
        $xAxis = [];
        $series = [];
        //循环天数
        for ($i = 30; $i >= 0; $i--) {
            $less_time = date("Y-m-d", strtotime("-{$i} day"));
            //查询值是否在数组中数组
            $item = deep_in_array($less_time, $lists);

            if ($item) {
                $xAxis[] = $less_time;
                $series['count'][] = $item['count'];
                $series['finish'][] = $item['finish'];
                $series['unfinish'][] = $item['unfinish'];
            } else {
                $xAxis[] = $less_time;
                $series['count'][] = 0;
                $series['finish'][] = 0;
                $series['unfinish'][] = 0;
            }
        }
        $arr['xAxis'] = $xAxis;
        $arr['series'] = $series;
        return $arr;
    }
}

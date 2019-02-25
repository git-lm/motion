<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

/**
 * 身体变化
 */
class MemberData extends Model
{

    //会员身体变化数据库
    protected $table = 'motion_member_data';

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
     * 获取会员数据
     * @param Array $where  查询条件
     * @param Array $order  排序条件
     * @param Arry $field  获取的字段
     * @param int $page     查询页数
     * @param int $limit    每页显示条数
     * @param bool $isWhere 是否直接查询
     */
    public function get_member_datas($where = [], $order = [], $page = 0, $limit = 0)
    {
        $db = Db::table($this->table)
            ->alias('d')
            ->leftJoin(['motion_member_data_info' => 'di'], 'di.d_id = d.id');
        $lists = DbService::queryALL($db, $where, $order, $page, $limit);
        foreach ($lists as &$list) {
            $list['create_time_show'] = $this->getDateAttr($list['create_time']);
        }
        return $lists;
    }

    /**
     * 处理会员数据 给echarts 使用
     */
    public function dataHandle($data)
    {
        if (empty($data)) {
            return array();
        }
        $returnJson = array();
        $legend = [
            'tz' => ['name' => '体重'], 'ggj' => ['name' => '骨骼肌'], 'tzf' => ['name' => '体脂肪'],
            'sfhl' => ['name' => '身体水分含量'], 'qztz' => ['name' => '去脂体重'],
            'zlzs' => ['name' => '身体质量指数'], 'tzbfb' => ['name' => '体脂百分比'],
            'ytb' => ['name' => '腰臀比'], 'jcdx' => ['name' => '基础代谢'],
            'jrkz' => ['name' => '肌肉控制'], 'zfkz' => ['name' => '脂肪控制'], ''
        ];

        foreach ($legend  as $k => $v) {
            foreach ($data as $j => $vv) {
                dump($k);
                dump($j);exit;
                if ($k == $j) {
                    $xAxisDate[] = $v['create_time_show'];
                }
            }
        }
    }

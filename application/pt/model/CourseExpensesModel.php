<?php

namespace app\pt\model;

use think\Model;

class CourseExpensesModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_course_expenses';

    public function course()
    {
        return $this->belongsTo('courseModel', 'course_id', 'id');
    }

    /**
     * 获取单个支出
     */
    function list($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['id'])) {
            $where[] = ['id', '=', $param['id']];
        }

        $list = $this->where($where)->find();

        return $list;
    }

    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['coach_id'])) {
            $where[] = ['coach_id', '=', $param['coach_id']];
        }

        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $lists = $this->where($where)->with('course')->order('course_id')->paginate($limit);
        return $lists;
    }

    /**
     * 新增支出
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $course_id_arr = explode(',', $param['course_id']);
        $range_num = explode(',', str_replace('，', ',', $param['range_num']));
        $expenses = explode(',', $param['expenses']);
        $award = explode(',', $param['award']);
        $expenses_arr = array();
        foreach ($course_id_arr as $key => $course_id) {
            foreach ($range_num as $k => $v) {
                $num = explode('-', $v);
                $floor_num = !empty($num[0]) ? $num[0] : 0;
                $upper_num = !empty($num[1]) ? $num[1] : $floor_num;

                $info['course_id'] = $course_id;
                $info['floor_num'] = $floor_num;
                $info['upper_num'] = $upper_num;
                $info['expenses'] = !empty($expenses[$k]) ? $expenses[$k] : 0;
                $info['award'] = !empty($award[$k]) ? $award[$k] : 0;
                $info['coach_id'] = $param['coach_id'];
                $expenses_arr[] = $info;
            }
        }
        $code = $this->saveAll($expenses_arr);
        if ($code) {
            return true;
        } else {
            $this->error = '新增失败';
            return false;
        }
    }

    /**
     * 编辑产品
     */
    public function edit($param)
    {
        $validate = $this->validate_edit($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $this->updateTable($param, $param['id']);
    }
    /**
     * 更新支出信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $courseExpenses = $this->get($id);
        $code = $courseExpenses->save($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'coach_id' => 'require|number',
            'course_id' => 'require',
            'range_num' => 'require',
            'expenses' => 'require',
            // 'award' => 'number',
        ];
        $message = [
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'course_id.require' => '团课必选',
            'range_num.require' => '人数范围必填',
            'expenses.require' => '佣金比例必填',
            // 'award.number' => '请正确填写上奖励金额',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate_edit($data)
    {
        $rule = [
            'coach_id' => 'require|number',
            'course_id' => 'require',
            'floor_num' => 'require|number',
            'upper_num' => 'require|number',
            'expenses' => 'require|number',
            // 'award' => 'number',
        ];
        $message = [
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'course_id.require' => '团课必选',
            'floor_num.require' => '下限人数必填',
            'floor_num.number' => '请正确填写下限人数',
            'upper_num.require' => '上限人数必填',
            'upper_num.number' => '请正确填写上限人数',
            'expenses.require' => '佣金比例必填',
            'expenses.number' => '请正确填写上佣金比例',
            // 'award.number' => '请正确填写上奖励金额',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

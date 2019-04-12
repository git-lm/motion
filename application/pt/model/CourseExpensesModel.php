<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class CourseExpensesModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_course_expenses';

    /**
     * 获取单个支出
     */
    public function list($param)
    {
        $where['status'] = ['=', 0];
        if (!empty($param['id'])) $where['id'] = ['=', $param['id']];
        $list =  self::where($where)->find();

        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        $where['status'] = ['=', 0];
        if (!empty($param['course_id']))  $where['course_id'] = ['=', $param['course_id']];
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $lists =  self::where($where)->paginate($limit);
        return $lists;
    }

    /**
     * 新增支出
     */
    public function add($param)
    {
        $validate = $this->validateMember($param);
        if ($validate) {
            $this->error = $validate;
            return;
        }
        $code = $this->save($param);
        if ($code) {
            return;
        } else {
            $this->error = '新增失败';
        }
    }

    /**
     * 编辑产品
     */
    public function edit($param)
    {
        $validate = $this->validateMember($param);
        if ($validate) {
            $this->error = $validate;
            return;
        }
        $this->updateCourse($param, $param['id']);
    }
    /**
     * 更新支出信息
     */
    public function updateCourse($param, $course_id)
    {
        if (empty($course_id)) {
            $this->error = '请选择要操作的数据！';
        }
        $code =  $this->where(array('id' => $course_id))->update($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
        }
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validateMember($data)
    {
        $rule = [
            'name' => 'require|max:5|min:2|chsAlpha',
        ];
        $message = [
            'name.require' => '产品名称必填',
            'name.min' => '产品名称最少两个字',
            'name.max' => '产品名称最多五个字',
            'name.chsAlpha' => '产品名称只能汉子和字母',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

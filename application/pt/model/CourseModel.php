<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class CourseModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $table = 'pt_course';

    /**
     * 关联开支表
     */
    public  function  courseExpenses()
    {
        return $this->hasMany('pt_course_expenses', 'course_id', 'id');
    }
    /**
     * 获取单个会员
     */
    public function list($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['id'])) $where[] = ['id', '=', $param['id']];
        $list =  $this->where($where)->find();

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
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        if (!empty($param['name'])) $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        $lists =  $this->where($where)->paginate($limit);
        return $lists;
    }

    /**
     * 新增会员
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $code = $this->save($param);
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
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $this->updateTable($param, $param['id']);
    }
    /**
     * 更新会员信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $course =  $this->get($id);
        $code =  $course->save($param);
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
            'name' => 'require|max:20|min:2',
        ];
        $message = [
            'name.require' => '产品名称必填',
            'name.min' => '产品名称最少两个字',
            'name.max' => '产品名称最多十一个字',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

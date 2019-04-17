<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ClassesModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_classes';

    /**
     * 关联团课上课记录
     */
    public  function  classesGroup()
    {
        return $this->hasOne('classesGroupModel', 'class_id', 'id');
    }
    /**
     * 获取单个会员
     */
    public function list($param)
    {
        $where['status'] = ['=', 0];
        if (!empty($param['phone'])) $where['phone'] = ['=', $param['phone']];
        if (!empty($param['id'])) $where['id'] = ['=', $param['id']];
        $list =  self::where($where)->find();

        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        $where['status'] = ['=', 1];
        if (!empty($param['begin_time']))  $where['class_at'] = ['>', $param['begin_time']];
        if (!empty($param['end_time']))  $where['class_at'] = ['<', $param['end_time']];
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $query = self::where($where);
        $lists =  $query->paginate($limit);
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
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return;
        }
        $this->updateCourse($param, $param['id']);
    }
    /**
     * 更新会员信息
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
    public function validate($data)
    {
        $rule = [
            'class_at' => 'require|date',
            'type' => 'require|in:1,2',
        ];
        $message = [
            'class_at.require' => '课程日期必填',
            'class_at.date' => '请正确选择课程日期',
            'type.require' => '请选择团课或者私教',
            'type.in' => '请选择团课或者私教'
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

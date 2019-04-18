<?php

namespace app\pt\model;

use think\Model;
use think\Db;
use app\motion\model\Coach;

class ClassesPrivateModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $crateTime = 'create_at';
    protected $table = 'pt_classes_private';


    public function member()
    {
        return $this->belongsTo('memberModel', 'member_id', 'id');
    }


    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'class_id' => 'require|number',
            'course_id' => 'require|number',
            'coach_id' => 'require|number',
            'begin_at' => 'require|date',
            'end_at' => 'require|date',
        ];
        $message = [
            'class_id.require' => '添加失败',
            'class_id.number' => '添加失败',
            'course_id.require' => '私教必选',
            'course_id.number' => '请正确选择私教',
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'begin_at.require' => '上课时间必选',
            'begin_at.date' => '请正确选择上课时间',
            'end_at.require' => '下课时间必选',
            'end_at.date' => '请正确选择下课时间',

        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

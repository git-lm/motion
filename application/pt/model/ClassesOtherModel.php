<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ClassesOtherModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $createTime = 'create_at';
    protected $table = 'pt_classes_other';


    public function class()
    {
        return $this->belongsTo('classModel', 'class_id', 'id');
    }
}

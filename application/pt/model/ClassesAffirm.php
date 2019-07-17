<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ClassesAffirm extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $table = 'pt_classes_affirm';
}

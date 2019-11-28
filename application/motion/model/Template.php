<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class Template extends Model
{

    protected $table = 'motion_template';
    protected $autoWriteTimestamp = true;
    public function getCreateTimeStrAttr($value, $data)
    {
        if (empty($data['create_time'])) return '';
        return date('Y-m-d', $data['create_time']);
    }

    public function lesson()
    {
        return $this->hasMany('TemplateLesson', 't_id', 'id');
    }
}

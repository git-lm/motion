<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class TemplateCourse extends Model
{

    protected $table = 'motion_template_course';
    protected $autoWriteTimestamp = true;

    public function getMidsStrAttr($value, $data)
    {
        $name =  Motion::where('id', 'in', $data['m_ids'])->column('name');
        return implode(',', $name);
    }

    public function getCreateTimeStrAttr($value, $data)
    {
        if (empty($data['create_time'])) return '';
        return date('Y-m-d', $data['create_time']);
    }
}

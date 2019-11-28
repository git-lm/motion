<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class TemplateLesson extends Model
{

    protected $table = 'motion_template_lesson';

    protected $autoWriteTimestamp = true;

    public function getWarmupMidsStrAttr($value, $data)
    {
        $name =  Motion::where('id', 'in', $data['warmup_mids'])->column('name');
        return implode(',', $name);
    }
    public function getColldownMidsStrAttr($value, $data)
    {
        $name =  Motion::where('id', 'in', $data['colldown_mids'])->column('name');
        return implode(',', $name);
    }

    public function getCreateTimeStrAttr($value, $data)
    {
        if (empty($data['create_time'])) return '';
        return date('Y-m-d', $data['create_time']);
    }

    public function course()
    {
        return $this->hasMany('TemplateCourse', 'l_id', 'id');
    }
}

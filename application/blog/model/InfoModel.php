<?php

namespace app\blog\model;

use think\Model;
use think\Db;

class InfoModel extends Model
{

    protected $table = 'blog_coach_info';

    public function coach()
    {
        return  $this->belongsTo('app\motion\model\Coach', 'id', 'coach_id');
    }
}

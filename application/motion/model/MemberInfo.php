<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class MemberInfo extends Model
{

    protected $table = 'motion_member_info';

    public function fans()
    {
        return $this->belongsTo('Fans', 'f_id', 'id');
    }
}

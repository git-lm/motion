<?php

namespace app\motion\model;

use think\Model;
use think\Db;
use service\DbService;

class LoginLog extends Model
{

    protected $table = 'motion_login_log';

    public function echarts()
    {
        $data =   Db::table($this->table)
            ->where('DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(
            FROM_UNIXTIME(create_time, "%Y-%m-%d") )')
            ->group("FROM_UNIXTIME(create_time, '%H')")
            ->field("concat(FROM_UNIXTIME(create_time, '%H')  ,'时') as name, count(*) as value")
            ->select();
        return $data;
    }
}

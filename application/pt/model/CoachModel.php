<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class CoachModel extends Model
{
    public $error;
    protected $coach_id;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $createTime = 'create_at';
    protected $table = 'motion_coach';

    public function getCoachId()
    {
        $this->coach_id;
    }
    public function setCoachId($coach_id)
    {
        $this->coach_id = $coach_id;
    }

    /**
     * 关联私教支出
     */
    public function  productExpenses()
    {
        return $this->hasMany('ProductExpensesModel', 'coach_id', 'id');
    }
    /**
     * 关联团课支出
     */
    public function  groupExpenses()
    {
        return $this->hasMany('CourseExpensesModel', 'coach_id', 'id');
    }
    /**
     * 关联教练私教订单
     */
    public function orderProduct()
    {
        return $this->hasMany('OrderProductModel', 'coach_id', 'id');
    }
    /**
     * 获取教练私教会员
     */
    public function coachProductMember()
    {
        $mm = new MemberModel();
        $lists = $mm->where(function ($query) {
            $opm = new OrderProductModel();
            $member_ids = $opm->join('pt_order o', 'o.id = order_id')->where(array('o.order_status' => 1, 'o.pay_status' => 1))->where(array('coach_id' => $this->coach_id))->whereBetweenTimeField('begin_at', 'end_at')->field('o.member_id')->column('o.member_id');
            $query->whereIn('id', $member_ids);
        })->where('status', 1)->select();
        return $lists;
    }
}

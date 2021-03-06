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
     * 关联账号
     */
    public function systemUser()
    {
        return $this->belongsTo('app\motion\model\SystemUser', 'u_id', 'id');
    }

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->hasOne('app\motion\model\Member', 'coach_id', 'id');
    }

    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['name'])) $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $lists =  $this->with(['systemUser'])->where($where)->paginate($limit);
        return $lists;
    }
    /**
     * 获取教练私教会员
     */
    public function coachProductMember()
    {
        $mm = new MemberModel();
        $lists = $mm->where(function ($query) {
            $opm = new OrderProductModel();
            $subQuery  = Db::table('pt_classes_private')->group('order_id')->where(['status' => 1])->field(['count(0) count', 'order_id', 'member_id', 'product_id'])->buildSql();
            $member_ids = $opm->join('pt_order o', 'o.id = order_id', 'left')
                ->join([$subQuery => 't'], 't.order_id = o.id', 'left')
                ->join('pt_product p',  'p.id = t.product_id', 'left')
                ->where(array('o.order_status' => 1, 'o.pay_status' => 1))
                ->where(array('coach_id' => $this->coach_id))
                ->whereBetweenTimeField('begin_at', 'end_at')
                ->where('t.count < p.number or t.count is null')
                ->field('o.member_id')->column('o.member_id');
            $query->whereIn('id', $member_ids);
        })->where('status', 1)->select();
        return $lists;
    }

    /**
     * 根据教练ID获取openid
     */
    public function getOpenidForId()
    {
        $coach_id = $this->getCoachId();
    }
}

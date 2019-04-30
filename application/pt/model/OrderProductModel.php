<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class OrderProductModel extends Model
{
    public $error;
    protected $orderData;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_order_product';

    public function setOrderData($orderData)
    {
        $this->orderData = $orderData;
    }
    public function getPayStatusTextAttr($value, $data)
    {
        $status = [1 => '已支付', 0 => '未支付'];
        return $status[$data['type']];
    }

    /**
     * 关联订单
     */
    public function  order()
    {
        return $this->belongsTo('orderModel', 'order_id', 'id');
    }
    /**
     * 关联项目
     */
    public function  product()
    {
        return $this->belongsTo('productModel', 'product_id', 'id');
    }

    /**
     * 关联会员
     */
    public function  member()
    {
        return $this->belongsTo('memberModel', 'member_id', 'id');
    }

    /**
     * 关联教练
     */
    public function  coach()
    {
        return $this->belongsTo('coachModel', 'coach_id', 'id');
    }

    /**
     * 获取单个项目订单
     */
    public function list($param)
    {
        $where['status'] = ['=', 1];
        if (!empty($param['id'])) $where['id'] = ['=', $param['id']];
        if (!empty($param['order_id'])) $where['order_id'] = ['=', $param['order_id']];
        if (!empty($param['product_id'])) $where['product_id'] = ['=', $param['product_id']];
        if (!empty($param['member_id'])) $where['member_id'] = ['=', $param['member_id']];
        if (!empty($param['coach_id'])) $where['coach_id'] = ['=', $param['coach_id']];
        $list =  $this->where($where)->find();
        return $list;
    }
    /**
     * 获取会员私教订单
     */
    public static function getProductForMemberId($member_id)
    {
        $list  = self::join('pt_order o', 'order_id = o.id')->where('o.order_status', 1)->where('o.pay_status', 1)->where('o.member_id', $member_id)
            ->find();
        return $list;
    }

    /**
     * 新增订单项目
     */
    public function addOrderProduct()
    {
        $this->order_id = $this->orderData['order_id'];
        $this->product_id = $this->orderData['product_id'];
        $this->member_id = $this->orderData['member_id'];
        $this->coach_id = $this->orderData['coach_id'];
        $this->product_price = $this->orderData['product_price'];
        $this->duration = $this->orderData['duration'];
        $this->give_time = $this->orderData['give_time'];
        $this->begin_at = $this->orderData['begin_at'];
        $this->end_at = date('Y-m-d', strtotime('+' . $this->duration + $this->give_time . ' day', strtotime($this->begin_at)));
        $this->save();
    }
}
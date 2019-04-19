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
        return $this->belongsTo('app\motion\model\Coach', 'coach_id', 'id');
    }

    /**
     * 获取单个课程
     */
    public function list($param)
    {
        if (empty($param['status'])) {
            $where['status'] = ['=', 1];
        } else {
            $where['status'] = ['=', $param['status']];
        }
        if (!empty($param['id'])) $where['id'] = ['=', $param['id']];
        $list =  $this->with(['coach', 'course'])->where($where)->find();

        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['status'])) {
            $where['status'] = ['=', 1];
        } else {
            $where['status'] = ['=', $param['status']];
        }
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $query = $this->with(['coach', 'course'])->where($where);
        $lists =  $query->paginate($limit);

        return $lists;
    }

    /**
     * 新增课程
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

    /**
     * 编辑产品
     */
    public function edit($param)
    {
        list($start, $end) = explode('-', $param['class_time']);
        $class_at = $param['class_date'];
        $param['class_at'] = $param['class_date'];
        $param['begin_at'] = $class_at . ' ' . trim($start);
        $param['end_at'] = $class_at . ' ' . trim($end);
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $this->updateTable($param, $param['id']);
    }
    /**
     * 更新课程信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $class = $this->get($id);
        $code = $class->save($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'order_id' => 'require|number',
            'product_id' => 'require|number',
            'member_id' => 'require|number',
            'coach_id' => 'require|number',
            'duration' => 'require|number',
            'course_id' => 'number',
        ];
        $message = [
            'class_at.require' => '课程日期必填',
            'class_at.date' => '请正确选择课程日期',
            'begin_at.require' => '上课时间必填',
            'begin_at.date' => '请正确选择上课时间',
            'end_at.require' => '下课时间必填',
            'end_at.date' => '请正确选择下课时间',
            'type.require' => '请选择团课或者私教',
            'type.in' => '请选择团课或者私教',
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'course_id.number' => '请正确选择课程',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

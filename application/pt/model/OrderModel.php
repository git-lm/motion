<?php

namespace app\pt\model;

use think\Model;
use think\Db;
use app\pt\model\OrderProductModel;

class OrderModel extends Model
{
    public $error;
    protected $orderData;
    protected $product;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_order';

    public function getPayStatusTextAttr($value, $data)
    {
        $status = [1 => '已支付', 0 => '未支付'];
        return $status[$data['type']];
    }


    /**
     * 关联订单项目
     */
    public function  orderProduct()
    {
        return $this->hasOne('orderProductModel', 'order_id', 'id');
    }
    /**
     * 关联团课上课记录
     */
    public function  member()
    {
        return $this->belongsTo('memberModel', 'member_id', 'id');
    }



    /**
     * 
     */

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
        $list =  $this->with(['member', 'orderProduct', 'coach'])->where($where)->find();
        
        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['order_status'])) {
            $where['order_status'] = ['=', 1];
        } else {
            $where['order_status'] = ['=', $param['order_status']];
        }
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $query = $this->with(['member', 'orderProduct','orderProduct.product','orderProduct.coach'])->where($where);
        $lists =  $query->paginate($limit);

        return $lists;
    }

    /**
     * 新增课程
     */
    public function addOffline($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $orderProduct = $this->getOrderProduct($param['member_id']);
        if (!empty($orderProduct)) {
            $this->error = '该会员当前时间内已存在项目';
            return false;
        }
        $param['pay_status'] = 1;
        $param['source'] = 1;
        $param['pay_at'] = date('Y-m-d H:i:s');
        $this->orderData = $param;
        $this->setTotalAmount($param['product_id']);
        $this->setOrderAmount($param['product_id']);

        try {
            Db::startTrans();
            $this->addOrder();
            $opm = new OrderProductModel();
            $opm->setOrderData($this->orderData);
            $opm->addOrderProduct();
            if ($opm->error) {
                Db::rollback();
                $this->error = $opm->error;
            } else {
                Db::commit();
                return true;
            }
        } catch (Exception $exc) {
            Db::rollback();
            $this->error = '新增失败';
            return false;
        }
    }

    public function addOrder()
    {
        $this->order_sn = $this->getOrderSn();
        $this->member_id = $this->orderData['member_id'];
        $this->pay_status = $this->orderData['pay_status'];
        $this->pay_at = $this->orderData['pay_at'];
        $this->source = $this->orderData['source'];
        $code = $this->save();
        if ($code) {
            $this->orderData['order_id'] = $this->id;
            return true;
        } else {
            $this->error = '订单生成失败';
            return false;
        }
    }


    /**
     * 赋值总金额
     */
    public function setTotalAmount($product_id)
    {
        $pm = new ProductModel();
        $product = $pm->where(array('id' => $product_id))->find();
        $this->orderData['product_price'] = $product['price'];
        $this->orderData['duration'] = $product['duration'];
        $this->total_amount = $product['price'];
    }
    /**
     * 赋值应付金额
     */
    public function setOrderAmount($product_id)
    {
        $pm = new ProductModel();
        $product = $pm->where(array('id' => $product_id))->find();
        $this->order_amount = $product['price'];
    }



    public function getOrderSn()
    {
        return  time() . rand(1000, 9999);
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
            'member_id' => 'require|number',
            'product_id' => 'require|number',
            'coach_id' => 'require|number',
            'give_time' => 'require|number',
            'begin_at' => 'require|date',
        ];
        $message = [
            'member_id.require' => '请正确选择会员',
            'member_id.number' => '请正确选择会员',
            'product_id.require' => '项目必选',
            'product_id.number' => '请正确选择项目',
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'give_time.require' => '赠送天数必填',
            'give_time.number' => '请正确填写赠送天数',
            'begin_at.require' => '开始时间必填',
            'begin_at.date' => '请正确选择开始时间',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }

    /**
     * 验证会员是否存在有效的记录
     */
    public function getOrderProduct($member_id)
    {
        $opm = new OrderProductModel();
        $orderProduct = $opm->where(array('member_id' => $member_id))->whereBetweenTimeField('begin_at', 'end_at')->find();
        return $orderProduct;
    }
}

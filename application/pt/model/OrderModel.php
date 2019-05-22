<?php

namespace app\pt\model;

use app\pt\model\OrderProductModel;
use think\Db;
use think\Model;

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
    protected $searchOrder;
    public function getPayStatusTextAttr($value, $data)
    {
        $status = [1 => '已支付', 0 => '未支付'];
        return $status[$data['type']];
    }

    /**
     * 关联订单项目
     */
    public function orderProduct()
    {
        return $this->hasOne('orderProductModel', 'order_id', 'id');
    }
    /**
     * 关联团课上课记录
     */
    public function member()
    {
        return $this->belongsTo('memberModel', 'member_id', 'id');
    }


    /**
     * 查询排序
     */
    public function setSearchOrder($order)
    {
        $this->searchOrder = $order;
        return $this;
    }

    /**
     * 获取单个订单
     */
    function list($param)
    {
        if (empty($param['order_status'])) {
            $where[] = ['order_status', '=', 1];
        } else {
            $where[] = ['order_status', '=', $param['order_status']];
        }
        if (!empty($param['id'])) {
            $where[] = ['id', '=', $param['id']];
        }

        $list = $this->where($where)->with(['member', 'orderProduct', 'orderProduct' => ['coach', 'product']])->find();

        return $list;
    }

    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['order_status'])) {
            $where[] = ['order_status', '=', 1];
        } else {
            $where[] = ['order_status', '=', $param['order_status']];
        }
        if (!empty($param['member_id'])) {
            $where[] = ['o.member_id', '=', $param['member_id']];
        }

        if (!empty($param['product_name'])) {
            $where[] = ['p.name', 'like', '%' . $param['product_name'] . '%'];
        }

        if (!empty($param['member_name'])) {
            $where[] = ['m.name', 'like', '%' . $param['member_name'] . '%'];
        }

        if (!empty($param['coach_name'])) {
            $where[] = ['c.name', 'like', '%' . $param['coach_name'] . '%'];
        }
        if (!empty($param['create_expire_time'])) {
            list($begin, $end) = explode(' - ', $param['create_expire_time']);
            $where[] = ['o.create_at', '>=', $begin . ' 00:00:00'];
            $where[] = ['o.create_at', '<=', $end . ' 23:59:59'];
        }
        if (!empty($param['end_expire_time'])) {
            list($begin, $end) = explode(' - ', $param['end_expire_time']);
            $where[] = ['op.end_at', '>=', $begin . ' 00:00:00'];
            $where[] = ['op.end_at', '<=', $end . ' 23:59:59'];
        }

        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $query = $this->where($where)->alias('o')
            ->leftJoin('pt_member m', 'm.id = o.member_id') //关联会员
            ->leftJoin('pt_order_product op', 'op.order_id = o.id') //关联私教订单
            ->leftjoin('motion_coach c', 'c.id = op.coach_id') //关联私教
            ->leftjoin('pt_product p', 'p.id = op.product_id') //关联私教项目
            ->field('m.name mname , p.name pname , p.price pprice , c.name cname ,o.create_at , o.pay_status , op.begin_at , op.end_at ,o.id ');
        if (!empty($this->searchOrder)) {
            $query->order($this->searchOrder);
        }
        $lists = $query->paginate($limit);
        return $lists;
    }

    /**
     * 新增线下订单
     */
    public function addOffline($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        // $orderProduct = $this->getOrderProduct($param['member_id']);
        // if (!empty($orderProduct)) {
        //     $this->error = '该会员当前时间内已存在项目';
        //     return false;
        // }
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
                return false;
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

    public function del()
    { }

    public function addOrder()
    {
        $param['order_sn'] = $this->getOrderSn();
        $param['member_id'] = $this->orderData['member_id'];
        $param['pay_status'] = $this->orderData['pay_status'];
        $param['pay_at'] = $this->orderData['pay_at'];
        $param['source'] = $this->orderData['source'];
        $code = $this->save($param);
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
        return time() . rand(1000, 9999);
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
     * 更新订单信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $order = $this->get($id);
        $code = $order->save($param);
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
        $orderProduct = $opm->alias('op')->where(array('op.member_id' => $member_id))->join('pt_order o', 'o.id = op.order_id')->where(array('order_status' => 1))->whereBetweenTimeField('begin_at', 'end_at')->find();
        return $orderProduct;
    }
}

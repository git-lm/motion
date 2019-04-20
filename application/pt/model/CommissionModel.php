<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class CommissionModel extends Model
{
    public $error;
    protected $coach_id;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $createTime = 'create_at';
    protected $table = 'pt_commission';

    /**
     * 关联课程
     */
    public function  class()
    {
        return $this->belongsTo('classModel', 'class_id', 'id');
    }
    /**
     * 关联教练
     */
    public function  coach()
    {
        return $this->belongsTo('coachModel', 'coach_id', 'id');
    }

    /**
     * 关联订单
     */
    public function  order()
    {
        return $this->belongsTo('orderModel', 'order_id', 'id');
    }

    /**
     * 关联私教支出
     */
    public function  productExpenses()
    {
        return $this->belongsTo('ProductExpensesModel', 'expenses_id', 'id');
    }
    /**
     * 关联团课支出
     */
    public function  groupExpenses()
    {
        return $this->belongsTo('CourseExpensesModel', 'expenses_id', 'id');
    }
    /**
     * 获取教练课程费用
     */
    public function add($class)
    {
        if (empty($class)) {
            $this->error = '无课程信息';
            return false;
        }
        if ($class['type'] == 1) { //说明是私教
            $classesPrivate = $class->classesPrivate;
            $product_id =  $classesPrivate->product_id;    //获取上课时私教项目
            $member_id =  $classesPrivate->member_id;      //获取上课会员
            $coach_id = $class->coach_id;                   //获取上课教练
            //获取教练项目的费用
            $pem = new ProductExpensesModel();
            $expenses = $pem->list(array('coach_id' => $coach_id, 'product_id' => $product_id));
            if (empty($expenses)) {
                $this->error = '该教练该项目无费用';
                return false;
            }
            //获取会员会员身份识别
            $orderProduct = OrderProductModel::getProductForMemberId($member_id);
            $oder_id =  $orderProduct['order_id'];
        } else if ($class['type'] == 2) { //说明是团课
            $classesGroup = $class->classesGroup;

            $coach_id = $class->coach_id;                   //获取上课教练
            $number = $classesGroup->number;                //上课人数
            $course_id = $class->course_id;                 //团课ID

            $cem = new CourseExpensesModel();

            $expenses = $cem
                ->where(array('course_id' => $course_id, 'coach_id' => $coach_id))
                ->where('floor_num', '<=', $number)
                ->where('upper_num', '>=', $number)
                ->order('id desc')
                ->find();
            if (empty($expenses)) {
                $this->error = '该教练该区间无费用';
                return false;
            }
            $oder_id = 0;
        }
        $cm = new CommissionModel();
        $commissio = $cm->where(array('class_id' => $class['id']))->find();
        if (empty($commissio)) {
            $commissio = $this;
        }
        $param['coach_id'] = $coach_id;
        $param['class_id'] = $class['id'];
        $param['order_id'] = $oder_id;
        $param['expenses_id'] = $expenses['id'];
        $param['expenses'] = $expenses['expenses'];
        $param['award'] =  $expenses['award'];
        $param['price'] = $expenses['expenses'] + $expenses['award'];
        $param['type'] = $class['type'];
        $code =  $commissio->save($param);
        if ($code) {
            return true;
        } else {
            $this->error = '费用更新失败';
            return false;
        }
    }
    /**
     * 更新团课信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $commission = $this->get($id);
        $code =  $commission->save($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }
}

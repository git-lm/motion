<?php

namespace app\pt\model;

use think\Model;

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
    function class()
    {
        return $this->belongsTo('classModel', 'class_id', 'id');
    }
    /**
     * 关联教练
     */
    public function coach()
    {
        return $this->belongsTo('coachModel', 'coach_id', 'id');
    }

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo('orderModel', 'order_id', 'id');
    }


    public function orderProduct()
    {
        return $this->belongsTo('orderProductModel', 'order_id', 'order_id');
    }

    /**
     * 关联私教支出
     */
    public function productExpenses()
    {
        return $this->belongsTo('ProductExpensesModel', 'expenses_id', 'id');
    }
    /**
     * 关联团课支出
     */
    public function groupExpenses()
    {
        return $this->belongsTo('CourseExpensesModel', 'expenses_id', 'id');
    }

    /**
     * 获取单个课程
     */
    function list($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['id'])) {
            $where[] = ['id', '=', $param['id']];
        }

        $list = $this->with(['coach', 'course', 'commission'])->where($where)->find();

        return $list;
    }

    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['status'])) {
            $where[] = ['c.status', '=', 1];
        } else {
            $where[] = ['c.status', '=', $param['status']];
        }
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        if (!empty($param['coach_name'])) {
            $where[] = ['coach.name', 'like', '%' . $param['coach_name'] . '%'];
        }
        if (!empty($param['member_name'])) {
            $where[] = ['m.name', 'like', '%' . $param['member_name'] . '%'];
        }

        if (!empty($param['type'])) {
            $where[] = ['c.type', '=', $param['type']];
        }

        if (!empty($param['expire_time'])) {
            list($begin, $end) = explode(' - ', $param['expire_time']);
            if (!empty($begin)) {
                $where[] = ['class.class_at', '>=', $begin];
            }
            if (!empty($end)) {
                $where[] = ['class.class_at', '<=', $end];
            }
        }
        if (!empty($param['coach_id'])) {
            $where[] = ['c.coach_id', '=', $param['coach_id']];
        }

        $query = $this->with(['order_product', 'order_product.product'])->where($where)->alias('c')
            ->field('c.* , course.name course_name ,coach.name coach_name ,class.class_at ,class.begin_at , class.end_at ,m.name mname , cg.number , p.name pname')
            ->leftJoin('motion_coach coach', 'coach.id = c.coach_id')
            ->leftJoin('pt_classes class', 'class.id = c.class_id')
            ->leftJoin('pt_course course', 'course.id = class.course_id')
            ->leftJoin('pt_classes_group cg', 'cg.class_id = c.class_id')
            ->leftJoin('pt_classes_private cp', 'cp.class_id = c.class_id')
            ->leftJoin('pt_member m', 'm.id = cp.member_id')
            ->leftJoin('pt_product p', 'p.id = cp.product_id');
        $lists = $query->paginate($limit);
        return $lists;
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
            $product = $classesPrivate->product;
            $product_id = $classesPrivate->product_id; //获取上课时私教项目
            $member_id = $classesPrivate->member_id; //获取上课会员
            $coach_id = $class->coach_id; //获取上课教练
            $coach_class_at = $classesPrivate->begin_at; //上课时间
            $unit_price = $product->unit_price; //单次课时费用
            //获取教练项目的费用
            $pem = new ProductExpensesModel();
            $expenses = $pem->list(array('coach_id' => $coach_id, 'product_id' => $product_id));
            if (empty($expenses)) {
                $this->error = '该教练该项目无费用';
                return false;
            }
            //获取会员会员身份识别
            $orderProduct = OrderProductModel::getProductForMemberId($member_id);
            $oder_id = $orderProduct['order_id'];
            $order_product_id = $orderProduct['order_product_id'];
        } else if ($class['type'] == 2) { //说明是团课
            $classesGroup = $class->classesGroup;

            $coach_id = $class->coach_id; //获取上课教练
            $number = $classesGroup->number; //上课人数
            $course_id = $class->course_id; //团课ID
            $coach_class_at = $classesGroup->update_at; //上课时间
            $unit_price = '0.00'; //单次课时费用
            $cem = new CourseExpensesModel();

            $expenses = $cem
                ->where(array('course_id' => $course_id, 'coach_id' => $coach_id, 'status' => 1))
                ->where('floor_num', '<=', $number)
                ->where('upper_num', '>=', $number)
                ->order('id desc')
                ->find();
            if (empty($expenses)) {
                $this->error = '该教练该区间无费用';
                return false;
            }
            $oder_id = 0;
            $order_product_id = 0;
        }
        $cm = new CommissionModel();
        $commission = $cm->where(array('class_id' => $class['id']))->find();
        if (empty($commission)) {
            $commission = $this;
        } else {
            $param['update_at'] = date('Y-m-d H:i:s');
        }
        $param['coach_id'] = $coach_id;
        $param['class_id'] = $class['id'];
        $param['order_id'] = $oder_id;
        $param['order_product_id'] = $order_product_id;
        $param['expenses_id'] = $expenses['id'];
        $param['expenses'] = $expenses['expenses'];
        $param['award'] = $expenses['award'];
        $param['price'] = $expenses['expenses'] + $expenses['award'];
        $param['type'] = $class['type'];
        $param['coach_class_at'] = $coach_class_at;
        $param['unit_price'] = $unit_price;
        $code = $commission->save($param);

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
        $code = $commission->save($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }
}

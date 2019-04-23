<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ClassesPrivateModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $createTime = 'create_at';
    protected $table = 'pt_classes_private';


    public function member()
    {
        return $this->belongsTo('memberModel', 'member_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo('productModel', 'product_id', 'id');
    }

    public function add($param)
    {
        $cm = new  ClassesModel();
        $class = $cm->where(array('id' => $param['class_id']))->find();
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $classPrivate = $this->where(array('class_id' => $class['id']))->find();
        if (!empty($classPrivate['end_at'])) {
            $this->error = '该课程已结束';
            return false;
        }
        if (empty($classPrivate)) {
            $classPrivate = $this;
        }

        $orderProduct = OrderProductModel::getProductForMemberId($param['member_id']);
        $classPrivate->class_id = $class['id'];
        $classPrivate->member_id = $param['member_id'];
        $classPrivate->product_id = $orderProduct['product_id'];
        $classPrivate->begin_at = !empty($param['begin_at']) ? $param['begin_at'] : null;
        $classPrivate->end_at = !empty($param['end_at']) ? $param['end_at'] : null;
        try {
            Db::startTrans();
            $classPrivate->save();
            $cm = new CommissionModel();
            $cm->add($class);
            if ($cm->error) {
                Db::rollback();
                $this->error = $cm->error;
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







    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'class_id' => 'require|number',
            'begin_at' => 'require|date',
            'end_at' => 'date',
        ];
        $message = [
            'class_id.require' => '请正确选择课程',
            'class_id.number' => '请正确选择课程',
            'begin_at.require' => '开始时间必选',
            'begin_at.date' => '请正确选择开始时间',
            'end_at.date' => '请正确选择结束时间',

        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

<?php

namespace app\pt\model;

use think\Model;

class ClassesModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_classes';

    protected $order = '';
    protected $where = '';

    public function getTypeTextAttr($value, $data)
    {
        $type = [1 => '私教', 2 => '团课'];
        return $type[$data['type']];
    }

    /**
     * 关联团课上课记录
     */
    public function classesGroup()
    {
        return $this->hasOne('classesGroupModel', 'class_id', 'id');
    }
    /**
     * 关联团课上课记录
     */
    public function classesPrivate()
    {
        return $this->hasOne('classesPrivateModel', 'class_id', 'id');
    }
    /**
     * 关联团课上课记录
     */
    public function classesOther()
    {
        return $this->hasOne('ClassesOtherModel', 'class_id', 'id');
    }
    /**
     * 关联教练
     */
    public function coach()
    {
        return $this->belongsTo('coachModel', 'coach_id', 'id');
    }
    /**
     * 关联团课
     */
    public function course()
    {
        return $this->belongsTo('courseModel', 'course_id', 'id');
    }


    public function classAffirm()
    {
        return $this->hasOne('ClassAffirm', 'class_id', 'id');
    }

    /**
     * 关联教练费用
     */
    public function commission()
    {
        return $this->hasOne('commissionModel', 'class_id', 'id');
    }

    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }
    public function setWhere($where)
    {
        $this->where = $where;
        return $this;
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
            $where[] = ['classes_model.status', '=', 1];
        } else {
            $where[] = ['classes_model.status', '=', $param['status']];
        }
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        if (!empty($param['type'])) {
            $where[] = ['classes_model.type', '=', $param['type']];
        }

        if (!empty($param['coach_id'])) {
            $where[] = ['classes_model.coach_id', '=', $param['coach_id']];
        }

        $course_name = !empty($param['course_name']) ? $param['course_name'] : '';
        $coach_name = !empty($param['coach_name']) ? $param['coach_name'] : '';
        if (!empty($param['expire_time'])) {
            list($begin, $end) = explode(' - ', $param['expire_time']);
            if (!empty($begin)) {
                $where[] = ['class_at', '>=', $begin];
            }
            if (!empty($end)) {
                $where[] = ['class_at', '<=', $end];
            }
        }
        $query = $this->where($where)->withJoin(['coach' => function ($query) use ($coach_name) {
            $query->where('name', 'like', '%' . $coach_name . '%');
        }, 'course' => function ($query) use ($course_name) {
            if ($course_name) {
                $query->where('name', 'like', '%' . $course_name . '%');
            }
        }, 'commission'], 'left');
        if (!empty($this->where)) {
            $query->where($this->where);
        }
        if (!empty($this->order)) {
            $query->order($this->order);
        }
        $lists = $query->paginate($limit);
        return $lists;
    }

    /**
     * 新增课程
     */
    public function add($param)
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
        // $res = $this->checkClassTime($param['coach_id'], $param['begin_at'], $param['end_at']);
        // if ($res) {
        //     $this->error = '该' . $param['class_time'] . '时间段该教练已有课';
        //     return false;
        // }

        $code = $this->create($param, true);
        if ($param['type'] == 3) {
            $com = new ClassesOtherModel();
            $com->remark = $param['remark'];
            $com->class_id = $code->id;
            $com->save();
        }
        if ($code) {
            return;
        } else {
            $this->error = '新增失败';
            return false;
        }
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


        $res = $this->checkClassTime($param['coach_id'], $param['begin_at'], $param['end_at'], $param['id']);
        if ($res) {
            $this->error = '该时间段该教练已有课';
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
            if ($class['type'] == 3) {
                $com = new ClassesOtherModel();
                $Other =  $com->get(array('class_id' => $id));
                if (isset($param['status']) && $param['status'] == 0) {
                    $Other->status = 0;
                } else {
                    $Other->remark = $param['remark'];
                }
                $Other->save();
            }
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }
    /**
     * 验证会员上课时间是否冲突
     */
    public function checkClassTime($coach_id, $begin, $end, $class_id = 0)
    {
        $map1 = [
            ['begin_at', '>', $begin],
            ['begin_at', '<', $end],
        ];
        $map2 = [
            ['begin_at', '<', $begin],
            ['end_at', '>', $end],
        ];
        $map3 = [
            ['end_at', '>', $begin],
            ['end_at', '<', $end],
        ];
        $query = $this
            // ->whereOr([$map1, $map2, $map3])
            ->where('coach_id', $coach_id)
            ->where(function ($query) use ($map1, $map2, $map3) {
                $query->whereOr([$map1, $map2, $map3]);
            });
        if (!empty($class_id)) {
            $query->where('id', '<>', $class_id);
        }
        $list = $query->find();
        if (!empty($list)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 确认上课
     */
    public function affirmPrivate($class_id)
    {
        $class = $this->get($class_id);
        if (empty($class)) {
            $this->error = '无此课程';
            return false;
        }
        if ($class['type'] != 1) {
            $this->error = '该课程无需确认';
            return false;
        }
        $info = $class['classesPrivate'];
        if (empty($info)) {
            $this->error = '该课程未上课';
            return false;
        }
        if (empty($info['begin_at'])) {
            $this->error = '该课程未开课';
            return false;
        }
        $affirm = $class['classAffirm'];
        if (!empty($affirm)) {
            $this->error = '该课程已确认';
            return false;
        }
        $member = session('motion_member');
        $data['class_id'] = $class_id;
        $data['type'] = 1;
        $data['detail_id'] = $info['id'];
        $data['member_id'] = $member['id'];
        ClassesAffirm::save($data);
    }

    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validate($data)
    {
        $rule = [
            'class_at' => 'require|date',
            'begin_at' => 'require|date',
            'end_at' => 'require|date',
            'type' => 'require|in:1,2,3',
            'coach_id' => 'require|number',
            'course_id' => 'number',
            'remark' => 'max:100',
            'member_name' => 'max:11',
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
            'remark.max' => '备注最多100个字',
            'member_name.max' => '会员名称最多11个字'
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

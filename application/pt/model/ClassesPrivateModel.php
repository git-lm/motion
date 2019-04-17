<?php

namespace app\pt\model;

use think\Model;
use think\Db;
use app\motion\model\Coach;

class ClassesPrivateModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $table = 'pt_classes_group';

    /**
     * 关联上课记录
     */
    public  function  classes()
    {
        return $this->hasOne('classesModel', 'id', 'class_id');
    }
    /**
     * 关联教练
     */
    public  function  coach()
    {
        return $this->belongsTo('app\motion\model\Coach', 'coach_id', 'id');
    }
    /**
     * 获取单个私教
     */
    public function list($param)
    {
        $where['status'] = ['=', 1];
        if (!empty($param['id'])) $where['id'] = ['=', $param['id']];
        $list =  self::where($where)->find();

        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        $where['status'] = ['=', 1];
        if (!empty($param['begin_time']))  $where['class_at'] = ['>', $param['begin_time']];
        if (!empty($param['end_time']))  $where['class_at'] = ['<', $param['end_time']];
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $query = self::where($where);
        $lists =  $query->paginate($limit);
        return $lists;
    }

    /**
     * 新增私教
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $code = $this->save($param);
        if ($code) {
            return;
        } else {
            $this->error = '新增失败';
            return false;
        }
    }

    /**
     * 删除私教
     */
    public function del($id)
    {

        $cm = new ClassesModel();
        $class = $this->getClasses($id);
        if ($this->error) {
            return false;
        }
        try {
            Db::startTrans();
            $this->updateTable(array('status' => 0), $id);
            $cm->updateTable(array('status' => 0), $class['id']);
            Db::commit();
        } catch (Exception $exc) {
            Db::rollback();
            $this->error('删除失败');
        }
        return true;
    }

    public function getClasses($group_id)
    {
        $classesGroup = $this->list(array('id' => $group_id));
        if ($classesGroup['begin_at'] < date('Y-m-d H:i:s')) {
            $this->error = '该私教已开始不能操作';
            return false;
        }
        if (empty($classesGroup)) {
            $this->error = '私教记录不存在';
            return false;
        }
        $cm = new ClassesModel();
        $class = $cm->list(array('id' => $classesGroup['class_id']));
        if (empty($class)) {
            $this->error = '私教不存在';
            return false;
        }
        return $class;
    }

    /**
     * 编辑私教
     */
    public function edit($param)
    {
        $group_id = $param['group_id'];

        $class  = $this->getClasses($group_id);
        if ($this->error) {
            return false;
        }
        $class_at = $param['class_date'];
        $classParam['class_at'] = $class_at;
        $classParam['type'] = 2;
        $cm = new ClassesModel();
        $validate = $cm->validate($classParam);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $res = $cm->updateTable($classParam, $class['id']);
        if (!$res) {
            $this->error = $cm->error;
            return false;
        }

        list($start, $end) = explode('-', $param['class_time']);
        $groupParam['class_id'] = $class['id'];
        $groupParam['course_id'] = $param['course_id'];
        $groupParam['coach_id'] = $param['coach_id'];
        $groupParam['begin_at'] = $class_at . ' ' . trim($start);
        $groupParam['end_at'] = $class_at . ' ' . trim($end);
        $res = $this->updateTable($groupParam,  $group_id);
        if (!$res) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * 更新私教信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $param['update_at'] = date('Y-m-d H:i:s');
        $code =  $this->where(array('id' => $id))->update($param);
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
            'class_id' => 'require|number',
            'course_id' => 'require|number',
            'coach_id' => 'require|number',
            'begin_at' => 'require|date',
            'end_at' => 'require|date',
        ];
        $message = [
            'class_id.require' => '添加失败',
            'class_id.number' => '添加失败',
            'course_id.require' => '私教必选',
            'course_id.number' => '请正确选择私教',
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'begin_at.require' => '上课时间必选',
            'begin_at.date' => '请正确选择上课时间',
            'end_at.require' => '下课时间必选',
            'end_at.date' => '请正确选择下课时间',

        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

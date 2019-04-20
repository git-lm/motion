<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ClassesGroupModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $updateTime = 'update_at';
    protected $createTime = 'create_at';
    protected $table = 'pt_classes_group';

    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $cm = new ClassesModel();
        $class = $cm->get($param['class_id']);
        if (empty($class) || $class['end_at'] > date('Y-m-d H:i:s')) {
            $this->error = '课程未结束，不能添加人数';
            return false;
        }
        $group  = $this->where(array('class_id' => $param['class_id']))->find();

        try {
            Db::startTrans();
            if (!empty($group)) {
                $group = $this->get($group['id']);
                $code =  $group->save($param);
            } else {
                $code = $this->save($param);
            }
            if ($code) {
                $cm = new CommissionModel();
                $cm->add($class);

                if ($cm->error) {
                    Db::rollback();
                    $this->error = $cm->error;
                    return false;
                }
                Db::commit();
                return true;
            } else {
                Db::rollback();
                $this->error = '新增失败';
                return false;
            }
        } catch (Exception $exc) {
            Db::rollback();
            $this->error = '新增失败';
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
        $group = $this->get($id);
        $code =  $group->save($param);
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
            'number' => 'require|number',
        ];
        $message = [
            'class_id.require' => '请正确选择课程',
            'class_id.number' => '请正确选择课程',
            'number.require' => '上课人数必填',
            'number.number' => '请正确填写上课人数',


        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class MemberModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $table = 'pt_member';

    /**
     * 获取单个会员
     */
    public function list($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['name'])) $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        if (!empty($param['phone'])) $where[] = ['phone', 'like', '%' . $param['phone'] . '%'];
        if (!empty($param['id'])) $where[] = ['id', '=', $param['id']];
        $list =  $this->where($where)->find();

        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        if (empty($param['status'])) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', $param['status']];
        }
        if (!empty($param['name'])) $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        if (!empty($param['phone'])) $where[] = ['phone', 'like', '%' . $param['phone'] . '%'];
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $lists =  $this->where($where)->paginate($limit);
        return $lists;
    }

    /**
     * 新增会员
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $list = $this->list(array('phone' => $param['phone']));
        if (!empty($list)) {
            $this->error = '该手机号码已存在';
            return false;
        }
        $code = $this->save($param);
        if ($code) {
            return true;
        } else {
            $this->error = '新增失败';
            return false;
        }
    }

    /**
     * 编辑会员
     */
    public function edit($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $list = $this->where(array('phone' => $param['phone']))->where('id', '<>', $param['id'])->find();
        if (!empty($list)) {
            $this->error = '该手机号码已存在';
            return false;
        }
        $this->updateTable($param, $param['id']);
    }
    /**
     * 更新会员信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请正确选择操作数据';
            return false;
        }
        $member = $this->get($id);
        $code =   $member->save($param);
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
            'name' => 'require|max:5|min:2|chsAlpha',
            'phone' => 'require|mobile',
        ];
        $message = [
            'name.require' => '会员名称必填',
            'name.min' => '会员名称最少两个字',
            'name.max' => '会员名称最多五个字',
            'name.chsAlpha' => '会员名称只能汉子和字母',
            'phone.require' => '手机号码必填',
            'phone.mobile' => '手机号码格式不正确',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

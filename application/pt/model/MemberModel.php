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
        $where['status'] = ['=', 1];
        if (!empty($param['phone'])) $where['phone'] = ['=', $param['phone']];
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
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $lists =  self::where($where)->paginate($limit);
        return $lists;
    }

    /**
     * 新增会员
     */
    public function add($param)
    {
        $validate = $this->validateMember($param);
        if ($validate) {
            $this->error = $validate;
            return;
        }
        $list = $this->list(array('phone' => $param['phone']));
        if (!empty($list)) {
            $this->error = '该手机号码已存在';
            return;
        }
        $this->name = $param['name'];
        $this->phone = $param['phone'];
        $code = $this->save();
        if ($code) {
            return;
        } else {
            $this->error = '新增失败';
        }
    }

    /**
     * 编辑会员
     */
    public function edit($param)
    {
        $validate = $this->validateMember($param);
        if ($validate) {
            $this->error = $validate;
            return;
        }
        $list = self::where(array('phone' => $param['phone']))->where('id', '<>', $param['id'])->find();
        if (!empty($list)) {
            $this->error = '该手机号码已存在';
            return;
        }
        $this->updateMember($param, $param['id']);
    }
    /**
     * 更新会员信息
     */
    public function updateMember($param, $member_id)
    {
        if (empty($member_id)) {
            $this->error = '请正确选择操作数据';
        }
        $code =  $this->where(array('id' => $member_id))->update($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
        }
    }


    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validateMember($data)
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

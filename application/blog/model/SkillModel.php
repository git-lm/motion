<?php

namespace app\blog\model;

use think\Model;

class SkillModel extends Model
{
    public $error;
    protected $table = 'blog_coach_skill';
    protected $searchOrder;
    protected $userId;
    protected $coachId;
    protected $limit;
    public function setSearchOrder($searchOrder)
    {
        $this->searchOrder = $searchOrder;
        return $this;
    }
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
    public function setCoachId($coachId)
    {
        $this->coachId = $coachId;
        return $this;
    }
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    public function coach()
    {
        return $this->belongsTo('app\motion\model\Coach', 'coach_id', 'id');
    }

    public function lists($param = array())
    {
        $where[] = ['status', '=', 1];
        if (!empty($this->userId)) {
            $where[] = ['user_id', '=', $this->userId];
        }
        if (!empty($this->coachId)) {
            $where[] = ['coach_id', '=', $this->coachId];
        }
        $limit = !empty($this->limit) ? $this->limit : 10;
        $lists = $this->with('coach')->where($where)->paginate($limit);
        return $lists;
    }

    /**
     * 新增证书
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $this->skill_name = $param['skill_name'];
        $this->ratio = $param['ratio'];
        $this->coach_id = $this->coachId;
        $this->user_id = $this->userId;
        $code = $this->save();
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }

    public function edit($param)
    {
        $this->updateTable($param, $param['id']);
    }

    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $img = $this->get($id);
        $code = $img->save($param);
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
            'skill_name' => 'require|length:0 , 10',
            'ratio' => 'require|number|between:1,100',

        ];
        $message = [
            'name.require' => '专业技能必填',
            'name.length' => '专业技能必填不能超过10个字',
            'ratio.require' => '熟悉度必填',
            'ratio.number' => '熟悉度必须是数字',
            'ratio.between' => '熟悉度不能小于1大于100',

        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

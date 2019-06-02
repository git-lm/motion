<?php

namespace app\blog\model;

use think\Model;

class InfoModel extends Model
{
    public $error;
    protected $table = 'blog_coach_info';
    protected $searchOrder;
    protected $userId;
    protected $coachId;
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
    public function coach()
    {
        return $this->belongsTo('app\motion\model\Coach', 'coach_id', 'id');
    }

    function list($param = array())
    {
        if (!empty($this->userId)) {
            $where[] = ['user_id', '=', $this->userId];
        }
        $list = $this->with('coach')->where($where)->find();
        return $list;
    }

    public function getBlogUrlAttr($value, $data)
    {
        if ($data['domain']) {
            return  request()->scheme() . '://' . $data['domain'] . '.' . request()->rootDomain();
        } else {
            return '';
        }
    }

    public function info($param)
    {
        $list = $this->list();
        if (empty($list)) {
            $this->add($param);
        } else {
            $this->edit($param);
        }
    }
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $this->full_name = $param['full_name'];
        $this->email = $param['email'];
        $this->position = $param['position'];
        $this->phone = $param['phone'];
        $this->address = $param['address'];
        $this->photo = $param['photo'];
        $this->content = $param['content'];
        $this->coach_id = $this->coachId;
        $this->user_id = $this->userId;
        $this->wx_picture = $param['wx_picture'];
        $this->domain = $param['domain'];
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
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }

        $info = $this->get(array('user_id' => $this->userId));
        $info->full_name = $param['full_name'];
        $info->email = $param['email'];
        $info->position = $param['position'];
        $info->phone = $param['phone'];
        $info->address = $param['address'];
        $info->photo = $param['photo'];
        $info->content = $param['content'];
        $info->coach_id = $this->coachId;
        $info->user_id = $this->userId;
        $info->wx_picture = $param['wx_picture'];
        $info->domain = $param['domain'];
        $code = $info->save();
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
            'full_name' => 'require',
            'email' => 'email',
            'position' => 'length:0 , 30',
            'phone' => 'mobile',
            'address' => 'length:0 , 50',
            'content' => 'length:0 , 300',
            'domain' => 'require|alphaNum|length: 4 ,20',
        ];
        $message = [
            'full_name.require' => '教练姓名必填',
            'email.email' => '请正确填写邮箱地址',
            'position.length' => '职位字数不能超过30个字',
            'phone.mobile' => '请正确填写手机号码',
            'address.length' => '联系地址字数不能超过50个字',
            'content.length' => '个人介绍字数不能超过300个字',

        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

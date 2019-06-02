<?php

namespace app\blog\model;

use think\Model;

class MessageModel extends Model
{
    public $error;
    protected $table = 'blog_message';
    protected $searchOrder;
    protected $memberId;
    protected $coachId;
    protected $limit;
    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $createTime = 'create_at';
    public function setSearchOrder($searchOrder)
    {
        $this->searchOrder = $searchOrder;
        return $this;
    }
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
        return;
    }

    public function setCoachId($coachId)
    {
        $this->coachId = $coachId;
        return $this;
    }
    public function setCoachIdByDomain($domain)
    {
        $model = new InfoModel();
        $info = $model->get(array('domain' => $domain));
        if (!empty($info)) {
            $this->coachId = $info['coach_id'];
        }
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
     * 新增消息
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $ip = request()->ip();
        $message  = $this->where(array('ip' => $ip))->whereTime('create_at', 'today')->find();
        if (!empty($message)) {
            $this->error = '您今日已经提交，请勿重复提交';
            return false;
        }

        $this->nickname = $param['name'];
        $this->email = $param['email'];
        $this->phone = $param['phone'];
        $this->content = $param['content'];
        $this->member_id = $this->memberId;
        $this->coach_id = $this->coachId;
        $this->openid = session('member_openid');
        $this->ip = $ip;
        $this->agent =  request()->server('HTTP_USER_AGENT');;
        $code = $this->save();
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
            'name' => 'require|length:0 , 10',
            'email' => 'email',
            'phone' => 'require|mobile',
            'content' => 'length:0 ,200',

        ];
        $message = [
            'name.require' => '联系人必填',
            'name.length' => '联系人最大10个字',
            'email.email' => '邮箱格式不正确',
            'phone.require' => '联系人电话必填',
            'phone.mobile' => '联系人电话格式不正确',
            'content.phlengthone' => '备注最大200个字',

        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

<?php

namespace app\blog\model;

use think\Model;

class CertificateModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $table = 'blog_coach_certificate';
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

    function list($param = array()) {
        if (!empty($this->userId)) {
            $where[] = ['user_id', '=', $this->userId];
        }
        $list = $this->with('coach')->where($where)->find();
        return $list;
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

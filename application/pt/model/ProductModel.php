<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ProductModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $table = 'pt_product';

    /**
     * 获取单个会员
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
        $code = $this->save($param);
        if ($code) {
            return;
        } else {
            $this->error = '新增失败';
        }
    }

    /**
     * 编辑产品
     */
    public function edit($param)
    {
        $validate = $this->validateMember($param);
        if ($validate) {
            $this->error = $validate;
            return;
        }
        $this->updateProduct($param, $param['id']);
    }
    /**
     * 更新会员信息
     */
    public function updateProduct($param, $product_id)
    {
        if (empty($product_id)) {
            $this->error = '请选择要操作的数据！';
        }
        $code =  $this->where(array('id' => $product_id))->update($param);
        if ($code) {
            return true;
        } else {
            $this->error = '操作失败';
        }
    }

    protected $regex = ['price' => '/^[0-9]+(.[0-9]{1,2})?$/'];
    /**
     * 验证类型数据有效性
     * @param type $data 需要验证的数据
     */
    public function validateMember($data)
    {
        $rule = [
            'name' => 'require|max:5|min:2|chsAlpha',
            'price' => 'require'
        ];
        $message = [
            'name.require' => '产品名称必填',
            'name.min' => '产品名称最少两个字',
            'name.max' => '产品名称最多五个字',
            'name.chsAlpha' => '产品名称只能汉子和字母',
            'price.require' => '产品价格必填',
            // 'price.regex' => '产品价格格式不正确',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

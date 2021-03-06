<?php

namespace app\pt\model;

use think\Model;
use think\Db;

class ProductExpensesModel extends Model
{
    public $error;
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $table = 'pt_product_expenses';

    public function product()
    {
        return $this->belongsTo('productModel', 'product_id', 'id');
    }

    /**
     * 获取单个支出
     */
    public function list($param)
    {
        $where[] = ['status', '=', 1];
        if (!empty($param['id'])) $where[] = ['id', '=', $param['id']];
        if (!empty($param['product_id'])) $where[] = ['product_id', '=', $param['product_id']];
        if (!empty($param['coach_id'])) $where[] = ['coach_id', '=', $param['coach_id']];
        $list =  $this->where($where)->find();

        return $list;
    }



    /**
     * 分页获取所有数据
     */
    public function lists($param)
    {
        $where[] = ['status', '=', 1];
        if (!empty($param['coach_id']))  $where[] = ['coach_id', '=', $param['coach_id']];
        $limit = !empty($param['limit']) ? $param['limit'] : 10;
        $lists =  $this->where($where)->with('product')->order('product_id')->paginate($limit);
        return $lists;
    }

    /**
     * 新增支出
     */
    public function add($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $product_id_arr = explode(',', $param['product_id']);
        $expenses_arr = array();
        foreach ($product_id_arr as $key => $product_id) {
            $expenses_arr[$key]['product_id'] = $product_id;
            $expenses_arr[$key]['expenses'] = $param['expenses'];
            $expenses_arr[$key]['award'] = $param['award'];
            $expenses_arr[$key]['coach_id'] = $param['coach_id'];
        }
        $code = $this->saveAll($expenses_arr);
        if ($code) {
            return true;
        } else {
            $this->error = '新增失败';
            return false;
        }
    }

    /**
     * 编辑私教支出
     */
    public function edit($param)
    {
        $validate = $this->validate($param);
        if ($validate) {
            $this->error = $validate;
            return false;
        }
        $this->updateTable($param, $param['id']);
    }
    /**
     * 更新支出信息
     */
    public function updateTable($param, $id)
    {
        if (empty($id)) {
            $this->error = '请选择要操作的数据！';
            return false;
        }
        $courseExpenses = $this->get($id);
        $code = $courseExpenses->save($param);
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
            'coach_id' => 'require|number',
            'product_id' => 'require',
            'expenses' => 'require|float',
            // 'award' => 'number',
        ];
        $message = [
            'coach_id.require' => '教练必选',
            'coach_id.number' => '请正确选择教练',
            'product_id.require' => '项目必选',
            'expenses.require' => '佣金比例必填',
            'expenses.float' => '请正确填写上佣金比例',
            // 'award.number' => '请正确填写上奖励金额',
        ];
        $validate = new \think\Validate();
        $validate->rule($rule)->message($message)->check($data);
        return $validate->getError();
    }
}

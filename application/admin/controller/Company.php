<?php

namespace app\admin\controller;

use app\motion\model\Company as CompanyModel;
use controller\BasicAdmin;

/**
 * 后台首页接口
 * Class Index
 * @package app\admin\controller
 * @author Anyon
 * @date 2017/02/15 10:41
 */
class Company extends BasicAdmin
{

    public function index()
    {
        $model = new CompanyModel();
        $company = $model->get(array('status' => 1));
        if (request()->isGet()) {
            $this->assign('title', '公司基本信息');
            $this->assign('company', $company);
            return $this->fetch();
        } else {
            if (!empty($company)) {
                $company->name = input('post.name/s', '');
                $company->mobile = input('post.mobile/s', '');
                $company->email = input('post.email/s', '');
                $company->address = input('post.address/s', '');
                $company->save();
                $this->success('操作成功', '');
            } else {
                $model->name = input('post.name/s', '');
                $model->mobile = input('post.mobile/s', '');
                $model->email = input('post.email/s', '');
                $model->address = input('post.address/s', '');
                $model->save();
                $this->success('操作成功', '');
            }

        }
    }
}

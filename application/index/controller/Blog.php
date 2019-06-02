<?php

namespace app\index\controller;

use app\blog\model\ImgModel;
use app\blog\model\InfoModel;
use app\blog\model\SkillModel;
use app\index\controller\MobileBase;
use app\motion\model\Company;
use app\blog\model\MessageModel;

/**
 * 应用入口控制器
 * @author Anyon
 */
class Blog extends MobileBase
{

    public function initialize()
    {
        parent::initialize();
    }

    /**
     *  首页
     */
    public function index()
    {
        $imgModel = new ImgModel();
        $infoModel = new InfoModel();
        $skillModel = new SkillModel();
        $companyModel = new Company();
        $domain = request()->subDomain();
        $coachInfo = $infoModel->where(array('domain' => $domain))->find();
        if (empty($coachInfo)) {
            $this->redirect(request()->scheme() . '://www.' . request()->rootDomain());
        }
        $coach_user_id = $coachInfo['user_id'];
        $imgs = $imgModel->setUserId($coach_user_id)->lists();
        $info = $infoModel->setUserId($coach_user_id)->list();
        $skills = $skillModel->setUserId($coach_user_id)->lists();
        $company = $companyModel->get(array('status' => 1));
        $this->assign('imgs', $imgs);
        $this->assign('info', $info);
        $this->assign('skills', $skills);
        $this->assign('company', $company);
        return $this->fetch();
    }

    

    /**
     * 提交申请
     */
    public function message()
    {
        $member = session('motion_member');
        $domain = request()->subDomain();
        $data['phone'] = input('post.phone/s', '');
        $data['name'] = input('post.name/s', '');
        $data['email'] = input('post.email/s', '');
        $data['content'] = input('post.content/s', '');
        $model = new MessageModel();
        $model->setMemberId(!empty($member['id']) ? $member['id'] : 0);
        $model->setCoachIdByDomain($domain);
        $model->add($data);
        if (!empty($model->error)) {
            $this->error($model->error);
        } else {
            $this->success('提交成功，一个工作日内客服将与你联系，请等待！', '');
        }
    }
}

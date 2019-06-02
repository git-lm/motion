<?php
namespace app\blog\controller;

use app\blog\model\ImgModel;
use app\blog\model\InfoModel;
use app\blog\model\SkillModel;
use app\motion\model\Coach;
use controller\BasicAdmin;
use Endroid\QrCode\QrCode;

class Info extends BasicAdmin
{
    public function initialize()
    {
        $user = session('user');
        $user['id'] = 10002;
        $coachModel = new Coach();
        $coachWhere['u_id'] = $user['id'];
        $coach = $coachModel->get_coach($coachWhere);
        if (empty($coach)) {
            $this->error('该账号无教练信息');
        }
        $this->user = $user;
        $this->user_id = $user['id'];
        $this->coach = $coach;
        $this->coach_id = $coach['id'];
    }

    /**
     * 添加或修改基础信息
     */
    public function info()
    {
        if (request()->isGet()) {
            $this->assign('title', '基本信息');
            $infoModel = new InfoModel();
            $infoModel->setUserId($this->user_id);
            $info = $infoModel->list();
            if (!empty($info['domain'])) {
                $info['blogUrl'] =  request()->scheme() . '://' . $info['domain'] . '.' . request()->rootDomain();
            }
            $this->assign('info', $info);
            return $this->fetch();
        }
        $post = input('post.');
        $infoModel = new InfoModel();
        $infoModel->setUserId($this->user_id)->setCoachId($this->coach_id)->info($post);
        if (!empty($infoModel->error)) {
            $this->error($infoModel->error);
        } else {
            $this->success('操作成功', '');
        }
    }
    /**
     * 展示图片
     */
    public function Img()
    {
        $this->assign('title', '展示图片');
        return $this->fetch();
    }

    /**
     * 获取图片
     */
    public function getImg()
    {
        $limit = input('get.limit/d', 0);
        $imgModel = new ImgModel();
        $lists = $imgModel->setUserId($this->user_id)
            ->setCoachId($this->coach_id)
            ->setLimit($limit)
            ->lists();
        echo $this->tableReturn($lists->all(), $lists->total());
    }
    /**
     * 新增图片
     */
    public function addImg()
    {
        if (request()->isGet()) {
            return $this->fetch();
        } else {
            $post = input('post.');
            $imgModel = new ImgModel();
            $imgModel->setUserId($this->user_id)
                ->setCoachId($this->coach_id)
                ->add($post);
            if ($imgModel->error) {
                $this->error($imgModel->error);
            } else {
                $this->success('新增成功', '');
            }
        }
    }
    /**
     * 删除图片
     */
    public function delImg()
    {
        $id = input('post.id/d', 0);
        $imgModel = new ImgModel();
        $param['id'] = $id;
        $param['status'] = 0;
        $imgModel->edit($param);
        if ($imgModel->error) {
            $this->error($imgModel->error);
        } else {
            $this->success('删除成功', '');
        }
    }

    /**
     * 专业技能
     */
    public function skill()
    {
        $this->assign('title', '专业技能');
        return $this->fetch();
    }
    /**
     * 获取专业技能
     */
    public function getSkill()
    {
        $limit = input('get.limit/d', 0);
        $skillModel = new SkillModel();
        $lists = $skillModel->setUserId($this->user_id)
            ->setCoachId($this->coach_id)
            ->setLimit($limit)
            ->lists();
        echo $this->tableReturn($lists->all(), $lists->total());
    }

    /**
     * 新增证书
     */
    public function addSkill()
    {
        if (request()->isGet()) {
            return $this->fetch();
        } else {
            $post = input('post.');
            $skillModel = new SkillModel();
            $skillModel->setUserId($this->user_id)
                ->setCoachId($this->coach_id)
                ->add($post);
            if ($skillModel->error) {
                $this->error($skillModel->error);
            } else {
                $this->success('新增成功', '');
            }
        }
    }
    /**
     * 删除证书
     */
    public function delSkill()
    {
        $id = input('post.id/d', 0);
        $skillModel = new SkillModel();
        $param['id'] = $id;
        $param['status'] = 0;
        $skillModel->edit($param);
        if ($skillModel->error) {
            $this->error($skillModel->error);
        } else {
            $this->success('删除成功', '');
        }
    }

    public function createQrCode()
    {
        $coach_id = input('get.coach_id/d', 0);
        $InfoModel = new InfoModel();
        $info =  $InfoModel->get(array('coach_id' => $coach_id));
        $site_name = sysconf('site_name');
        if (!empty($info) && !empty($info['domain'])) {
            $blogUrl =  request()->scheme() . '://' . $info['domain'] . '.' . request()->rootDomain();
            $qrCode = new QrCode();
       
            $qrCode->setText($blogUrl)
                ->setSize(300)
                ->setPadding(10)
                ->setErrorCorrection('high')
                ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
                ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
                ->setLabel($site_name)
                ->setLabelFontSize(16)
                ->setImageType($qrCode::IMAGE_TYPE_PNG);
            $qrCode->render();
        }
    }
}

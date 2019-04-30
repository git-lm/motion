<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\ClassesModel;
use app\pt\model\CourseModel;
use app\pt\model\CoachModel;
use app\pt\model\ClassesGroupModel;
use app\pt\model\ClassesPrivateModel;

class Classes extends BasicAdmin
{
    public function initialize()
    {
        $this->csm = new ClassesModel();
    }

    /**
     * 
     */
    public function index()
    {
        $this->assign('title', '课程列表');
        return $this->fetch();
    }
    public function get_lists()
    {
        $get = input('get.');
        $lists = $this->csm->lists($get);
        echo $this->tableReturn($lists->all(), $lists->total());
    }

    /**
     * 上课人数
     */
    public function classNumber()
    {
        if (request()->isGet()) {
            $class_id = input('get.class_id/d');
            if (empty($class_id)) {
                $this->error('请正确选择课程');
            }
            $list = ClassesModel::with('classesGroup')->where(array('id' => $class_id, 'status' => 1))->find();
            $this->assign('list', $list);
            return $this->fetch();
        } else {
            $post = input('post.');
            $cgm = new ClassesGroupModel();
            $cgm->add($post);
            if ($cgm->error) {
                $this->error($cgm->error);
            } else {
                $this->success('修改成功', '');
            }
        }
    }
    /**
     * 上课会员
     */
    public function classPeople()
    {
        if (request()->isGet()) {
            $class_id = input('get.class_id/d');
            if (empty($class_id)) {
                $this->error('请正确选择课程');
            }
            $list = ClassesModel::with('classesPrivate')->where(array('id' => $class_id, 'status' => 1))->find();
            $this->assign('list', $list);
            //获取教练下的所有有效会员
            $cm = new CoachModel();
            $cm->setCoachId($list['coach_id']);
            $members = $cm->coachProductMember();
            $this->assign('members', $members);
            return $this->fetch();
        } else {
            $post = input('post.');
            $cpm = new ClassesPrivateModel();
            $cpm->add($post);
            if ($cpm->error) {
                $this->error($cpm->error);
            } else {
                $this->success('操作成功', '');
            }
        }
    }
    /**
     * 渲染课程记录
     */
    public function schedule()
    {
        $this->assign('title', '课程记录');
        $post = input('post.');
        return $this->fetch();
    }
    //获取首页日历信息
    public function get_calendar_lists()
    {
        $startDate = input('post.startStr/s', date('Y-m-01'));
        $endDate = input('post.endStr/s', date('Y-m-31'));
        $classes = $this->csm::withJoin(['coach', 'course'] ,'left')
            ->where(array('classes_model.status' => 1))
            ->whereTime('class_at', 'between', [$startDate, $endDate])
            ->select();
            
        return json($classes);
    }

    //添加团课信息
    public function addGroup()
    {
        if (request()->isGet()) {
            //获取教练信息
            $cm = new CoachModel();
            $coaches = $cm->where(array('status' => 1))->select();
            //获取团课课程信息
            $cm = new CourseModel();
            $courses = $cm::where(array('status' => 1))->select();
            $this->assign('coaches', $coaches);
            $this->assign('courses', $courses);
            return $this->fetch();
        } else {
            //获取课程信息 先添加
            $post = input('post.');
            $post['class_date'] = input('post.class_date/s', '');
            $post['class_time'] =  input('post.class_time/s', '');
            $post['type'] = 2;
            $this->csm->add($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('添加成功', '');
            }
        }
    }

    public function editGroup()
    {
        if (request()->isGet()) {
            $class_id = input('class_id/d');
            if (empty($class_id)) {
                $this->error('请正确选择课程');
            };
            //获取教练信息
            $cm = new CoachModel();
            $coaches = $cm->where(array('status' => 1))->select();
            //获取团课列表信息
            $cm = new CourseModel();
            $courses = $cm::where(array('status' => 1))->select();
            $this->assign('coaches', $coaches);
            $this->assign('courses', $courses);
            //获取已添加的团课信息

            $class = $this->csm::with(['coach', 'course'])->where(array('status' => 1, 'id' => $class_id))->find();
            $this->assign('list', $class);
            return $this->fetch();
        } else {
            $post = input('post.');
            $post['class_date'] = input('post.class_date/s', '');
            $post['class_time'] =  input('post.class_time/s', '');
            $post['type'] = 2;
            $this->csm->edit($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('修改成功', '');
            }
        }
    }



    /**
     * 删除团课记录 并删除课程信息
     */
    public function del()
    {
        $class_id = input('post.id/d');
        if (empty($class_id)) {
            $this->error('请选择要删除的课程');
        }
        $this->csm->updateTable(array('status' => 0), $class_id);
        if ($this->csm->error) {
            $this->error($this->csm->error);
        } else {
            return ['code' => 1, 'msg' => '删除成功', 'url' => ''];
        }

        exit;
    }

    public function addPrivate()
    {
        if (request()->isGet()) {
            //获取教练信息
            $user = session('user');
            $cm = new CoachModel();
            if (!empty($user['is_admin'])) {
                $coaches = $cm->where(array('status' => 1))->select();
                $this->assign('coaches', $coaches);
            } else {
                $coach = $cm->where(array('u_id' => $user['id']))->find();
                if (empty($coach)) {
                    $this->error('无教练信息');
                }
                $this->assign('coach', $coach);
            }
            return $this->fetch();
        } else {
            //获取课程信息 先添加
            $post = input('post.');
            $post['class_date'] = input('post.class_date/s', '');
            $post['class_time'] =  input('post.class_time/s', '');
            $post['type'] = 1;
            $this->csm->add($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('添加成功', '');
            }
        }
    }

    public function editPrivate()
    {
        if (request()->isGet()) {
            $class_id = input('class_id/d');
            if (empty($class_id)) {
                $this->error('请正确选择课程');
            };
            //获取教练信息
            $user = session('user');
            $cm = new CoachModel();
            if (!empty($user['is_admin'])) {
                $coaches = $cm->where(array('status' => 1))->select();
                $this->assign('coaches', $coaches);
            } else {
                $coach = $cm->where(array('u_id' => $user['id']))->find();
                if (empty($coach)) {
                    $this->error('无教练信息');
                }
                $this->assign('cocah', $coach);
            }
            $class = $this->csm::with(['coach', 'course'])->where(array('status' => 1, 'id' => $class_id))->find();
            $this->assign('list', $class);
            return $this->fetch();
        } else {
            $post = input('post.');
            $post['class_date'] = input('post.class_date/s', '');
            $post['class_time'] =  input('post.class_time/s', '');
            $post['type'] = 1;
            $this->csm->edit($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('修改成功', '');
            }
        }
    }
}

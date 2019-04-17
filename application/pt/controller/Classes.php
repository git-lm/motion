<?php

namespace app\pt\controller;

use controller\BasicAdmin;
use app\pt\model\ClassesModel;
use app\pt\model\CourseModel;
use app\pt\model\ClassesGroupModel;

class Classes extends BasicAdmin
{
    public function initialize()
    {
        $this->csm = new ClassesModel();
    }
    /**
     * 渲染课程记录
     */
    public function index()
    {

        $this->assign('title', '课程记录');
        $post = input('post.');
        return $this->fetch();
    }
    //获取首页日历信息
    public function get_group_lists()
    {
        $startDate = input('post.startStr/s', date('Y-m-01'));
        $endDate = input('post.endStr/s', date('Y-m-31'));
        $cm = new ClassesModel();
        $cgm  = new ClassesGroupModel();
        $classes =  $cm::with(['coach'])->where(array('status' => 1))->whereTime('class_at', 'between', [$startDate, $endDate])->select();
        foreach ($classes as &$class) {
            if ($class['type'] == 2) {
                $class['group'] = $cgm->list(array('class_id' => $class['id']));
            }
        }
        return json($classes);
    }
    //添加团课信息
    public function addGroup()
    {
        if (request()->isGet()) {
            //获取教练信息
            $coachModel = new \app\motion\model\Coach;
            $coaches = $coachModel->get_coachs();
            //获取团课课程信息
            $cm = new CourseModel();
            $courses = $cm::where(array('status' => 1))->select();
            $this->assign('coaches', $coaches);
            $this->assign('courses', $courses);
            return $this->fetch();
        } else {
            try {
                db()->startTrans();
                //获取课程信息 先添加
                $post = input('post.');
                $class_at = input('post.class_date/s');
                $classParam['class_at'] = $class_at;
                $classParam['type'] =  input('post.type/d');
                $classParam['coach_id'] =  input('post.coach_id/d');
                $this->csm->add($classParam);
                if ($this->csm->error) {
                    db()->rollback();
                    $this->error($this->csm->error);
                }
                //课程信息 添加成功后 获取自增ID
                $class_id = $this->csm->id;
                list($start, $end) = explode('-', input('post.class_time/s', ''));
                $groupParam['class_id'] = $class_id;
                $groupParam['course_id'] = input('post.course_id/d');
                $groupParam['coach_id'] = input('post.coach_id/d');
                $groupParam['begin_at'] = $class_at . ' ' . trim($start);
                $groupParam['end_at'] = $class_at . ' ' . trim($end);
                $cgm = new ClassesGroupModel();
                //添加团课信息
                $cgm->add($groupParam);
                if ($cgm->error) {
                    db()->rollback();
                    $this->error($cgm->error);
                }
                db()->commit();
                return ['code' => 1, 'msg' => '添加成功', 'url' => ''];
            } catch (Exception $exc) {
                db()->rollback();
                $this->error('添加失败');
            }
        }
    }

    public function editGroup()
    {
        if (request()->isGet()) {
            $group_id = input('group_id/d');
            if (empty($group_id)) {
                $this->error('请正确选择课程');
            };
            //获取教练信息
            $coachModel = new \app\motion\model\Coach;
            $coaches = $coachModel->get_coachs();
            //获取团课列表信息
            $cm = new CourseModel();
            $courses = $cm::where(array('status' => 1))->select();
            $this->assign('coaches', $coaches);
            $this->assign('courses', $courses);
            //获取已添加的团课信息
            $cgm = new ClassesGroupModel();
            $classesGroup =  $cgm::with(['classes', 'coach'])->where(array('status' => 1, 'id' => $group_id))->find();
            $this->assign('list', $classesGroup);
            return $this->fetch();
        } else {

            try {
                db()->startTrans();
                $post = input('post.');
                $post['group_id'] = input('post.group_id/d');
                $cgm = new ClassesGroupModel();
                $cgm->edit($post);
                if ($cgm->error) {
                    db()->rollback();
                    $this->error($cgm->error);
                } else {
                    db()->rollback();
                    return ['code' => 1, 'msg' => '修改成功', 'url' => ''];
                }
            } catch (Exception $exc) {
                db()->rollback();
                $this->error('修改失败');
            }
        }
    }



    /**
     * 删除团课记录 并删除课程信息
     */
    public function delGroup()
    {
        $group_id = input('post.id/d');
        if (empty($group_id)) {
            $this->error('请选择要删除的课程');
        }
        $cgm = new ClassesGroupModel();
        $cgm->del($group_id);
        if ($cgm->error) {
            $this->error($cgm->error);
        } else {
            return ['code' => 1, 'msg' => '删除成功', 'url' => ''];
        }

        exit;
    }
}

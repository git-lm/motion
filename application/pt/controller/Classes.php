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

    public function get_group_lists()
    {
        $startDate = input('post.startStr/s', date('Y-m-01'));
        $endDate = input('post.endStr/s', date('Y-m-31'));
        $cgm = new ClassesGroupModel();
        $classesGroup =  $cgm::with(['classes', 'coach', 'course'])->where(array('status' => 1))->whereTime('begin_at', 'between', [$startDate, $endDate])->select();
        return json($classesGroup);
    }

    public function addGroup()
    {
        if (request()->isGet()) {
            $coachModel = new \app\motion\model\Coach;
            $where['c.status'] = 1;
            $oders['c.create_time'] = 'desc';
            $coaches = $coachModel->get_coachs($where, $oders);
            $cm = new CourseModel();
            $courses = $cm::where(array('status' => 1))->select();
            $this->assign('coaches', $coaches);
            $this->assign('courses', $courses);
            return $this->fetch();
        } else {
            try {
                db()->startTrans();
                $post = input('post.');
                $class_at = input('post.class_date/s');
                $classParam['class_at'] = $class_at;
                $classParam['type'] =  input('post.type/d');
                $this->csm->add($classParam);
                if ($this->csm->error) {
                    db()->rollback();
                    $this->error($this->csm->error);
                }
                $class_id = $this->csm->id;
                list($start, $end) = explode('-', input('post.class_time/s', ''));
                $groupParam['class_id'] = $class_id;
                $groupParam['course_id'] = input('post.course_id/d');
                $groupParam['coach_id'] = input('post.coach_id/d');
                $groupParam['begin_at'] = $class_at . ' ' . trim($start);
                $groupParam['end_at'] = $class_at . ' ' . trim($end);
                $cgm = new ClassesGroupModel();
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
        $group_id = input('group_id/d');
        dump($group_id);
    }
}

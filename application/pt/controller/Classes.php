<?php

namespace app\pt\controller;

use app\pt\model\ClassesGroupModel;
use app\pt\model\ClassesModel;
use app\pt\model\ClassesPrivateModel;
use app\pt\model\CoachModel;
use app\pt\model\CourseModel;
use controller\BasicAdmin;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\Db;

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
        $this->csm->setOrder(array('begin_at' => 'desc'));
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
    /**
     * 渲染课程记录
     */
    public function my_schedule()
    {
        $this->assign('title', '课程记录');
        $post = input('post.');
        $this->assign('type', 1);
        return $this->fetch('schedule');
    }

    //获取首页日历信息
    public function get_calendar_lists()
    {
        $user = session('user');
        $type = input('post.type/d', 1);
        $startDate = input('post.startStr/s', date('Y-m-01'));
        $endDate = input('post.endStr/s', date('Y-m-31'));
        $query = $this->csm::withJoin(['coach', 'course'], 'left')
            ->where(array('classes_model.status' => 1))
            ->whereTime('class_at', 'between', [$startDate, $endDate]);
        // if ($user['username'] != 'admin' && $user['username'] != 'superadmin' && $user['username'] != 'chenyuan') {
        if ($type) {
            $coach = CoachModel::get(array('u_id' => $user['id']));
            if (empty($coach)) {
                return json([]);
            }
            $query->where(array('coach_id' => $coach['id']));
        }
        $classes = $query->select();
        foreach ($classes as &$value) {
            if ($value['type'] == 3) {
                $value['classesOther'];
            }
        }
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
            $post['class_time'] = input('post.class_time/s', '');
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
            $post['class_time'] = input('post.class_time/s', '');
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
            $post['class_time'] = input('post.class_time/s', '');
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
            $post['class_time'] = input('post.class_time/s', '');
            $post['type'] = 1;
            $this->csm->edit($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('修改成功', '');
            }
        }
    }

    //添加其他信息
    public function addOther()
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
            $post['class_time'] = input('post.class_time/s', '');
            $post['type'] = 3;
            $this->csm->add($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('添加成功', '');
            }
        }
    }

    public function editOther()
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
            $class = $this->csm::with(['coach', 'course', 'classesOther'])->where(array('status' => 1, 'id' => $class_id))->find();
            $this->assign('list', $class);
            return $this->fetch();
        } else {
            $post = input('post.');
            $post['class_date'] = input('post.class_date/s', '');
            $post['class_time'] = input('post.class_time/s', '');
            $post['type'] = 3;
            $this->csm->edit($post);
            if ($this->csm->error) {
                $this->error($this->csm->error);
            } else {
                $this->success('修改成功', '');
            }
        }
    }

    /**
     * 上传日程
     * @param $data 数据
     * @param $id motion_batch_lesson 主键ID
     */
    public function upload()
    {
        if (request()->post()) {
            $file = input('file/s');
            $type = input('type/d', 1); //1 是提交 2是验证
            $req = $this->analysisExcel($file);
            if ($type == 2) {
                return $req;
            } else {
                if ($req['code'] != 1) {
                    $this->error($req['msg']);
                }
                // $mid = input('mid/d');
                // $member = $this->check_member_data($mid);
                $data = $req['data'];
                $file_url = $file;
                Db::startTrans();
                $courseModel = new CourseModel();
                $coachMode = new CoachModel();
                $classesModel = new ClassesModel();
                try {
                    foreach ($data as $key => $value) {
                        //上课日期
                        $param['class_date'] = !empty($value['date']) ? $value['date'] : '';
                        $detail = $value['detail'];
                        foreach ($detail as $k => $v) {
                            //上课时间
                            $param['class_time'] = !empty($v['time']) ? $v['time'] : '';
                            //上课团课课程
                            $course_name = $v['course'];
                            if ($course_name == '私教') {
                                $param['course_id'] = 0;
                                $param['type'] = 1;
                            } else {
                                $course = $courseModel->where(array('name' => $course_name, 'status' => 1))->find();
                                if (empty($course) || empty($course['id'])) {
                                    Db::rollback();
                                    return ['code' => 0, 'msg' => "无此{$course_name}团课课程"];
                                    $this->error("无此{$course_name}团课课程");
                                }
                                $param['course_id'] = $course['id'];
                                $param['type'] = 2;
                            }

                            //上课教练
                            $coach_name = $v['coach'];
                            $coach = $coachMode->where(array('name' => $coach_name, 'status' => 1))->find();
                            if (empty($coach) || empty($coach['id'])) {
                                Db::rollback();
                                return ['code' => 0, 'msg' => "无此{$coach_name}教练"];
                                $this->error("无此{$coach_name}教练");
                            }
                            $param['coach_id'] = $coach['id'];
                            $param['member_name'] = $v['member_name'];
                            $classesModel->add($param);
                            if ($classesModel->error) {
                                Db::rollback();
                                return ['code' => 0, 'msg' => $classesModel->error];
                                $this->error($classesModel->error);
                            }
                        }
                    }

                    Db::commit();
                    return ['code' => 1, 'msg' => '添加成功'];
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->error('添加失败');
                }
            }
            return;
        }
        return $this->fetch();
    }
    /**
     * 解析上传的计划
     * @param $url 文件地址
     * @param array  解析后的数组
     */

    public function analysisExcel($url = '')
    {
        //解析url
        $parse_url = parse_url($url);
        $path = !empty($parse_url['path']) ? '.' . $parse_url['path'] : '';

        if (file_exists($path)) {
            $inputFileName = $path;
            $inputFileType = 'Xlsx';
            $reader = IOFactory::createReader($inputFileType);

            $spreadsheet = $reader->load($inputFileName);
            // $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $worksheet = $spreadsheet->getActiveSheet(); //获取工作表
            $sheetData = $worksheet->toArray(null, true, true, true);
            $highestRow = $worksheet->getHighestRow(); // 总行数
            $highestColumn = $worksheet->getHighestColumn(); // 总列数
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); //把列的字母转成数字

            $end_title = (string) $worksheet->getCellByColumnAndRow(1, $highestRow)->getValue(); //获取最后一行的文字

            if ('结束日程' != $end_title) {
                unlink($path);
                return ['code' => 0, 'msg' => '日程表错误，无--结束日程--标签，请重新下载', 'data' => array()];
            }
            $sheet = array();
            for ($column = 1; $column <= $highestColumnIndex - 1; $column++) { //$highestRow
                //每次循环 读取四列 生成一个日程
                $columnNext = $column + 3;
                //定义一个日程数组
                $valArr = array();
                //获取日程时间
                $date = (string) $worksheet->getCellByColumnAndRow($column, 2)->getValue();
                if (empty($date)) {
                    break;
                }
                $valArr['date'] = $date;
                $detail = array(); //日程详情
                for ($row = 3; $row <= $highestRow; $row++) {
                    $end = (string) $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                    if ($end == '结束日程') {
                        break;
                    }
                    $time = (string) $worksheet->getCellByColumnAndRow($column, $row)->getValue();
                    if (empty($time)) {
                        continue;
                    }
                    $course = (string) $worksheet->getCellByColumnAndRow($column + 1, $row)->getValue();
                    $coach = (string) $worksheet->getCellByColumnAndRow($column + 2, $row)->getValue();
                    $member_name = (string) $worksheet->getCellByColumnAndRow($column + 3, $row)->getValue();
                    $detail['time'] = $time;
                    $detail['course'] = $course;
                    $detail['coach'] = $coach;
                    $detail['member_name'] = $member_name;
                    $valArr['detail'][] = $detail;
                }

                $sheet[] = $valArr;
                $column = $columnNext;
            }
            if (empty($sheet)) {
                unlink($path);
                return ['code' => 0, 'msg' => '该文件日程为空'];
            } else {
                return ['code' => 1, 'msg' => '', 'data' => $sheet];
            }
        } else {
            return ['code' => 0, 'msg' => '文件不存在', 'data' => array()];
        }
    }
}

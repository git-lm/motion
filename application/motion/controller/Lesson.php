<?php

namespace app\motion\controller;

use app\motion\model\Coach as coachModel;
use app\motion\model\Course as courseModel;
use app\motion\model\Lesson as lessonModel;
use app\motion\model\Member as memberModel;
use app\motion\model\Motion as motionModel;
use controller\BasicAdmin;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\db;
use think\facade\Request;
use app\motion\model\MemberType;
use app\motion\model\Template;
use app\motion\model\TemplateLesson;
use app\motion\model\TemplateCourse;

class Lesson extends BasicAdmin
{

    public function initialize()
    {
        $this->lessonModel = new lessonModel();
        $this->memberModel = new memberModel();
        $this->courseModel = new courseModel();
        $this->coachModel = new coachModel();
        $this->motionModel = new motionModel();
    }

    /**
     * 渲染会员首页
     */
    public function index()
    {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 我的会员
     */
    public function my()
    {
        $this->assign('title', '会员列表');
        return $this->fetch();
    }

    /**
     * 获取所有会员信息
     */
    public function get_all_lists()
    {
        echo $this->get_member_lists();
    }

    /**
     * 获取我的会员信息
     */
    public function get_my_lists()
    {
        $uid = session('user.id');
        //获取所属uid 的教练
        $where['u_id'] = $uid;
        $where['status'] = 1;
        $coach = $this->coachModel->get_coach($where);
        if (empty($coach)) {
            $this->error('该账号未绑定教练');
        }
        $lwhere[] = ['c_id', '=', $coach['id']];
        echo $this->get_member_lists($lwhere);
    }

    /**
     * 获取会员列表信息
     */
    public function get_member_lists($where = [])
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $cname = request()->has('cname', 'get') ? request()->get('cname/s') : '';
        $expire_time = request()->has('expire_time', 'get') ? request()->get('expire_time/s') : '';
        if ($name) {
            $where[] = ['m.name', 'like', '%' . $name . '%'];
        }
        if ($cname) {
            $where[] = ['c.name', 'like', '%' . $cname . '%'];
        }
        if ($expire_time) {
            $et = explode(' - ', $expire_time);
            if (!empty($et[0]) && validate()->isDate($et[0])) {
                $begin_time = strtotime($et[0] . ' 00:00:00');
                $where[] = ['expire_time', '>=', $begin_time];
            }
            if (!empty($et[1]) && validate()->isDate($et[1])) {
                $end_time = strtotime($et[1] . ' 23:59:59');
                $where[] = ['expire_time', '<=', $end_time];
            }
        }
        $where[] = ['m.status', '<>', 0];
        $order['c.id'] = 'desc';
        $order['m.create_time'] = 'desc';
        $lists = $this->memberModel->get_members($where, $order, $page, $limit);
        $count = count($this->memberModel->get_members($where));
        return $this->tableReturn($lists, $count);
    }

    /**
     * 查看排课信息
     */
    public function arrange()
    {
        $this->assign('title', '计划列表');
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请选择会员');
        }
        //验证会员是否存在
        $this->check_member_data($id);
        //获取会员信息
        $where['m_id'] = $id;
        $memberinfo = $this->memberModel->get_member_info($where);
        $this->assign('memberinfo', $memberinfo);
        $this->assign('mid', $id);
        return $this->fetch();
    }

    /**
     * 获取排课信息
     */
    public function get_arrange_lists()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $class_time = request()->has('class_time', 'get') ? request()->get('class_time/s') : '';
        if (!$id) {
            $this->error('请选择会员');
        }
        if ($name) {
            $where[] = ['l.name', 'like', '%' . $name . '%'];
        }
        if ($class_time) {
            $ct = explode(' - ', $class_time);
            if (!empty($ct[0]) && validate()->isDate($ct[0])) {
                $begin_time = strtotime($ct[0] . ' 00:00:00');
                $where[] = ['class_time', '>=', $begin_time];
            }
            if (!empty($ct[1]) && validate()->isDate($ct[1])) {
                $end_time = strtotime($ct[1] . ' 23:59:59');
                $where[] = ['class_time', '<=', $end_time];
            }
        }

        //验证会员是否存在
        $this->check_member_data($id);
        //获取排课信息
        $where[] = ['l.m_id', '=', $id];
        $where[] = ['l.status', '=', 1];
        $order['l.class_time'] = 'desc';
        $lists = $this->lessonModel->get_arrange_lists($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_arrange_lists($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function add()
    {
        //获取获取ID
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择会员');
        }
        //获取该会员的信息
        $this->check_member_time($mid);
        $this->assign('mid', $mid);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 新增会员动作信息
     */
    public function add_info()
    {

        $colldown_mids = request()->has('colldown_mids', 'post') ? request()->post('colldown_mids/s') : null;
        $colldown = request()->has('colldown', 'post') ? request()->post('colldown/s') : null;
        $warmup_mids = request()->has('warmup_mids', 'post') ? request()->post('warmup_mids/s') : null;
        $warmup = request()->has('warmup', 'post') ? request()->post('warmup/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $class_time = request()->has('class_time', 'post') ? request()->post('class_time/s') : null;
        $mids = request()->has('mid', 'post') ? request()->post('mid/s') : 0;
        $is_batch = request()->has('is_batch', 'post') ? request()->post('is_batch/d') : 0;
        $mid_array = explode(',', $mids);
        if (!$mids) {
            $this->error('请正确选择会员');
        }
        if (!$name) {
            $this->error('请正确填写名称');
        }
        if (!$class_time) {
            $this->error('请正确选择上课时间');
        }
        foreach ($mid_array as $mid) {
            $member = $this->check_member_data($mid);
        }

        //获取该会员时间的信息
        foreach ($mid_array as $mid) {
            $this->check_member_time($mid);
        }

        //验证数据有效性
        $data['name'] = $name; // 课程名称
        $data['warmup'] = $warmup; //热身语
        $data['warmup_mids'] = $warmup_mids; //热身视频
        $data['colldown'] = $colldown; //冷身语
        $data['colldown_mids'] = $colldown_mids; //冷身视频
        $data['class_time'] = $class_time;

        $validate = $this->lessonModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['class_time'] = strtotime($class_time . ' 23:59:59');
        if (!empty($is_batch)) {
            $uid = session('user.id');
            $data['u_id'] = $uid;
            $data['member_ids'] = $mids;
            $code = $this->lessonModel->add_batch_lesson($data);
        } else {
            $data['m_id'] = $mids;
            $data['coach_id'] = $member['c_id'];
            $code = $this->lessonModel->add($data);
        }
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 渲染编辑窗口
     */
    public function edit()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $list = $this->check_arrange_data($id);
        $this->assign('list', $list);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 编辑会员动作信息
     */
    public function edit_info()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $colldown_mids = request()->has('colldown_mids', 'post') ? request()->post('colldown_mids/s') : null;
        $colldown = request()->has('colldown', 'post') ? request()->post('colldown/s') : null;
        $warmup_mids = request()->has('warmup_mids', 'post') ? request()->post('warmup_mids/s') : null;
        $warmup = request()->has('warmup', 'post') ? request()->post('warmup/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $class_time = request()->has('class_time', 'post') ? request()->post('class_time/s') : null;
        $is_batch = request()->has('is_batch', 'post') ? request()->post('is_batch/d') : 0;
        $mids = request()->has('mid', 'post') ? request()->post('mid/s') : 0;
        $mid_array = explode(',', $mids);
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }
        if (!$name) {
            $this->error('请填写动作名称');
        }
        if (!$class_time) {
            $this->error('请正确选择上课时间');
        }
        foreach ($mid_array as $mid) {
            $member = $this->check_member_data($mid);
        }
        //获取该会员时间的信息
        foreach ($mid_array as $mid) {
            $this->check_member_time($mid);
        }
        if (!$is_batch) {
            //判断排课信息
            $list = $this->check_arrange_data($id);
        }
        //验证数据有效性
        $data['name'] = $name; // 课程名称
        $data['warmup'] = $warmup; //热身语
        $data['warmup_mids'] = $warmup_mids; //热身视频
        $data['colldown'] = $colldown; //冷身语
        $data['colldown_mids'] = $colldown_mids; //冷身视频
        $data['class_time'] = $class_time;
        $validate = $this->lessonModel->validate($data);
        if ($validate) {
            $this->error($validate);
        }
        $data['class_time'] = strtotime($class_time . ' 23:59:59');

        if ($is_batch) {
            $data['member_ids'] = $mids;
            $code = $this->edit_batch_lesson($data, $id);
        } else {
            $where['id'] = $id;
            $code = $this->lessonModel->edit($data, $where);
        }

        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 上传计划
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
                try {
                    $bank = db()->table('motion_bank')->where(array('status' => 1))->column('name', 'id');
                    foreach ($data as $key => $value) {
                        $mid = $key;
                        $member = $this->check_member_data($mid);
                        foreach ($value as $k => $vo) {
                            $lesson['class_time'] = strtotime(validate()->isDate($vo['date']) ? $vo['date'] : date('Y-m-d')); //上课时间
                            $lesson['name'] = !empty($vo['name']) ? $vo['name'] : ''; //计划名称
                            $lesson['warmup'] = !empty($vo['warmUp']) ? $vo['warmUp'] : ''; //warmup
                            $lesson['colldown'] = !empty($vo['collDown']) ? $vo['collDown'] : ''; //colldown
                            $lesson['m_id'] = $mid;
                            $lesson['coach_id'] = $member['c_id'];
                            $lesson['file_url'] = $file_url;

                            $warmUpVideo = !empty($vo['warmUpVideo']) ? explode(',', str_replace('，', ',', $vo['warmUpVideo'])) : []; //video
                            $collDownVideo = !empty($vo['collDownVideo']) ? explode(',', str_replace('，', ',', $vo['collDownVideo'])) : []; //video
                            $warmup_mids = '';
                            foreach ($warmUpVideo as $v) {
                                $bank_id = array_search($v, $bank);
                                if ($bank_id) {
                                    $warmup_mids .= $bank_id . ',';
                                }
                            }
                            $lesson['warmup_mids'] = trim($warmup_mids, ',');
                            $colldown_mids = '';
                            foreach ($collDownVideo as $k) {
                                $bank_id = array_search($k, $bank);
                                if ($bank_id) {
                                    $colldown_mids .= $bank_id . ',';
                                }
                            }
                            $lesson['colldown_mids'] = trim($colldown_mids, ',');
                            $lesson_id = $this->lessonModel->add($lesson);
                            if (!$lesson_id) {
                                continue;
                            }
                            foreach ($vo['detail'] as $k => $v) {
                                $little['num'] = !empty($v['num']) ? $v['num'] : ''; //动作编号
                                $name_remark = trim($v['explain']);
                                $name = substr($name_remark, 0, stripos($name_remark, "\n"));
                                $remark = substr($name_remark, stripos($name_remark, "\n"));
                                $little['name'] = !empty($name) ? $name : '';
                                $little['remark'] = !empty($remark) ? $remark : '';
                                $littleVideo = !empty($v['video']) ? explode(',', str_replace('，', ',', $v['video'])) : []; //video
                                $m_ids = '';
                                foreach ($littleVideo as $k) {
                                    $bank_id = array_search($k, $bank);
                                    if ($bank_id) {
                                        $m_ids .= $bank_id . ',';
                                    }
                                }
                                $little['m_ids'] = trim($m_ids, ',');
                                $little['l_id'] = $lesson_id;
                                $little_id = $this->lessonModel->little_add($little);
                                if (!$little_id) {
                                    continue;
                                }
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
        $mid = request()->has('mid', 'get') ? request()->get('mid/d') : 0;
        // echo $mid;
        $this->assign('mid', $mid);
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
            //获取所有sheet 名称
            $sheetNames = $spreadsheet->getSheetNames();
            $excelArr = array();
            $msg = '';
            foreach ($sheetNames as $k => $sheetName) {
                $sheetIndex = $k + 1;
                $sheetNameArr = explode('-', $sheetName);
                if (empty($sheetNameArr[0]) || empty($sheetNameArr[1])) {
                    unlink($path);
                    return ['code' => 0, 'msg' => "第{$sheetIndex}个工作表名错误", 'data' => array()];
                }
                $member_id = (int) $sheetNameArr[0];
                $member_name = trim($sheetNameArr[1]);
                $where['m.id'] = ['=', $member_id];
                $where['m.name'] = ['=', $member_name];
                $member = $this->memberModel->get_member($where);
                if (empty($member)) {
                    unlink($path);
                    return ['code' => 0, 'msg' => "第{$sheetIndex}个工作表名不对应", 'data' => array()];
                }
                $worksheet = $spreadsheet->setActiveSheetIndexByName($sheetName);
                $highestRow = $worksheet->getHighestRow(); // 总行数
                $highestColumn = $worksheet->getHighestColumn(); // 总列数
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                $sheet = array();

                $title = (string) $worksheet->getCellByColumnAndRow(1, 2)->getValue();
                if ('计划任务-' . date('Y-m-d') != $title) {
                    unlink($path);
                    return ['code' => 0, 'msg' => '计划表错误', 'data' => array()];
                }
                $end_row_title = (string) $worksheet->getCellByColumnAndRow(1, $highestRow)->getValue();
                if ('结束计划' != $end_row_title) {
                    unlink($path);
                    return ['code' => 0, 'msg' => '计划表错误，无--最后一行结束计划--标签，总行数：' . $highestRow . '，请重新下载', 'data' => array()];
                }

                $end_column_title = (string) $worksheet->getCellByColumnAndRow($highestColumnIndex, 3)->getValue();
                if ('结束计划' != $end_column_title) {
                    unlink($path);
                    return ['code' => 0, 'msg' => '计划表错误，无--最后一列结束计划--标签，总列数：' . $highestColumn . '，请重新下载', 'data' => array()];
                }
                //循环列 去掉说明列
                $index = 1; //第几个计划
                for ($column = 2; $column <= $highestColumnIndex - 1; $column++) { //$highestRow
                    //每次循环 读取两列 生成一个计划
                    $planNext = $column + 2;
                    $detail_video_column = $column + 1;
                    $detail_explain_column = $column + 2;
                    //定义一个计划数组
                    $valArr = array();
                    //循环行，第一行 日期 第二行计划名称 第三行热身语  最后一行冷身语  其他为计划详情
                    $detail = array(); //动作详情

                    $date = (string) $worksheet->getCellByColumnAndRow($column, 3)->getValue();
                    if ($date == '结束计划') {
                        break;
                    }
                    if (empty($date)) {
                        unlink($path);
                        return ['code' => 0, 'msg' => "第{$sheetIndex}个工作表中,第{$index}个计划，时间格式格式错误", 'data' => array()];
                    }
                    $valArr['date'] = $date;
                    $name = (string) $worksheet->getCellByColumnAndRow($column, 4)->getValue();
                    if (empty($name)) {
                        unlink($path);
                        return ['code' => 0, 'msg' => "第{$sheetIndex}个工作表中,第{$index}个计划，计划名称错误", 'data' => array()];
                    }
                    $valArr['name'] = $name;

                    $warmUp = (string) $worksheet->getCellByColumnAndRow($column, 5)->getValue();
                    if (empty($warmUp)) {
                        $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，热身语为空##@@##";
                    }
                    $valArr['warmUp'] = $warmUp;
                    $warmUpVideo = (string) $worksheet->getCellByColumnAndRow($column, 6)->getValue();
                    if (empty($warmUpVideo)) {
                        $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，热身视频为空##@@##";
                    }
                    $valArr['warmUpVideo'] = $warmUpVideo;

                    $collDown = (string) $worksheet->getCellByColumnAndRow($column, 7)->getValue();
                    if (empty($collDown)) {
                        $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，冷身语为空##@@##";
                    }
                    $valArr['collDown'] = $collDown;
                    $collDownVideo = (string) $worksheet->getCellByColumnAndRow($column, 8)->getValue();
                    if (empty($collDownVideo)) {
                        $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，冷身视频为空##@@##";
                    }
                    $valArr['collDownVideo'] = $collDownVideo;
                    for ($row = 9; $row < $highestRow; $row++) {
                        //结束信息
                        $end = (string) $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                        if ($end == '结束计划') {
                            break;
                        }
                        //把数字转成字母
                        $numLetter = Coordinate::stringFromColumnIndex($column);
                        $videoLetter = Coordinate::stringFromColumnIndex($detail_video_column);
                        $explainLetter = Coordinate::stringFromColumnIndex($detail_explain_column);

                        //获取计划详情内容
                        $detail_num = (string) $worksheet->getCellByColumnAndRow($column, $row)->getValue(); //计划详情编号
                        if (empty($detail_num)) {
                            $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，详情编号为空##@@##";
                        }
                        $detail_video = (string) $worksheet->getCellByColumnAndRow($detail_video_column, $row)->getValue();
                        if (empty($detail_video)) {
                            $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，详情视频为空##@@##";
                        }
                        $detail_explain = (string) $worksheet->getCellByColumnAndRow($detail_explain_column, $row)->getValue();
                        if (empty($detail_video)) {
                            $msg .= "第{$sheetIndex}个工作表中,第{$index}个计划，详情说明为空##@@##";
                        }
                        $detail[$row]['explain'] = $detail_explain;
                        $detail[$row]['num'] = $detail_num;
                        $detail[$row]['video'] = $detail_video;
                        $valArr['detail'] = $detail;
                    }
                    $msg .= "\r\n";
                    $sheet[] = $valArr;
                    $column = $planNext;
                    $index++;
                }
                $excelArr[$member_id] = $sheet;
            }
            // $logpath = './runtime/arrange/' . date('YmdHis') . '.txt';
            // write_log($msg, $logpath);
            return ['code' => 1, 'msg' => $msg, 'data' => $excelArr];
        } else {
            return ['code' => 0, 'msg' => '文件不存在', 'data' => array()];
        }
    }

    public function test()
    {
        // $bank = db()->table('motion_bank')->column('name', 'id');
        // $id = array_search('交替壶铃借力推 ', $bank);
        // dump($id);
        // dump($bank);exit;
        // $url = 'http://motion.com/static/upload/1.xlsx';
        $url = 'http://motion.com/static/upload/0d4c207ce5250441.xlsx';
        // $logpath = './runtime/arrange/' . date('YmdHis') . '.txt';
        // echo $logpath;exit;
        // write_log($url, $logpath);
        $res = $this->analysisExcel($url);
        dump($res);
    }

    /**
     * 编辑批量计划
     * @param $data 数据
     * @param $id motion_batch_lesson 主键ID
     */
    public function edit_batch_lesson($data, $id)
    {
        $where['id'] = $id;
        $batch_lesson = $this->lessonModel->get_batch_lesson($where);
        $code = $this->lessonModel->edit_batch_lesson($data, $where);
        if (!empty($batch_lesson['is_dispense'])) {
            //说明已经分发了  还要修改计划信息
            unset($data['member_ids']);
            $lesson_ids = $batch_lesson['lesson_ids'];
            $lesson_ids_arr = explode(',', $lesson_ids);
            foreach ($lesson_ids_arr as $v) {
                $lwhere['id'] = $v;
                $code = $this->lessonModel->edit($data, $lwhere);
            }
        }
        return $code;
    }

    /**
     * 删除课程
     */
    public function del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择会员动作');
        }
        $this->check_arrange_data($id);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 查看小动作
     */
    public function little()
    {
        $this->assign('title', '动作详情');
        //排课ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //判断排课是否存在
        $this->check_arrange_data($id);
        $this->assign('lid', $id);
        return $this->fetch();
    }

    /**
     * 获取小动作列表信息
     */
    public function get_little_courses()
    {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $where[] = ['l_id', '=', $lid];
        $where[] = ['status', '=', 1];
        $order['sort'] = 'asc';
        $lists = $this->lessonModel->get_little_courses($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_little_courses($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 添加小动作
     */
    public function little_add()
    {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        $is_batch = request()->has('is_batch', 'get') ? request()->get('is_batch/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }

        $this->assign('lid', $lid);
        //验证排课是否存在
        if (!$is_batch) {
            $list = $this->check_arrange_data($lid);
            $this->check_member_time($list['m_id']);
        } else {
            $where['id'] = $lid;
            $batch_lesson = $this->lessonModel->get_batch_lesson($where);
            if (empty($batch_lesson)) {
                $this->error('该教练计划不存在');
            }
        }

        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        $this->assign('is_batch', $is_batch); //是否计划
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function little_add_info()
    {
        $lid = request()->has('lid', 'post') ? request()->post('lid/d') : 0;
        $m_ids = request()->has('m_ids', 'post') ? request()->post('m_ids/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $remark = request()->has('remark', 'post') ? request()->post('remark/s') : '';
        $num = request()->has('num', 'post') ? request()->post('num/s') : '';
        $is_batch = request()->has('is_batch', 'post') ? request()->post('is_batch/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }
        if (!$name) {
            $this->error('请填写动作名称');
        }
        //判断排课是否存在
        if (!$is_batch) {
            $list = $this->check_arrange_data($lid);
        } else {
            $where['id'] = $lid;
            $batch_lesson = $this->lessonModel->get_batch_lesson($where);
            if (empty($batch_lesson)) {
                $this->error('该教练计划不存在');
            }
        }
        $data['l_id'] = $lid;
        $data['name'] = $name;
        $data['m_ids'] = $m_ids;
        $data['remark'] = $remark;
        $data['num'] = $num;
        $validate = $this->lessonModel->course_validate($data);
        if ($validate) {
            $this->error($validate);
        }

        if ($is_batch) {
            $code = $this->batch_little_add_info($data, $lid);
        } else {
            $code = $this->lessonModel->little_add($data);
        }
        if ($code) {
            $this->success('保存成功', '');
        } else {
            $this->error('保存失败');
        }
    }
    /**
     * 添加批量计划详情
     */
    public function batch_little_add_info($data, $lid)
    {
        $lwhere['id'] = $lid;
        $lesson = $this->lessonModel->get_batch_lesson($lwhere);
        if (!empty($lesson['is_dispense'])) {
            //说明已经分发了
            $lesson_ids_arr = explode(',', $lesson['lesson_ids']);
            $littleIdsArr = array(); //定义一个空数组  存放自增ID
            foreach ($lesson_ids_arr as $v) {
                $data['l_id'] = $v; //l_id 是计划的ID
                $code = $this->lessonModel->little_add($data);
                $littleIdsArr[] = $code;
            }
            $lesson_course_ids = implode(',', $littleIdsArr);
            $data['lesson_course_ids'] = $lesson_course_ids;
            $data['l_id'] = $lid; // l_id  是教练计划ID
            $code = $this->lessonModel->batch_little_add($data);
        } else {
            $code = $this->lessonModel->batch_little_add($data);
        }
        return $code;
    }

    /**
     * 编辑小动作
     * @return type
     */
    public function little_edit()
    {
        //小动作ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }

        //验证小动作是否存在
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        $this->assign('list', $list);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 添加小动作信息
     */
    public function little_edit_info()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $is_batch = request()->has('is_batch', 'post') ? request()->post('is_batch/d') : 0;
        $m_ids = request()->has('m_ids', 'post') ? request()->post('m_ids/s') : null;
        $name = request()->has('name', 'post') ? request()->post('name/s') : null;
        $remark = request()->has('remark', 'post') ? request()->post('remark/s') : '';
        $num = request()->has('num', 'post') ? request()->post('num/s') : '';
        if (!$id) {
            $this->error('请正确选择');
        }
        if (!$name) {
            $this->error('请填写动作名称');
        }
        if (!$is_batch) {
            //验证小动作是否存在
            $list = $this->check_little_data($id);
            //验证排课是否存在
            $arrange = $this->check_arrange_data($list['l_id']);
        }
        $data['name'] = $name;
        $data['m_ids'] = $m_ids;
        $data['remark'] = $remark;
        $data['num'] = $num;
        $validate = $this->lessonModel->course_validate($data);
        if ($validate) {
            $this->error($validate);
        }

        if ($is_batch) {
            $this->batch_little_edit_info($data, $id);
        } else {
            $lwhere['id'] = $id;
            $code = $this->lessonModel->little_edit($data, $lwhere);
        }

        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 修改序号
     */
    public function edit_sort()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $sort = request()->has('sort', 'post') ? request()->post('sort/d') : 0;
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }
        if (!$sort && $sort != 0) {
            $this->error('请填写序号');
        }
        $data['sort'] = $sort;
        $where[] = ['id', '=', $id];
        $code = $this->lessonModel->little_edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }
    /**
     * 编辑批量计划
     */
    public function batch_little_edit_info($data, $id)
    {
        $llwhere['id'] = ['=', $id];
        $little = $this->lessonModel->get_batch_little($llwhere); //获取批量计划详情
        $lwhere['id'] = ['=', $little['l_id']];
        $lesson = $this->lessonModel->get_batch_lesson($lwhere); //获取批量计划
        $this->lessonModel->batch_little_edit($data, $llwhere);
        if (!empty($lesson['is_dispense'])) {
            //说明分发了
            $lesson_course_ids_arr = explode(',', $little['lesson_course_ids']);
            foreach ($lesson_course_ids_arr as $v) {
                $where['id'] = $v;
                $this->lessonModel->little_edit($data, $where);
            }
        }
        $this->success('修改成功', '');
    }

    public function edit_batch_little()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        $sort = request()->has('sort', 'post') ? request()->post('sort/d') : 0;
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }
        if (!$sort && $sort != 0) {
            $this->error('请填写序号');
        }
        $data['sort'] = $sort;
        $where[] = ['id', '=', $id];
        $code = $this->lessonModel->batch_little_edit($data, $where);
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 删除小动作
     */
    public function little_del()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $list = $this->check_little_data($id);
        //验证排课是否存在
        $arrange = $this->check_arrange_data($list['l_id']);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->little_edit($data, $where);
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 发送消息
     */
    public function send()
    {
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        $array = $this->check_arrange_data($id);
        $member = $this->check_member_data($array['m_id']);
        $where['m_id'] = $member['id'];
        $memberinfo = $this->memberModel->get_member_info($where);
        if (empty($memberinfo)) {
            $this->error('无该会员信息，不能发送，请先完善');
        }
        if (!$memberinfo['is_email'] && !$memberinfo['is_wechat']) {
            $this->error('该会员不接收任何信息通知');
        }
        //发送邮件消息
        if ($memberinfo['is_email']) {
            //暂不处理
        }
        //发送微信消息
        if ($memberinfo['is_wechat']) {
            $res = $this->lessonModel->send_lesson($id);
            if ($res['errcode'] == 0) {
                //更新记录信息
                $data['is_send'] = 1;
                $where['id'] = $id;
                $this->lessonModel->edit($data, $where);
                $this->success('发送成功', '');
            } else if ($res['errcode'] = -1) {
                $this->error($res['msg']);
            } else {
                $this->error('发送失败');
            }
        }
    }

    /**
     * 获取视频地址
     */
    public function get_motion_url()
    {
        //获取数据
        $mid = request()->has('mid', 'post') ? request()->post('mid/d') : 0;
        if (!$mid) {
            $this->error('请正确选择');
        }
        $where['id'] = $mid;
        $list = $this->motionModel->get_motion($where);
        if (!empty($list)) {
            preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $list['url'], $matched);
            $list['url'] = $matched[1];
        }
        echo $this->success($list);
    }

    /**
     * 获取课程
     * @return type
     */
    public function get_courses($pid = 0)
    {

        $where['pid'] = $pid;
        $where['status'] = 1;
        $order['create_time'] = 'desc';
        $courses = $this->courseModel->get_courses($where, $order);
        return $courses;
    }

    /**
     * 计划统计
     */
    public function statistics()
    {
        $mid = request()->has('id', 'get') ? request()->get('id/d') : 0;
        $search_time = request()->get('search_time/s');
        if (!empty($search_time)) {
            $end_time = request()->has('search_time', 'get') ? strtotime(request()->get('search_time/s') . ' 23:59:59') : time();
            $begin_time = strtotime('-7 day', $end_time);
            $where[] = ['class_time', '>=', $begin_time];
            $where[] = ['class_time', '<=', $end_time];
            $page = 0;
            $limit = 0;
        } else {
            $page = 1;
            $limit = 10;
        }
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $where[] = ['m.id', '=', $mid];
        $where[] = ['l.status', '=', 1];
        $where['name'] = $name;
        $order['class_time'] = 'asc';
        $lesson = $this->lessonModel->get_arrange_lists($where, $order, $page, $limit);
        // $little_array = array();
        foreach ($lesson as $k => $l) {
            $littleWhere['status'] = ['=', 1];
            $littleWhere['l_id'] = ['=', $l['id']];
            $little = $this->lessonModel->get_little_courses($littleWhere);
            $lesson[$k]['little'] = $little;
            foreach ($little as $j => $v) {
                $fwhere['lc_id'] = ['=', $v['id']];
                $files = $this->lessonModel->get_course_files($fwhere);
                $lesson[$k]['little'][$j]['files'] = $files;
            }

            // $little_array[$l['id']] = $little;
        }
        // dump($lesson);exit;
        // $this->assign('little', $little_array);
        $this->assign('lesson', $lesson);
        return $this->fetch();
    }

    /**
     * 批量计划
     */
    public function batch()
    {
        $this->assign('title', '批量备课');
        return $this->fetch();
    }
    /**
     * 获取批量计划信息
     */
    public function get_batch_lesson()
    {
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $name = request()->has('name', 'get') ? request()->get('name/s') : '';
        $class_time = request()->has('class_time', 'get') ? request()->get('class_time/s') : '';
        if (session('user.is_admin') == 0) {
            $where[] = ['u_id', '=', session('user.id')];
        }
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($class_time) {
            $ct = explode(' - ', $class_time);
            if (!empty($ct[0]) && validate()->isDate($ct[0])) {
                $begin_time = strtotime($ct[0] . ' 00:00:00');
                $where[] = ['class_time', '>=', $begin_time];
            }
            if (!empty($ct[1]) && validate()->isDate($ct[1])) {
                $end_time = strtotime($ct[1] . ' 23:59:59');
                $where[] = ['class_time', '<=', $end_time];
            }
        }
        $where[] = ['status', '=', 1];
        $order['create_time'] = 'desc';

        $lists = $this->lessonModel->get_batch_lessons($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_batch_lessons($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 添加批量计划
     */
    public function add_batch()
    {
        $uid = session('user.id');
        //获取所属uid 的教练
        $where['u_id'] = $uid;
        $where['status'] = 1;
        $coach = $this->coachModel->get_coach($where);
        if (empty($coach) && session('user.is_admin') == 0) {
            $this->error('该账号未绑定教练');
        }
        if (session('user.is_admin') == 0) {
            $lwhere[] = ['c_id', '=', $coach['id']];
        }
        $lwhere[] = ['m.status', '=', 1];
        $lwhere[] = ['t.end_time', '>', time()];
        $members = $this->memberModel->get_members($lwhere);
        $memberTypes = MemberType::where(array('status' => 1))->select();
        foreach ($memberTypes as $key => &$value) {
            $member = [];
            foreach ($members as $k => $v) {
                if ($value['id'] == $v['type_id']) {
                    $member[] = $v;
                }
            }
            $value['member'] = $member;
        }
        $this->assign('memberTypes', $memberTypes);
        $motionModel = new \app\motion\model\Motion();
        $types = $motionModel->get_type_motions();
        $this->assign('types', $types);
        $is_batch = 1;
        $this->assign('is_batch', $is_batch);
        return $this->fetch('add');
    }

    /**
     * 编辑批量计划
     */
    public function edit_batch()
    {
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择批量计划');
        }

        $lessonWhere['status'] = ['=', 1];
        $lessonWhere['id'] = ['=', $id];
        $lesson = $this->lessonModel->get_batch_lesson($lessonWhere);
        if (empty($lesson)) {
            $this->error('您选择的批量计划不存在');
        }
        $this->assign('list', $lesson);
        $uid = session('user.id');
        //获取所属uid 的教练
        $where['u_id'] = $uid;
        $where['status'] = 1;
        $coach = $this->coachModel->get_coach($where);
        if (empty($coach) && session('user.is_admin') == 0) {
            $this->error('该账号未绑定教练');
        }
        if (session('user.is_admin') == 0) {
            $lwhere[] = ['c_id', '=', $coach['id']];
        }
        $lwhere[] = ['m.status', '=', 1];
        $lwhere[] = ['t.end_time', '>', time()];
        $members = $this->memberModel->get_members($lwhere);
        $this->assign('members', $members);
        $motionModel = new \app\motion\model\Motion();
        $types = $motionModel->get_type_motions();
        $this->assign('types', $types);
        $is_batch = 1;
        $this->assign('is_batch', $is_batch);

        return $this->fetch('edit');
    }
    /**
     * 删除批量计划
     */
    public function del_batch()
    {
        //获取数据
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择批量计划');
        }

        $where['id'] = $id;
        $batch_lesson = $this->lessonModel->get_batch_lesson($where);
        if (empty($batch_lesson)) {
            $this->error('无此教练计划');
        }
        $data['status'] = 0;
        $code = $this->lessonModel->edit_batch_lesson($data, $where);
        $cllwhere['l_id'] = $id; //批量计划详情
        $code = $this->lessonModel->batch_little_edit($data, $cllwhere);
        //如果已经分发  删除计划信息
        if (!empty($batch_lesson['is_dispense'])) {
            $lesson_ids = $batch_lesson['lesson_ids'];
            $lesson_ids_arr = explode(',', $lesson_ids);
            foreach ($lesson_ids_arr as $v) {
                $lwhere['id'] = $v;
                $this->lessonModel->edit($data, $lwhere);
                $llwhere['l_id'] = $v;
                $this->lessonModel->little_edit($data, $llwhere);
            }
        }
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }
    /**
     * 批量计划详情
     */
    public function batch_little()
    {
        $this->assign('title', '动作详情');
        //获取数据
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择批量计划');
        }

        $where['id'] = ['=', $id];
        $batch_lesson = $this->lessonModel->get_batch_lesson($where);
        if (empty($batch_lesson)) {
            $this->error('无此批量计划');
        }
        $this->assign('lid', $id);
        return $this->fetch();
    }

    /**
     * 编辑教练计划详情
     */
    public function batch_little_edit()
    {

        //小动作ID
        $id = request()->has('id', 'get') ? request()->get('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }

        $where['id'] = ['=', $id];
        $list = $this->lessonModel->get_batch_little($where);
        $this->assign('list', $list);
        //获取所有分组视频
        $motionModel = new \app\motion\model\Motion();
        $types = $motionModel->get_type_motions();
        $this->assign('types', $types);
        $this->assign('is_batch', 1);
        return $this->fetch('little_edit');
    }

    /**
     * 删除教练计划详情
     */
    public function batch_little_del()
    {
        //小动作ID
        $id = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$id) {
            $this->error('请正确选择');
        }
        //获取详情

        $llwhere['id'] = $id;
        $little = $this->lessonModel->get_batch_little($llwhere);
        if (empty($little)) {
            $this->erro('无此批量计划详情');
        }
        $lwhere['id'] = $little['l_id'];
        $lesson = $this->lessonModel->get_batch_lesson($lwhere);
        $where['id'] = $id;
        $data['status'] = 0;
        $code = $this->lessonModel->batch_little_edit($data, $where);
        if (!empty($lesson['is_dispense'])) {
            $lesson_course_ids_arr = explode(',', $little['lesson_course_ids']);
            foreach ($lesson_course_ids_arr as $v) {
                $where['id'] = $v;
                $ldata['status'] = 0;
                $this->lessonModel->little_edit($ldata, $where);
            }
        }
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取批量计划详情
     */
    public function get_batch_little()
    {
        //排课ID
        $lid = request()->has('lid', 'get') ? request()->get('lid/d') : 0;
        $page = request()->has('page', 'get') ? request()->get('page/d') : 1;
        $limit = request()->has('limit', 'get') ? request()->get('limit/d') : 10;
        $where[] = ['l_id', '=', $lid];
        $where[] = ['status', '=', 1];
        $order['create_time'] = 'asc';

        $lists = $this->lessonModel->get_batch_littles($where, $order, $page, $limit);
        $count = count($this->lessonModel->get_batch_littles($where));
        echo $this->tableReturn($lists, $count);
    }

    /**
     * 分发教练计划
     */
    public function batch_dispense()
    { //排课ID
        $lid = request()->has('id', 'post') ? request()->post('id/d') : 0;
        if (!$lid) {
            $this->error('请正确选择');
        }
        $lessonWhere['id'] = ['=', $lid];
        $lesson = $this->lessonModel->get_batch_lesson($lessonWhere);
        $littleWhere['l_id'] = ['=', $lid];
        $little = $this->lessonModel->get_batch_littles($littleWhere);
        if (empty($little)) {
            $this->error('未添加计划详情');
        }
        Db::startTrans();
        try {
            $memberModel = new memberModel();
            //获取批量信息
            $lessonIdArr = array(); //定义一个空数据数组 记录每次添加的自增ID
            if (!empty($lesson['member_ids'])) {
                $member_ids = explode(',', $lesson['member_ids']);
                unset($lesson['id']); //去除ID 防止添加时ID字段
                foreach ($member_ids as $v) {
                    $lesson['m_id'] = $v;
                    $mwhere['m.id'] = $v;
                    $member =  $memberModel->get_member($mwhere);
                    $lesson['coach_id'] = $member['c_id'];
                    $code = $this->lessonModel->add($lesson);
                    $lessonIdArr[] = $code;
                }

                $lessonids = implode(',', $lessonIdArr);
                $where['id'] = $lid;
                $data['lesson_ids'] = $lessonids;
                $data['is_dispense'] = 1;
                $this->lessonModel->edit_batch_lesson($data, $where);
            }

            $data = array(); //初始化 data 数据
            foreach ($little as $l) {
                $littleIdArr = array(); //定义一个空数据数组 记录每次添加的自增ID
                $little_id = $l['id'];
                unset($l['id']); //去除ID 防止添加时ID字段
                foreach ($lessonIdArr as $v) {
                    $l['l_id'] = $v;
                    $code = $this->lessonModel->little_add($l);
                    $littleIdArr[] = $code;
                }
                $littleids = implode(',', $littleIdArr);
                $where['id'] = $little_id;
                $data['lesson_course_ids'] = $littleids;
                $this->lessonModel->batch_little_edit($data, $where);
            }
        } catch (\Exception $e) {
            Db::rollback();
            // 回滚事务
            $this->error('分发失败');
        }
        Db::commit();
        $this->success('分发成功', '');
    }

    /**
     * 判断会员是否存在
     */
    public function check_member_data($id = 0)
    {
        //判断类型是否存在
        $where['m.id'] = $id;
        $list = $this->memberModel->get_member($where);
        if (empty($list)) {
            $this->error('无此会员');
        }
        return $list;
    }

    /**
     * 验证会员是否在时间内
     * @param type $id
     */
    public function check_member_time($id = 0)
    {
        $where[] = ['m.id', '=', $id];
        $member = $this->memberModel->get_member($where);
        if (empty($member) || empty($member['c_id'])) {
            $this->error('无此会员或者该会员无教练');
        }

        $twhere[] = ['mt.m_id', '=', $id];
        $twhere[] = ['mt.status', '=', 1];
        $twhere[] = ['end_time|begin_time', '>', time()];
        $time = $this->memberModel->get_member_time($twhere);
        if (empty($time)) {
            $this->error('该会员未开通或未到时间');
        }
    }

    /**
     * 判断排课是否存在
     */
    public function check_arrange_data($id = 0)
    {
        //判断排课是否存在
        $where['id'] = $id;
        $list = $this->lessonModel->get_arrange_list($where);
        if (empty($list)) {
            $this->error('无此排课');
        }

        $list['class_time_show'] = date('Y-m-d', $list['class_time']);
        return $list;
    }

    /**
     * 验证小动作是否存在
     */
    public function check_little_data($id = 0)
    {
        $where['id'] = $id;
        $list = $this->lessonModel->get_little_course($where);
        if (empty($list)) {
            $this->error('无此小动作');
        }
        return $list;
    }
    public function template()
    {
        $this->assign('title', '备课模板');
        return $this->fetch();
    }
    public function getTemplate()
    {
        $query = Template::where(['status' => 1])->append(['createTimeStr']);
        $res = $query->paginate(10, false);
        $list = $res->getCollection();
        $count = $res->total();
        echo $this->tableReturn($list, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function templateAdd()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            $model = new Template();
            $code = $model->save($param);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑模板
     */
    public function templateEdit()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            $model = new Template();
            $list = $model->where(['id' => $param['id']])->find();
            $code = $list->save($param);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
        $id = input('get.id/d', 0);
        $model = new Template();
        $list = $model->where(['id' => $id])->find();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑模板
     */
    public function templateDel()
    {
        //获取数据
        $id = input('post.id/d', 0);
        if (!$id) {
            $this->error('请正确选择模板');
        }
        $model = new Template();
        $list = $model->where(['id' => $id])->find();
        if (empty($list)) {
            $this->error('模板不存在');
        }
        $list->status = 0;
        $code =  $list->save();
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 备课模板计划
     */
    public function templateLesson()
    {
        $id = input('get.id/d', 0);
        $this->assign('title', '备案模板计划');
        $this->assign('t_id', $id);
        return $this->fetch();
    }

    public function getTemplateLesson()
    {
        $t_id = input('get.t_id/d', 0);
        $data = input('get.');
        $query = TemplateLesson::where(['status' => 1, 't_id' => $t_id])->append(['createTimeStr', 'warmupMidsStr', 'colldownMidsStr']);
        $res = $query->paginate(10, false);
        $list = $res->getCollection();
        $count = $res->total();
        echo $this->tableReturn($list, $count);
    }

    /**
     * 渲染新增窗口
     */
    public function templateLessonAdd()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            $model = new TemplateLesson();
            $code = $model->save($param);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
        $t_id = input('get.t_id/d', 0);
        $this->assign('t_id', $t_id);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 修改序号
     */
    public function templateLessonEditSort()
    {
        $id = input('post.id/d', 0);
        $sort = input('post.sort/d', 0);
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }
        if (!$sort && $sort != 0) {
            $this->error('请填写序号');
        }
        $model = new TemplateLesson();
        $list = $model->where(['id' => $id])->find();
        $list->sort = $sort;
        $code = $list->save();
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 编辑模板
     */
    public function templateLessonEdit()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            if (empty($param['title'])) {
                $this->error('请填写模板名称');
            }
            $model = new TemplateLesson();
            $list = $model->where(['id' => $param['id']])->find();
            $code = $list->save($param);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
        $id = input('get.id/d', 0);
        $model = new TemplateLesson();
        $list = $model->where(['id' => $id])->find();
        $this->assign('list', $list);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 编辑模板
     */
    public function templateLessonDel()
    {
        //获取数据
        $id = input('post.id/d', 0);
        if (!$id) {
            $this->error('请正确选择模板');
        }
        $model = new TemplateLesson();
        $list = $model->where(['id' => $id])->find();
        if (empty($list)) {
            $this->error('模板不存在');
        }
        $list->status = 0;
        $code =  $list->save();
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 模板详情
     */
    public function templateCourse()
    {
        $this->assign('title', '模板详情');
        //排课ID
        $id = input('get.id/d', 0);;
        if (!$id) {
            $this->error('请正确选择');
        }
        //判断排课是否存在
        $this->assign('lid', $id);
        return $this->fetch();
    }

    public function getTemplateCourse()
    {
        $data = input('get.');
        $id = input('get.lid/d', 0);
        $query = TemplateCourse::where(['status' => 1, 'l_id' => $id])->append(['createTimeStr', 'midsStr']);
        $res = $query->paginate(10, false);
        $list = $res->getCollection();
        $count = $res->total();
        echo $this->tableReturn($list, $count);
    }
    /**
     * 添加小动作
     */
    public function templateCourseAdd()
    {
        if ($this->request->isPost()) {
            $param = input('param.');
            $model = new TemplateCourse();
            $code = $model->save($param);
            if ($code) {
                $this->success('添加成功', '');
            } else {
                $this->error('添加失败');
            }
        }
        //排课ID
        $lid = input('get.lid/d', 0);;;
        if (!$lid) {
            $this->error('请正确选择');
        }
        $this->assign('lid', $lid);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 修改序号
     */
    public function templateCourseEditSort()
    {
        $id = input('post.id/d', 0);
        $sort = input('post.sort/d', 0);
        if (!$id) {
            $this->error('请选择要编辑的动作');
        }
        if (!$sort && $sort != 0) {
            $this->error('请填写序号');
        }
        $model = new TemplateCourse();
        $list = $model->where(['id' => $id])->find();
        $list->sort = $sort;
        $code = $list->save();
        if ($code) {
            $this->success('编辑成功', '');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 修改序号
     */
    public function templateCourseEdit()
    {
        if ($this->request->isPost()) {
            $param = input('post.');

            $model = new TemplateCourse();
            $list = $model->where(['id' => $param['id']])->find();
            $code = $list->save($param);
            if ($code) {
                $this->success('保存成功', '');
            } else {
                $this->error('保存失败');
            }
        }
        $id = input('get.id/d', 0);
        $model = new TemplateCourse();
        $list = $model->where(['id' => $id])->find();
        $this->assign('list', $list);
        //获取所有分组视频
        $types = $this->motionModel->get_type_motions();
        $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 删除模板详情
     */
    public function templateCourseDel()
    {
        //获取数据
        $id = input('post.id/d', 0);
        if (!$id) {
            $this->error('请正确选择模板详情');
        }
        $model = new TemplateCourse();
        $list = $model->where(['id' => $id])->find();
        if (empty($list)) {
            $this->error('模板详情不存在');
        }
        $list->status = 0;
        $code =  $list->save();
        if ($code) {
            $this->success('删除成功', '');
        } else {
            $this->error('删除失败');
        }
    }
}

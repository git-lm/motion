<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use service\FileService;
use think\Response;

/**
 * 插件助手控制器
 * Class Plugs
 * @package app\admin\controller
 * @author Anyon
 * @date 2017/02/21
 */
class Plugs extends BasicAdmin
{

    /**
     * 文件上传
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function upfile()
    {
        $uptype = $this->request->get('uptype');
        if (!in_array($uptype, ['local', 'qiniu', 'oss'])) {
            $uptype = sysconf('storage_type');
        }
        $mode = $this->request->get('mode', 'one');
        $filetype = $this->request->get('filetype', '0');
        $view = $this->request->get('view', '0');
        if (is_weixin()) {
            $types = '';
            $this->assign('mimes', '');
        } else {
            $types = $this->request->get('type', 'jpg,png');
            $this->assign('mimes', FileService::getFileMine($types));
        }

        $this->assign('field', $this->request->get('field', 'file'));
        return $this->fetch('', ['mode' => $mode, 'types' => $types, 'uptype' => $uptype, 'filetype' => $filetype]);
    }
    /**
     * 文件夹名称说明
     * 1、说明是系统参数图片
     * 2、产品图片
     * 3、留言图片
     * 4、会员运动记录
     * 5、会员头像
     * 6、会员照片
     * 7、计划任务
     * 8、音乐
     * 9、课程日程
     */

    /**
     * 通用文件上传
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @throws \OSS\Core\OssException
     */
    public function upload()
    {

        if (!empty($_FILES['file'])) {
            if ($_FILES['file']['error'] == 1 || $_FILES['file']['error'] == 2) {
                return json(['code' => 'ERROR', 'msg' => '文件上传大小受限']);
            }
        } else {
            return json(['code' => 'ERROR', 'msg' => '请上传文件']);
        }
        $file = $this->request->file('file');
        if (!$file->checkExt(strtolower(sysconf('storage_local_exts')))) {
            return json(['code' => 'ERROR', 'msg' => '文件上传类型受限']);
        }

        $filetype = $this->request->post('filetype', 0);
        $uptype = $this->request->post('uptype', 'local');
        $menber = session('motion_member');

        if (!empty($menber)) {
            $uid = md5($menber['id']);
        } else {
            $uid = md5(0);
            if ($filetype == 7) {
                if (!empty(session('user.id'))) {
                    $uid = md5(session('user.id'));
                }
            }
        }
        $dir = date('Ym');
        if ($filetype == 1) {
            $pr = "sysconfig";
        } else if ($filetype == 2) {
            $pr = "{$dir}/store/{$uid}";
        } else if ($filetype == 3) {
            $pr = "{$dir}/message/{$uid}";
        } else if ($filetype == 4) {
            $pr = "{$dir}/record/{$uid}";
        } else if ($filetype == 5) {
            $pr = "{$dir}/member/{$uid}";
        } else if ($filetype == 6) {
            $pr = "{$dir}/photo/{$uid}";
        } else if ($filetype == 7) {
            $pr = "{$dir}/arrange/{$uid}";
        } else if ($filetype == 8) {
            $pr = "{$dir}/music/{$uid}";
        } else if ($filetype == 9) {
            $pr = "{$dir}/class/{$uid}";
        } else {
            $pr = "{$dir}/others/{$uid}";
        }
        $names = str_split($this->request->post('md5'), 16);
        $ext = strtolower(pathinfo($file->getInfo('name'), 4));
        $ext = $ext ? $ext : 'tmp';
        $filename = "{$names[0]}/{$names[1]}.{$ext}";
        // 文件上传Token验证
        if ($this->request->post('token') !== md5($filename . session_id())) {
            return json(['code' => 'ERROR', 'msg' => '文件上传验证失败']);
        }
        // 文件上传处理
        if ($uptype == 'oss') {
            $filePath = $file->getInfo('tmp_name');
            $filename = $pr . "/{$names[1]}.{$ext}";
            $res = FileService::ossUploadFile($filename, $filePath);
            if (!empty($res['url'])) {
                return json(['data' => ['site_url' => $res['url']], 'code' => 'SUCCESS', 'msg' => '文件上传成功']);
            }
        } else if (($info = $file->move("static/upload/{$pr}", "{$names[1]}.{$ext}", true))) {
            // if (($info = $file->move("static/upload/{$pr}/{$names[0]}", "{$names[1]}.{$ext}", true))) {
            // if (($site_url = FileService::getFileUrl("{$pr}/{$filename}", 'local'))) {
            if (($site_url = FileService::getFileUrl("{$pr}/{$names[1]}.{$ext}", 'local'))) {
                // imgthumb("static/upload/{$pr}", "{$names[1]}", "{$ext}");
                // $this->imgthumb("static/upload/{$pr}/{$names[0]}", "{$names[1]}", "{$ext}");
                return json(['data' => ['site_url' => $site_url], 'code' => 'SUCCESS', 'msg' => '文件上传成功']);
            }
        }
        return json(['code' => 'ERROR', 'msg' => '文件上传失败']);
    }

    /**
     * 文件状态检查
     * @throws \OSS\Core\OssException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function upstate()
    {
        session('motion_member');
        $post = $this->request->post();
        $ext = strtolower(pathinfo($post['filename'], 4));

        $filename = join('/', str_split($post['md5'], 16)) . '.' . ($ext ? $ext : 'tmp');
        // 检查文件是否已上传
        if (($site_url = FileService::getFileUrl($filename))) {
            return json(['data' => ['site_url' => $site_url], 'code' => "IS_FOUND"]);
        }

        // 需要上传文件，生成上传配置参数
        $data = ['uptype' => $post['uptype'], 'file_url' => $filename];
        switch (strtolower($post['uptype'])) {
            case 'local':
                $data['token'] = md5($filename . session_id());
                $data['server'] = FileService::getUploadLocalUrl();
                $data['session_id'] = session_id();
                break;
            case 'qiniu':
                $data['token'] = $this->_getQiniuToken($filename);
                $data['server'] = FileService::getUploadQiniuUrl(true);
                break;
            case 'oss':
                $data['token'] = md5($filename . session_id());
                $data['server'] = FileService::getUploadLocalUrl();
                $data['session_id'] = session_id();
                // $time = time() + 3600;
                // $policyText = [
                //     'expiration' => date('Y-m-d', $time) . 'T' . date('H:i:s', $time) . '.000Z',
                //     'conditions' => [['content-length-range', 0, 1048576000]],
                // ];
                // $data['server'] = FileService::getUploadOssUrl();
                // $data['policy'] = base64_encode(json_encode($policyText));
                // $data['site_url'] = FileService::getBaseUriOss() . $filename;
                // $data['signature'] = base64_encode(hash_hmac('sha1', $data['policy'], sysconf('storage_oss_secret'), true));
                // $data['OSSAccessKeyId'] = sysconf('storage_oss_keyid');
                break;
        }
        return json(['data' => $data, 'code' => "NOT_FOUND"]);
    }

    /**
     * 生成七牛文件上传Token
     * @param string $key
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function _getQiniuToken($key)
    {
        $baseUrl = FileService::getBaseUriQiniu();
        $bucket = sysconf('storage_qiniu_bucket');
        $accessKey = sysconf('storage_qiniu_access_key');
        $secretKey = sysconf('storage_qiniu_secret_key');
        $params = [
            "scope" => "{$bucket}:{$key}", "deadline" => 3600 + time(),
            "returnBody" => "{\"data\":{\"site_url\":\"{$baseUrl}/$(key)\",\"file_url\":\"$(key)\"}, \"code\": \"SUCCESS\"}",
        ];
        $data = str_replace(['+', '/'], ['-', '_'], base64_encode(json_encode($params)));
        return $accessKey . ':' . str_replace(['+', '/'], ['-', '_'], base64_encode(hash_hmac('sha1', $data, $secretKey, true))) . ':' . $data;
    }

    /**
     * 图片生成缩略图
     */
    public function imgthumb()
    {
        $file = request()->url();
        $pathinfo = pathinfo($file);
        if (empty($pathinfo)) {
            return;
        };
        $dirname = $pathinfo['dirname'];
        $basename = $pathinfo['basename'];
        $extension = $pathinfo['extension'];
        $filename = $pathinfo['filename'];
        $types = str_replace(',', '|', sysconf('storage_local_exts'));
        if (!empty($dirname) && !empty($basename) && !empty($filename) && stripos($types, $extension) !== false) {
            $dir = str_replace('thumb/', '', $dirname);
            $fileNameArr = explode('_', $filename);
            if (empty($fileNameArr[0])) {
                return;
            }
            $name = $fileNameArr[0];
            if (empty($fileNameArr[1])) {
                return;
            } else {
                $height = (int) $fileNameArr[1] > 100 ? 100 : (int) $fileNameArr[1];
            }
            if (empty($fileNameArr[2])) {
                return;
            } else {
                $width = (int) $fileNameArr[2] > 100 ? 100 : (int) $fileNameArr[2];
            }

            $file = ".{$dir}/" . $name . '.' . $extension;
            if (file_exists($file)) {
                !is_dir(trim($dirname, '/')) && mkdir(trim($dirname, '/'), 0777, true);
                $image = \think\Image::open($file);
                $image->thumb($width, $height)->save(trim($dirname, '/') . '/' . $name . '_' . $width . '_' . $height . '.' . $extension);
                // $img = FileService::getBaseUriLocal() .  $dirname . '/' . $name . '_' . $width . '_' . $height . '.' . $extension;
                $imgType = $image->mime();
                ob_start();
                $image->save(null);
                $out2 = ob_get_clean();
                return new Response($out2, 200, ["content-type: {$imgType}"]);
            }
        }
    }

    /**
     * 字体图标选择器
     * @return \think\response\View
     */
    public function icon()
    {
        $field = $this->request->get('field', 'icon');
        return $this->fetch('', ['field' => $field]);
    }
}

<?php

use service\DataService;
use service\NodeService;
use think\Db;

/**
 * 打印输出数据到文件
 * @param mixed $data 输出的数据
 * @param bool $force 强制替换
 * @param string|null $file
 */
function p($data, $force = false, $file = null)
{
    is_null($file) && $file = env('runtime_path') . date('Ymd') . '.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
    $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
}

/**
 * RBAC节点权限验证
 * @param string $node
 * @return bool
 */
function auth($node)
{
    return NodeService::checkAuthNode($node);
}

/**
 * 设备或配置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是null为获取值，否则为更新
 * @return string|bool
 * @throws \think\Exception
 * @throws \think\exception\PDOException
 */
function sysconf($name, $value = null)
{
    static $config = [];
    if ($value !== null) {
        list($config, $data) = [[], ['name' => $name, 'value' => $value]];
        return DataService::save('SystemConfig', $data, 'name');
    }
    if (empty($config)) {
        $config = Db::name('SystemConfig')->column('name,value');
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * 日期格式标准输出
 * @param string $datetime 输入日期
 * @param string $format 输出格式
 * @return false|string
 */
function format_datetime($datetime, $format = 'Y年m月d日 H:i:s')
{
    return date($format, strtotime($datetime));
}

/**
 * UTF8字符串加密
 * @param string $string
 * @return string
 */
function encode($string)
{
    list($chars, $length) = ['', strlen($string = iconv('utf-8', 'gbk', $string))];
    for ($i = 0; $i < $length; $i++) {
        $chars .= str_pad(base_convert(ord($string[$i]), 10, 36), 2, 0, 0);
    }
    return $chars;
}

/**
 * UTF8字符串解密
 * @param string $string
 * @return string
 */
function decode($string)
{
    $chars = '';
    foreach (str_split($string, 2) as $char) {
        $chars .= chr(intval(base_convert($char, 36, 10)));
    }
    return iconv('gbk', 'utf-8', $chars);
}

/**
 * 下载远程文件到本地
 * @param string $url 远程图片地址
 * @return string
 */
function local_image($url)
{
    return \service\FileService::download($url)['url'];
}

/**
 * Ajax 返回数据
 * @param Array/String $msg  返回数据
 * @param int   $code    状态码   1正常 0错误
 * @return type
 */
function ajax_list_return($msg = '', $pages = 0, $code = 1)
{
    $json = ['code' => $code, 'msg' => $msg, 'pages' => $pages];
    echo json_encode($json);
    return;
}

/**
 *  时间比较 
 * @param type $the_time 时间戳
 * @return type
 */
function time_trans($the_time)
{
    $now_time = time();


    $dur = $now_time - $the_time;

    if ($dur < 60) {
        return $dur . '秒前';
    } else if ($dur < 3600) {
        return floor($dur / 60) . '分钟前';
    } else if ($dur < 86400) {
        return floor($dur / 3600) . '小时前';
    } else {
        return floor($dur / 86400) . '天前';
    }
}

/**
 * 查找某个值是否存在于多维数组中
 * @param type $value 要查找的值
 * @param type $array 查找的数组
 * @return boolean
 */
function deep_in_array($value, $array)
{
    foreach ($array as $item) {
        if (!is_array($item)) {
            if ($item == $value) {
                return $item;
            } else {
                continue;
            }
        }

        if (in_array($value, $item)) {
            return $item;
        } else if (deep_in_array($value, $item)) {
            return $item;
        }
    }
    return false;
}
/**
 * 图片生成缩略图
 */
function imgthumb($file_url = '', $file_name = '', $ext = '', $width = 150, $height = 150)
{
    $types = str_replace(',', '|', sysconf('storage_local_exts'));
    $file = "{$file_url}/{$file_name}.{$ext}";
    dump($file);
    dump(file_exists($file));
    if (!empty($file) && file_exists($file)) {
        if (stripos($types, $ext) !== false) {
            $image = \think\Image::open($file);
            !is_dir("{$file_url}/thumb/") && mkdir("{$file_url}/thumb/", 0777, true);
            $image->thumb($width, $height)->save("{$file_url}/thumb/{$file_name}.{$ext}");
        }
    }
}

// /**
//  * 获取图片的缩略图
//  * @param  string $str 内容
//  */
function get_thumb1($str = '')
{
    $str = 'http://motion.com/static/upload/photo/45c48cce2e2d7fbdea1afc51c7c6ad26/5e7894d61a0f63fa.png';
    $path_parts =  pathinfo($str);
    dump(pathinfo($str, PATHINFO_DIRNAME));
    $data = [];
    $reg = '/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|gif|png)/';
    $matches = array();
    preg_match_all($reg, $str, $matches);
    foreach ($matches[0] as $value) {
        $data[] = $value;
    }

    dump($matches);
    return $data;
}
/**
 * 获取图片的缩略图
 * @param  string $str 内容
 */
function get_thumb2($str = '')
{
    $str = 'http://motion.com/static/upload/photo/45c48cce2e2d7fbdea1afc51c7c6ad26/5e7894d61a0f63fa.png';
    $matches = array();
    // preg_match("/\/(?P<name>\w+\.(?:" . str_replace(',', '|', sysconf('storage_local_exts')) . "))$/i", $str, $matches);
    if (!empty($matches['name'])) {
        $name = $matches['name'];
        $thumb_str = str_replace($name, 'thumb/' . $name, $str);

        if (!file_exists($str)) {
            $path_parts =  pathinfo($str);
            imgthumb($path_parts['dirname'], $path_parts['filename'], $path_parts['extension']);
        }
        dump($thumb_str);
        return $str;
    } else {
        return $str;
    }
}
function get_thumb($str = '')
{
    $str = 'http://motion.com/static/upload/photo/45c48cce2e2d7fbdea1afc51c7c6ad26/5e7894d61a0f63fa.png';
    $path_parts =  pathinfo($str);
    $thumb_str = str_replace($path_parts['basename'], 'thumb/' . $path_parts['basename'], $str);

    imgthumb($path_parts['dirname'], $path_parts['filename'], $path_parts['extension']);
    // imgthumb('static/upload/photo/45c48cce2e2d7fbdea1afc51c7c6ad26','5e7894d61a0f63fa', 'png');
}

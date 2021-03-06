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
 * 判断是否微信
 * @return boolean
 */
function is_weixin()
{
    if (strpos(
        $_SERVER['HTTP_USER_AGENT'],
        'MicroMessenger'
    ) !== false) {
        return true;
    }
    return false;
}
function get_thumb($str = '', $width = 100, $height = 100)
{
    if (strpos($str, 'aliyuncs.com')) {
        return $str . '?x-oss-process=image/resize,m_lfit,h_100,w_100';
    } else {
        $parse_url = parse_url($str);
        if (!empty($parse_url['path'])) {
            $path = $parse_url['path'];
            $pathinfo = pathinfo($path);
            $filename = $pathinfo['filename'];
            $str = str_replace(['static/upload', $filename], ['static/upload/thumb', $filename . "_{$width}" . "_{$height}"], $str);
            return $str;
        }
    }
}

/**
 * [write_log 写入日志]
 * @param  [type] $data [写入的数据]
 * @return [type]       [description]
 */
function write_log($data, $file = null)
{
    //设置路径目录信息
    is_null($file) && $file = './runtime/' . date('YmdHis') . '.txt';
    $dir_name = dirname($file);
    //目录不存在就创建
    if (!file_exists($dir_name)) {
        //iconv防止中文名乱码
        $res = mkdir(iconv("UTF-8", "GBK", $dir_name), 0777, true);
    }
    $fp = fopen($file, "a"); //打开文件资源通道 不存在则自动创建
    fwrite($fp, date("Y-m-d H:i:s") . var_export($data, true) . "\r\n"); //写入文件
    fclose($fp); //关闭资源通道
}

function extractSrc($src = '')
{
    preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $src, $matched);
    if (empty($matched[1])) {
        return '';
    } else {
        return $matched[1];
    }
}
/**
 * 求两个已知经纬度之间的距离,单位为米
 * 
 * @param lng1 $ ,lng2 经度
 * @param lat1 $ ,lat2 纬度
 * @return float 距离，单位米
 * @author www.Alixixi.com 
 */
function getdistance($lng1, $lat1, $lng2, $lat2)
{
    // 将角度转为狐度
    $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
    return $s;
}

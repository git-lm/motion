<?php
namespace app\index\controller;

class Test
{

    public function index()
    {

        $this->write_log(json_encode(request()->param()));
    }

    /**
     * [write_log 写入日志]
     * @param  [type] $data [写入的数据]
     * @return [type]       [description]
     */
    public function write_log($data)
    {
        $years = date('Y-m');
        //设置路径目录信息
        $url = './public/log/txlog/' . $years . '/' . date('Ymd') . '_request_log.txt';
        $dir_name = dirname($url);
        //目录不存在就创建
        if (!file_exists($dir_name)) {
            //iconv防止中文名乱码
            $res = mkdir(iconv("UTF-8", "GBK", $dir_name), 0777, true);
        }
        $fp = fopen($url, "a"); //打开文件资源通道 不存在则自动创建
        fwrite($fp, date("Y-m-d H:i:s") . var_export($data, true) . "\r\n"); //写入文件
        fclose($fp); //关闭资源通道
    }

    public function tiqusrc()
    {
        $str ='<iframe frameborder="0" src="https://v.qq.com/txp/iframe/player.html?vid=r0807dju9ya" allowFullScreen="true"></iframe>';
        preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $str, $matched);
        echo $matched[1];
    }
}

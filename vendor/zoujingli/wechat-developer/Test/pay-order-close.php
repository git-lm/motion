<?php
try {

    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备公众号配置参数
    $config = include "./config.php";

    // 3. 创建接口实例
    $wechat = new \WeChat\Pay($config);

    // 4. 组装参数，可以参考官方商户文档
    $options = '1217752501201407033233368018';
    $result = $wechat->closeOrder($options);

    var_export($result);

} catch (Exception $e) {

    // 出错啦，处理下吧
    echo $e->getMessage() . PHP_EOL;

}
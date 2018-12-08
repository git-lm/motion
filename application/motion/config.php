<?php

use think\Db;
$config = Db::table(); 
return [
    // 应用调试模式
    'app_debug' => true,
    // 应用Trace调试
    'app_trace' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 1,
];

<?php

/**
 * 模块路由及配置检测并加载
 * @include module/init.php
 * @author Anyon
 */
foreach (scandir(env('app_path')) as $dir) {
    if ($dir[0] !== '.') {
        $filename = realpath(env('app_path') . "{$dir}/init.php");
        $filename && file_exists($filename) && include($filename);
    }
}
//绑定admin 为后台
Route::domain('admin', 'admin');
Route::domain('wx', 'index');
Route::domain('www', '/');
Route::domain('*', 'index/blog');
// Route::domain('www', 'index');
//绑定前台index 模块  Login 控制器  别名
Route::alias('login', 'index/login');
//绑定前台index 模块  Member 控制器 别名
Route::alias('member', 'index/member');
//绑定前台index 模块  index 控制器 别名
Route::alias('list', 'index/index');
//绑定前台index 模块  Lesson 控制器 别名
Route::alias('lesson', 'index/lesson');
//缩略图        
// Route::get('static/upload/thumb/:date/:dir/:basename/:filename', 'admin/Plugs/imgthumb');
return [];

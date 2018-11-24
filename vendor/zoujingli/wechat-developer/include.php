<?php
spl_autoload_register(function ($classname) {
    $separator = DIRECTORY_SEPARATOR;
    $filename = __DIR__ . $separator . str_replace('\\', $separator, $classname) . '.php';
    if (file_exists($filename)) {
        if (stripos($classname, 'WeChat') === 0) {
            include $filename;
        }
        if (stripos($classname, 'WeMini') === 0) {
            include $filename;
        }
        if (stripos($classname, 'WePay') === 0) {
            include $filename;
        }
        if ($classname === 'We') {
            include $filename;
        }
    }
});
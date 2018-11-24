<?php

return [
    'token'          => 'test',
    'appid'          => 'wx60a43dd8161666d4',
    'appsecret'      => '71308e96a204296c57d7cd4b21b883e8',
    'encodingaeskey' => 'BJIUzE0gqlWy0GxfPp4J1oPTBmOrNDIGPNav1YFH5Z5',
    // 配置商户支付参数
    'mch_id'         => "1332187001",
    'mch_key'        => 'A82DC5BD1F3359081049C568D8502BC5',
    // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
    // 'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_cert.p12',
    'ssl_key'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_key.pem',
    'ssl_cer'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem',
    // 配置缓存目录，需要拥有写权限
    'cache_path'     => '',
];
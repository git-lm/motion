<?php

namespace app\wechat\controller\api;

use service\WechatService;
use think\facade\Response;

/**
 * 公众号页面支持
 * Class Script
 * @package app\wechat\controller\api
 */
class Js
{

    /**
     * jsSign签名
     * @return mixed
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $result = app('request');
        $url = $result->server('HTTP_REFERER', $result->url(true), null);
        $wechat = WechatService::webOauth($url, $result->get('mode', 1), false);
        $assign = [
            'jssdk'  => WechatService::webJsSDK($url),
            'openid' => $wechat['openid'], 'fansinfo' => $wechat['fansinfo'],
        ];
        return Response::create(env('APP_PATH') . 'wechat/view/api/script/index.js', 'view', 200, [
            'Content-Type'  => 'application/x-javascript',
            'Cache-Control' => 'no-cache', 'Pragma' => 'no-cache', 'Expires' => '0',
        ])->assign($assign);
    }
}
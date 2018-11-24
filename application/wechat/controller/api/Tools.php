<?php

namespace app\wechat\controller\api;

use controller\BasicAdmin;
use Endroid\QrCode\QrCode;
use service\WechatService;

/**
 * 公众号测试工具
 * Class Tools
 * @package app\wechat\controller\api
 */
class Tools extends BasicAdmin
{
    /**
     * 网页授权测试
     * @return string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function oauth()
    {
        $fans = WechatService::webOauth($this->request->url(true), 1);
        return $this->fetch('', ['fans' => $fans]);
    }

    /**
     * 显示网页授权二维码
     * @return \think\Response
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionFailedException
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     * @throws \Endroid\QrCode\Exceptions\ImageTypeInvalidException
     */
    public function oauth_qrc()
    {
        $url = url('@wechat/api.tools/oauth', '', true, true);
        return $this->createQrc($url);
    }

    /**
     * JSSDK测试
     * @return string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function jssdk()
    {
        return $this->fetch('', ['options' => WechatService::webJsSDK()]);
    }

    /**
     * 显示网页授权二维码
     * @return \think\Response
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionFailedException
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     * @throws \Endroid\QrCode\Exceptions\ImageTypeInvalidException
     */
    public function jssdk_qrc()
    {
        $url = url('@wechat/api.tools/jssdk', '', true, true);
        return $this->createQrc($url);
    }

    /**
     * 创建二维码响应对应
     * @param string $url 二维码内容
     * @return \think\Response
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionFailedException
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     * @throws \Endroid\QrCode\Exceptions\ImageTypeInvalidException
     */
    protected function createQrc($url)
    {
        $qrCode = new QrCode();
        $qrCode->setText($url)->setSize(300)->setPadding(20)->setImageType(QrCode::IMAGE_TYPE_PNG);
        return \think\facade\Response::header('Content-Type', 'image/png')->data($qrCode->get());
    }

}
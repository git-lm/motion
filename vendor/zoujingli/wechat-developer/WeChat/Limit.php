<?php

namespace WeChat;


use WeChat\Contracts\BasicWeChat;

/**
 * 接口调用频次限制
 * Class Limit
 * @package WeChat
 */
class Limit extends BasicWeChat
{

    /**
     * 公众号调用或第三方平台帮公众号调用对公众号的所有api调用（包括第三方帮其调用）次数进行清零
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function clearQuota()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/clear_quota?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['appid' => $this->config->get('appid')]);
    }


}
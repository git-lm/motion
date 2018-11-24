<?php
namespace WePay;

use WeChat\Contracts\BasicPay;

/**
 * 微信商户代金券
 * Class Coupon
 * @package WePay
 */
class Coupon extends BasicPay
{
    /**
     * 发放代金券
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function create(array $options)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/send_coupon";
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 查询代金券批次
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function queryStock(array $options)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/query_coupon_stock";
        return $this->callPostApi($url, $options, false);
    }

    /**
     * 查询代金券信息
     * @param array $options
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function queryInfo(array $options)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/query_coupon_stock";
        return $this->callPostApi($url, $options, false);
    }

}
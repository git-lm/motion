<?php

namespace app\wechat\service;

use service\DataService;
use service\ToolsService;
use service\WechatService;
use think\Db;

/**
 * 微信扫码登录WIFI
 * Class FansService
 * @package app\wechat
 */
class WifiService
{

    /**
     * 增加扫码时间
     * @param array $user
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function set(array $user, array $receive)
    {
        $user['appid'] = WechatService::getAppid();
        if (!empty($user['subscribe_time'])) {
            $user['subscribe_at'] = date('Y-m-d H:i:s', $user['subscribe_time']);
        }
        if (isset($user['tagid_list']) && is_array($user['tagid_list'])) {
            $user['tagid_list'] = join(',', $user['tagid_list']);
        }
        foreach (['country', 'province', 'city', 'nickname', 'remark'] as $field) {
            isset($user[$field]) && $user[$field] = ToolsService::emojiEncode($user[$field]);
        }
        unset($user['privilege'], $user['groupid']);
        //连网信息
        if (!empty($receive['ConnectTime'])) {
            $user['connect_time'] = date('Y-m-d H:i:s', $receive['ConnectTime']);
        }
        if (!empty($receive['ShopId'])) {
            $user['shop_id'] = $receive['ShopId'];
        }
        if (!empty($receive['DeviceNo'])) {
            $user['device_no'] = $receive['DeviceNo'];
        }
        return DataService::save('wechatWifi', $user);
    }
}

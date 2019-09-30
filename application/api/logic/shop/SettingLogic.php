<?php

namespace app\api\logic\shop;

use app\api\logic\BaseLogic;

class SettingLogic extends BaseLogic
{
    /**
     * 店铺设置转换
     *
     * @param json $setting
     *
     * @return array
     */
    public function settingTransform($setting)
    {
        $settingArray = json_decode($setting, true);
        // 默认配置
        $finalSetting = $defaultSetting = [
            'show_send_sms' => 1,
            'show_phone' => 1,
            'show_enter_shop' => 1,
            'show_address' => 1,
            'show_wechat' => 0,
            'show_qq' => 0,
            'show_payment' => 1,
        ];
        if (!empty($settingArray)) {
            foreach ($defaultSetting as $key => $value) {
                $finalSetting[$key] = $settingArray[$key] ?? $defaultSetting[$key];
            }
        }
        return $finalSetting;
    }
}

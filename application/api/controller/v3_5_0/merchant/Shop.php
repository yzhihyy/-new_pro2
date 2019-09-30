<?php

namespace app\api\controller\v3_5_0\merchant;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\{
    ShopModel
};
use app\api\validate\v3_5_0\{
    ShopValidate
};
use app\api\logic\shop\SettingLogic;

class Shop extends Presenter
{
    /**
     * 获取店铺设置
     *
     * @return Json
     */
    public function getShopSetting()
    {
        try {
            // 当前店铺
            $selectedShop = $this->request->selected_shop;
            $shopModel = model(ShopModel::class);
            $where = ['id' => $selectedShop->id];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            // 店铺设置逻辑
            $settingLogic = new SettingLogic();
            // 店铺设置转换
            $setting = $settingLogic->settingTransform($shopInfo['setting']);
            $data = [
                'show_send_sms' => $setting['show_send_sms'],
                'show_phone' => $setting['show_phone'],
                'show_enter_shop' => $setting['show_enter_shop'],
                'show_address' => $setting['show_address'],
                'show_wechat' => $setting['show_wechat'],
                'show_qq' => $setting['show_qq'],
                'qq' => $shopInfo['qq'],
                'wechat' => $shopInfo['wechat'],
                'show_payment' => $setting['show_payment'],
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('获取买单设置接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 店铺设置
     *
     * @return Json
     */
    public function shopSetting()
    {
        try {
            // 请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(ShopValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('shopSetting')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 当前店铺
            $selectedShop = $this->request->selected_shop;
            $shopModel = model(ShopModel::class);
            $where = ['id' => $selectedShop->id];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            if (isset($paramsArray['show_wechat']) && $paramsArray['show_wechat'] == 1 && empty($shopInfo['wechat']) && empty($paramsArray['wechat'])) {
                // 请填写微信号
                return apiError(config('response.msg104'));
            }
            if (isset($paramsArray['show_qq']) && $paramsArray['show_qq'] == 1 && empty($shopInfo['qq']) && empty($paramsArray['qq'])) {
                // 请填写QQ号
                return apiError(config('response.msg105'));
            }
            $settingLogic = new SettingLogic();
            // 店铺设置转换
            $setting = $settingLogic->settingTransform($shopInfo['setting']);
            // 拼接参数
            foreach ($setting as $key => $value) {
                $settingData[$key] = isset($paramsArray[$key]) ? (int)$paramsArray[$key] : $setting[$key];
            }
            // 如果没修改就不用更新
            if ($setting != $settingData) {
                // 转化为json格式
                $shopData['setting'] = json_encode($settingData);
            }
            // qq号
            if (!empty($paramsArray['qq']) && $paramsArray['qq'] != $shopInfo['qq']) {
                $shopData['qq'] = $paramsArray['qq'];
            }
            // 微信号
            if (!empty($paramsArray['wechat']) && $paramsArray['wechat'] != $shopInfo['wechat']) {
                $shopData['wechat'] = $paramsArray['wechat'];
            }
            if (empty($shopData)) {
                return apiSuccess();
            }
            // 更新数据
            $shopModel->where($where)->update($shopData);
            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog('店铺设置接口异常：' . $e->getMessage());
        }
        return apiError();
    }
}
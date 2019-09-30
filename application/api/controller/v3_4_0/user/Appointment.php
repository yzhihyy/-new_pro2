<?php

namespace app\api\controller\v3_4_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\{
    ShopModel
};
use app\api\model\v3_4_0\{
    ThemeActivityModel
};
use app\api\validate\v3_4_0\AppointmentValidate;

class Appointment extends Presenter
{
    /**
     * 预约接口
     *
     * @return Json
     */
    public function appointment()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(AppointmentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('appointment')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $themeActivityId = $paramsArray['theme_activity_id'];
            // 实例化主题活动模型
            $themeActivityModel = model(ThemeActivityModel::class);
            $where = [
                'id' => $themeActivityId,
                'delete_status' => 0
            ];
            // 查询主题活动信息
            $themeActivityInfo = $themeActivityModel->where($where)->find();
            if (empty($themeActivityInfo)) {
                // 主题活动不存在
                return apiError(config('response.msg83'));
            }
            // 店铺id
            $shopId = $themeActivityInfo['shop_id'];
            // 实例化店铺模型
            $shopModel = model(ShopModel::class);
            $shopInfo = $shopModel->getShopInfo(['id' => $shopId]);
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            $data = [
                'theme_title' => $themeActivityInfo['theme_title'],
                'theme_cover' => getImgWithDomain($themeActivityInfo['theme_cover']),
                'theme_thumb_cover' => getImgWithDomain($themeActivityInfo['theme_thumb_cover']),
                'booking_status' => $themeActivityInfo['booking_status'],
                'booking_amount' => $themeActivityInfo['booking_amount'],
                'shop_id' => $shopId,
                'shop_name' => $shopInfo['shop_name'],
                'shop_image' => getImgWithDomain($shopInfo['shop_image']),
                'shop_thumb_image' => getImgWithDomain($shopInfo['shop_thumb_image']),
                'shop_address' => $shopInfo['shop_address'],
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '预约接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

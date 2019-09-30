<?php

namespace app\api\controller\v3_0_0\merchant;

use app\api\Presenter;
use think\Response\Json;
use app\api\service\UploadService;
use app\api\model\v3_0_0\{
    ShopModel, OrderModel
};
use app\api\validate\v3_0_0\{
    ShopValidate
};
use app\common\utils\string\StringHelper;

class Shop extends Presenter
{
    /**
     * 上传商家相册图片
     *
     * @return Json
     */
    public function uploadMerchantAlbumImg()
    {
        $uploadImage = input('file.image');
        try {
            $uploadService = new UploadService();
            $uploadResult = $uploadService->setConfig([
                'image_upload_path' => config('parameters.merchant_album_upload_path'),
                'image_thumb_name_type' => 2
            ])->uploadImage($uploadImage);
            if (!$uploadResult['code']) {
                return apiError($uploadResult['msg']);
            }

            return apiSuccess(['image' => $uploadResult['data']['image']]);
        } catch (\Exception $e) {
            generateApiLog('上传商家相册图片接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 获取买单设置
     *
     * @return Json
     */
    public function getPaySetting()
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
            $data = [
                'pay_setting_type' => $shopInfo['pay_setting_type'],
                'prestore_money' => $shopInfo['prestore_money'],
                'present_money' => $shopInfo['present_money'],
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('获取买单设置接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 买单设置
     *
     * @return Json
     */
    public function paySetting()
    {
        try {
            // 请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(ShopValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('paySetting')->check($paramsArray);
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
            $shopData = [
                'pay_setting_type' => $paramsArray['pay_setting_type'],
            ];
            if ($paramsArray['pay_setting_type'] == 2) {
                if (empty($paramsArray['prestore_money']) || !is_numeric($paramsArray['prestore_money']) || $paramsArray['prestore_money'] < 0) {
                    return apiError(config('response.msg95'));
                }
                if (empty($paramsArray['present_money']) || !is_numeric($paramsArray['present_money']) || $paramsArray['present_money'] < 0) {
                    return apiError(config('response.msg96'));
                }
                $shopData['prestore_money'] = $paramsArray['prestore_money'];
                $shopData['present_money'] = $paramsArray['present_money'];
            }
            $shopModel->where($where)->update($shopData);
            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog('买单设置接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 订单列表
     *
     * @return Json
     */
    public function orderList()
    {
        try {
            $shopInfo = $this->request->selected_shop;
            // 获取请求参数
            $paramsArray = input();
            // 实例化关注模型
            $orderModel = model(OrderModel::class);
            $condition = [
                'shopId' => $shopInfo['id'],
                'status' => isset($paramsArray['status']) ? $paramsArray['status'] : 0,
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3')
            ];
            // 获取订单列表
            $orderArray = $orderModel->getOrderList($condition);
            $orderList = [];
            if (!empty($orderArray)) {
                foreach ($orderArray as $value) {
                    $info = [
                        'order_id' => $value['orderId'],
                        'order_type' => $value['orderType'],
                        'order_num' => $value['orderNum'],
                        'verification_status' => $value['verificationStatus'] ?? -1,
                        'pay_money' => $this->decimalFormat($value['paymentAmount']),
                        'prestore_money' => $this->decimalFormat($value['prestoreAmount']),
                        'pay_time' => $value['payTime'],
                        'user_id' => $value['userId'],
                        'nickname' => $value['nickname'],
                        'avatar' => getImgWithDomain($value['avatar']),
                        'thumb_avatar' => getImgWithDomain($value['userThumbAvatar']),
                        'phone' => $value['phone'],
                        'theme_activity_id' => $value['themeActivityId'],
                        'theme_activity_title' => $value['themeActivityTitle'],
                    ];
                    $orderList[] = $info;
                }
            }
            $data = [
                'order_list' => $orderList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('订单列表接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 订单核销接口
     * @return json
     */
    public function orderWriteOff()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(ShopValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('orderWriteOff')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $shopInfo = $this->request->selected_shop;
            // 订单编号
            $orderNumber = $paramsArray['order_number'];
            // 实例化订单模型
            $orderModel = model(OrderModel::class);
            $orderWhere = [
                'order_num' => $orderNumber,
                'shop_id' => $shopInfo['id'],
                'order_status' => 1,
                'verification_status' => 1
            ];
            // 查询订单信息
            $orderInfo = $orderModel->where($orderWhere)->find();
            if (empty($orderInfo)) {
                // 订单不存在
                return apiError(config('response.msg34'));
            }
            // 用户id
            $userId = $this->getUserId();
            $orderData = [
                'verification_status' => 2,
                'verification_operator_user_id' => $userId
            ];
            $orderModel->where($orderWhere)->update($orderData);
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '订单核销接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
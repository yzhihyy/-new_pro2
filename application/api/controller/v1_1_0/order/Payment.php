<?php

namespace app\api\controller\v1_1_0\order;

use app\api\Presenter;
use app\common\utils\string\StringHelper;

class Payment extends Presenter
{
    /**
     * 支付完成接口
     * @return json
     */
    public function paymentCompleted()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Payment');
            // 验证请求参数
            $checkResult = $validate->scene('paymentCompleted')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            if (!$userId) {
                return apiError(config('response.msg9'));
            }
            // 订单编号
            $orderNumber = $paramsArray['order_number'];
            // 实例化订单模型
            $orderModel = model('api/v1_1_0/Order');
            // 查询订单信息
            $orderInfo = $orderModel->getOrderInfo([
                'orderNum' => $orderNumber,
                'userId' => $userId
            ]);
            if (empty($orderInfo)) {
                // 订单不存在
                return apiError(config('response.msg34'));
            }
            $alsoNeedOrderCount = $orderInfo['shop_free_order_frequency'] - $orderInfo['current_number'];
            $data = [
                'shop_id' => $orderInfo['shop_id'],
                'shop_name' => $orderInfo['shop_name'],
                'shop_thumb_image' => getImgWithDomain($orderInfo['shop_thumb_image']),
                'order_status' => $orderInfo['order_status'],
                'free_flag' => $orderInfo['free_flag'],
                'free_money' => $orderInfo['free_money'],
                'pay_money' => $orderInfo['payment_amount'],
                'also_need_order_count' => $alsoNeedOrderCount < 0 ? 0 : $alsoNeedOrderCount,
                'predict_free_money' => $orderInfo['current_number'] == 0 ? '0' : decimalDiv($orderInfo['consume_amount'], $orderInfo['current_number']),
                'share_url' => config('app_host') . '/h5/storeDetail.html?shop_id=' . $orderInfo['shop_id'],
                'free_rule_id' => $orderInfo['free_rule_id']
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '支付完成接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

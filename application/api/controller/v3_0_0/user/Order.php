<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use app\api\model\v3_0_0\OrderModel;
use app\api\logic\v3_0_0\user\OrderLogic;
use app\api\validate\v3_0_0\OrderValidate;

class Order extends Presenter
{
    /**
     * 使用待核销的订单(预存或预定)
     *
     * @return \think\Response\Json
     */
    public function useVerificationOrder()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, OrderValidate::class . '.UseVerificationOrder');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 用户ID
            $userId = $this->request->user->id;
            // 订单ID
            $orderId = $paramsArray['order_id'];
            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $order = $orderModel->getVerificationOrder([
                'orderId' => $orderId,
                'userId' => $userId
            ]);
            // 订单不存在
            if (empty($order)) {
                return apiError(config('response.msg34'));
            }

            // 订单状态发生改变
            if ($order->verification_status != 0) {
                return apiError(config('response.msg90'));
            }

            // 更新订单核销状态
            $result = $orderModel->where([
                'id' => $orderId,
                'user_id' => $userId,
            ])->update([
                'verification_status' => 1
            ]);
            if (!$result) {
                throw new \Exception("用户使用待核销的订单失败,订单ID={$orderId}");
            }

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("用户使用待核销的订单接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}
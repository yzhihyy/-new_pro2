<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;

class OrderLogic extends BaseLogic
{
    /**
     * 处理订单
     *
     * @param array $orders
     *
     * @return array
     */
    public function ordersHandle(array $orders)
    {
        $result = [];
        foreach ($orders as $order) {
            $result[] = [
                'order_id' => $order['orderId'],
                'order_type' => $order['orderType'],
                'order_num' => $order['orderNum'],
                'verification_status' => $order['verificationStatus'] ?? -1,
                'pay_money' => $this->decimalFormat($order['paymentAmount']),
                'prestore_money' => $this->decimalFormat($order['prestoreAmount']),
                'pay_time' => date('n月j日 H:i', strtotime($order['payTime'])),
                'shop_id' => $order['shopId'],
                'nickname' => $order['shopName'],
                'avatar' => getImgWithDomain($order['shopImage']),
                'thumb_avatar' => getImgWithDomain($order['shopThumbImage']),
                'theme_activity_title' => !empty($order['themeTitle']) ? $order['themeTitle'] : '',
            ];
        }

        return $result;
    }
}

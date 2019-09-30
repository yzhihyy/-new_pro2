<?php

namespace app\api\controller\center;

use app\api\Presenter;
use app\common\utils\string\StringHelper;

class MerchantOrder extends Presenter
{
    /**
     * 获取商家订单列表
     *
     * @return \think\response\Json
     */
    public function index()
    {
        /**
         * @var \app\api\model\Order $orderModel
         */
        try {
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $limit = config('parameters.page_size_level_2');
            $userId = $this->getUserId();
            // 查询条件
            $where = [
                'page' => $pageNo,
                'limit' => $limit,
                'userId' => $userId
            ];
            // 筛选客户
            $customerId = input('user_id/d', 0);
            if ($customerId) {
                $userModel = model('api/User');
                $user = $userModel->find($customerId);
                if (!empty($user)) {
                    $where['customerId'] = $customerId;
                }
            }
            $orderModel = model('api/Order');
            $orderList = $orderModel->getMerchantOrderList($where);
            // 返回数据
            $dataList = [];
            foreach ($orderList as $order) {
                $info = [];
                $info['order_id'] = $order['id'];
                $info['order_num'] = $order['orderNum'];
                $info['user_id'] = $order['buyerId'];
                $info['user_nickname'] = $order['buyerNickname'];
                $info['user_avatar'] = getImgWithDomain($order['buyerAvatar']);
                $info['user_thumb_avatar'] = getImgWithDomain($order['buyerThumbAvatar']);
                $info['consume_amount'] = $this->decimalFormat($order['consumeAmount']); // 本轮消费总额
                $info['count_already_buy_times'] = $order['countAlreadyBuyTimes']; // 本轮消费次数
                $info['count_also_need_buy_times'] = $order['countAlsoNeedBuyTimes'] < 0 ? 0 : $order['countAlsoNeedBuyTimes']; // 免单还需次数
                $info['avg_free_money'] = $this->decimalFormat($order['avgFreeMoney']); // 免单平均价
                $info['free_flag'] = $order['freeFlag']; // 是否免单
                $info['pay_money'] = $this->decimalFormat($order['payMoney']); // 本次消费金额
                $info['pay_time'] = dateFormat($order['payTime']); // 支付时间
                array_push($dataList, $info);
            }
            $dataList = StringHelper::nullValueToEmptyValue($dataList);
            return apiSuccess([
                'order_list' => $dataList
            ]);
        } catch (\Exception $e) {
            $logContent = '商家订单列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

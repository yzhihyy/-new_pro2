<?php

namespace app\api\logic\v2_0_0\merchant;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\OrderModel;
use app\common\utils\date\DateHelper;

class StatisticsLogic extends BaseLogic
{
    /**
     * 获取店铺今天的记账时间
     *
     * @param string $tallyTime
     *
     * @return array
     */
    public function getShopTodayTallyTime(string $tallyTime)
    {
        $result = ['start_time' => '', 'end_time' => ''];
        if (!$tallyTime) {
            return $result;
        }

        $nowTime = DateHelper::getNowDateTime();
        $startTime = clone $nowTime;
        $startTime = $startTime->modify($tallyTime);
        $endTime = clone $nowTime;
        $endTime = $endTime->modify('tomorrow ' . $tallyTime);
        // 跨天的营业时间中，如果当前时间还在营业时间，则开始和结束时间要减去一天
        if ($nowTime < $startTime) {
            $startTime->modify('-1 days');
            $endTime->modify('-1 days');
        }
        $result['start_time'] = $startTime;
        $result['end_time'] = $endTime;

        return $result;
    }

    /**
     * 店铺简要交易数据
     *
     * @param array $shop
     *
     * @return array
     */
    public function shopDealSimpleStatistic($shop)
    {
        $tally = $this->getShopTodayTallyTime($shop['tallyTime']);
        $todayStartTime = $tally['start_time'];
        $todayEndTime = $tally['end_time'];
        // 今日开始时间
        $todayStartTime = $todayStartTime->format('Y-m-d H:i:s');
        // 今日结束时间
        $todayEndTime = $todayEndTime->format('Y-m-d H:i:s');
        $orderWhere = ['shop_id' => $shop['id'], 'order_status' => 1];
        // 订单模型
        $orderModel = model(OrderModel::class);
        // 今日成交额
        $dealTodayMoney = $orderModel->where($orderWhere)->whereBetweenTime('payment_time', $todayStartTime, $todayEndTime)->sum('payment_amount');

        // 昨日开始时间
        $yesStartTime = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($todayStartTime)));
        // 昨日结束时间
        $yesEndTime = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($todayEndTime)));
        // 昨日成交额
        $dealYesMoney = $orderModel->where($orderWhere)->whereBetweenTime('payment_time', $yesStartTime, $yesEndTime)->sum('payment_amount');

        // 今日订单数
        $orderTodayCount = $orderModel->where($orderWhere)->whereBetweenTime('payment_time', $todayStartTime, $todayEndTime)->count();
        // 昨日订单数
        $orderYesCount = $orderModel->where($orderWhere)->whereBetweenTime('payment_time', $yesStartTime, $yesEndTime)->count();
        // 总客户人数
        $totalQuantity = $orderModel->where($orderWhere)->group('user_id')->count();
        // 昨日客户人数
        $totalYesPeopleCount = $orderModel->where($orderWhere)->whereBetweenTime('payment_time', $yesStartTime, $yesEndTime)->group('user_id')->count();

        $dealData = [
            'deal_today_money' => decimalAdd($dealTodayMoney, 0),
            'deal_yes_money' => decimalAdd($dealYesMoney, 0),
            'order_today_count' => $orderTodayCount,
            'order_yes_count' => $orderYesCount,
            'total_quantity' => $totalQuantity,
            'total_yes_people_count' => $totalYesPeopleCount,
        ];

        return $dealData;
    }

    /**
     * 店铺详细交易数据
     *
     * @param int $shopId
     * @param string $tallyTime
     * @param string $type
     *
     * @return array
     * @throws
     */
    public function shopDealDetailedStatistic($shopId, $tallyTime = '', $type = 'total')
    {
        $model = model(OrderModel::class);
        $where = ['shop_id' => $shopId, 'order_status' => 1];
        $tally = $this->getShopTodayTallyTime($tallyTime);
        $field = 'SUM(payment_amount) AS moneySum, COUNT(DISTINCT user_id) AS customerCount, SUM(order_amount) as orderMoneySum, COUNT(id) AS orderCount';
        $timeFormat = 'Y-m-d H:i:s';
        switch ($type) {
            case 'today':
                $startTime = $tally['start_time']->format($timeFormat);
                $endTime = $tally['end_time']->format($timeFormat);
                $result = $model->where($where)->whereBetweenTime('payment_time', $startTime, $endTime)->field($field)->find();
                break;
            case 'three_days':
                $startTime = $tally['start_time']->modify('-3 days')->format($timeFormat);
                $endTime = $tally['end_time']->modify('-1 days')->format($timeFormat);
                $result = $model->where($where)->whereBetweenTime('payment_time', $startTime, $endTime)->field($field)->find();
                break;
            case 'seven_days':
                $startTime = $tally['start_time']->modify('-7 days')->format($timeFormat);
                $endTime = $tally['end_time']->modify('-1 days')->format($timeFormat);
                $result = $model->where($where)->whereBetweenTime('payment_time', $startTime, $endTime)->field($field)->find();
                break;
            case 'thirty_days':
                $startTime = $tally['start_time']->modify('-1 month')->format($timeFormat);
                $endTime = $tally['end_time']->modify('-1 days')->format($timeFormat);
                $result = $model->where($where)->whereBetweenTime('payment_time', $startTime, $endTime)->field($field)->find();
                break;
            default:
                $result = $model->where($where)->field($field)->find();
                break;
        }
        //免单统计
        $where['free_flag'] = 1;
        $field = 'SUM(free_money) AS freeMoneySum, COUNT(id) AS freeCount';
        $freeInfo = $model;
        if(isset($startTime) && isset($endTime)){
            $freeInfo = $model->whereBetweenTime('payment_time', $startTime, $endTime);
        }
        $freeInfo = $freeInfo->field($field)->where($where)->find();

        $data = [];
        $data['money_sum'] = decimalAdd($result['moneySum'], 0);//到账金额
        $data['order_count'] = $result['orderCount'];//交易次数
        $data['customer_count'] = $result['customerCount'];
        $data['order_money_sum'] = decimalAdd($result['orderMoneySum'], 0);//订单总额
        $data['free_money_sum'] = decimalAdd($freeInfo['freeMoneySum'], 0);//免单金额
        $data['free_count'] = $freeInfo['freeCount'];//免单次数
        $data['avg_money'] = $result['orderCount'] > 0 ? (decimalDiv($data['money_sum'], $data['order_count'])) : '0.00';//客均价

        return $data;
    }
}

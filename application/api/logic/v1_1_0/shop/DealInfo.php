<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/12
 * Time: 12:04
 */

namespace app\api\logic\v1_1_0\shop;

use app\api\logic\BaseLogic;
use app\common\utils\date\DateHelper;

class DealInfo extends BaseLogic
{
    /**
     * 交易数据
     * @param $shopId
     * @return array
     */
    public static function dealStatis($shopId)
    {
        bcscale(2);
        //今日成交额
        $todayStartTime = DateHelper::getNowDateTime()->format('Y-m-d').' 00:00:00';
        $todayEndTime = DateHelper::getNowDateTime()->format('Y-m-d').' 23:59:59';
        $todayWhere = [ 'shop_id' => $shopId, 'order_status' => 1];
        $orderModel = model('api/Order');
        //今日成交额
        $dealTodayMoney = $orderModel->where($todayWhere)->whereBetweenTime('payment_time', $todayStartTime, $todayEndTime)->sum('payment_amount');
        //昨日成交额
        $yesStartTime = DateHelper::getNowDateTime('yesterday')->format('Y-m-d').' 00:00:00';
        $yesEndTime = DateHelper::getNowDateTime('yesterday')->format('Y-m-d').' 23:59:59';
        $yesWhere = [
            'shop_id' => $shopId,
            'order_status' => 1,
        ];
        $dealYesMoney = $orderModel->where($yesWhere)->whereBetweenTime('payment_time', $yesStartTime, $yesEndTime)->sum('payment_amount');
        //今日订单数
        $orderTodayCount = $orderModel->where($todayWhere)->whereBetweenTime('payment_time', $todayStartTime, $todayEndTime)->count();
        //昨日订单数
        $orderYesCount = $orderModel->where($yesWhere)->whereBetweenTime('payment_time', $yesStartTime, $yesEndTime)->count();
        //总客户人数
        $totalWhere = [ 'shop_id' => $shopId, 'order_status' => 1];
        $totalQuantity = $orderModel->where($totalWhere)->group('user_id')->count();
        //昨日客户人数
        $totalYesPeopleCount = $orderModel->where($yesWhere)->whereBetweenTime('payment_time', $yesStartTime, $yesEndTime)->group('user_id')->count();

        $dealData = [
            'deal_today_desc' => '今日成交额',
            'deal_today_money' => bcadd($dealTodayMoney, 0),
            'deal_yes_desc'   => '昨日',
            'deal_yes_money'  => bcadd($dealYesMoney, 0).'元',
            'order_today_desc' => '今日订单',
            'order_today_count' => $orderTodayCount,
            'order_yes_desc' => '昨日',
            'order_yes_count' => $orderYesCount.'单',
            'total_customer_desc' => '本店总客户',
            'total_quantity' => $totalQuantity,
            'total_yes_desc' => '昨日',
            'total_yes_people_count' => $totalYesPeopleCount.'人',
        ];
        return $dealData;
    }

    /**
     * 店铺交易数据
     * @param $params
     * @return array
     */
    public static function statisShopData($params)
    {
        if(!empty($params)){
            $model = model('api/Order');
            switch($params['type']){
                case 'total':
                    $where = ['shop_id' => $params['shop_id'], 'order_status' => 1];
                    $moneySum = $model->where($where)->sum('payment_amount');
                    $orderCount = $model->where($where)->count();
                    $customerCount = $model->where($where)->group('user_id')->count();

                    break;
                case 'threeDays':
                    $where = ['shop_id' => $params['shop_id'], 'order_status' => 1];

                    $moneySum = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->sum('payment_amount');
                    $orderCount = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->count();
                    $customerCount = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->group('user_id')->count();

                    break;
                case 'sevenDays':
                    $where = ['shop_id' => $params['shop_id'], 'order_status' => 1];
                    $moneySum = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->sum('payment_amount');
                    $orderCount = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->count();
                    $customerCount = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->group('user_id')->count();
                    break;
                case 'thirdtyDays':
                    $where = ['shop_id' => $params['shop_id'], 'order_status' => 1];
                    $moneySum = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->sum('payment_amount');
                    $orderCount = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->count();
                    $customerCount = $model->where($where)->whereBetweenTime('payment_time', $params['start_time'], $params['end_time'])->group('user_id')->count();
                    break;
            }
        }
        $data = [];
        $data['moneySum'] = $moneySum ? bcadd($moneySum, 0, 2) : '0.00';
        $data['orderCount'] = $orderCount ?? 0;
        $data['customerCount'] = $customerCount ?? 0;
        return $data;
    }
}

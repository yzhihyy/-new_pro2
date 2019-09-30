<?php

namespace app\api\controller\center;

use app\api\Presenter;
use app\common\utils\string\StringHelper;

class User extends Presenter
{
    /**
     * 用户中心
     * @return json
     */
    public function userCenter()
    {
        try {
            // 用户模型
            $userModel = model('api/User');
            // 用户id
            $userId = $this->getUserId();
            $userInfo = $userModel->getUserInfo(['id' => $userId]);

            // 订单模型
            $orderModel = model('api/Order');
            $orderCondition = [
                'user_id' => $userId,
                'order_status' => 1,
                'free_flag' => 1
            ];
            // 查询订单统计信息
            $order = $orderModel->getOrderStatistics($orderCondition);

            // 实例化用户在店铺的免单规则模型
            $freeRuleModel = model('api/FreeRule');
            $freeRuleCondition = [
                'user_id' => $userId,
            ];
            $nearestFree = $freeRuleModel->getNearestFreeInfo($freeRuleCondition);
            $nearestFreeInfo = (object)[];
            if (!empty($nearestFree)) {
                $alsoNeedOrderCount = $nearestFree['shop_free_order_frequency'] - $nearestFree['order_count'];
                $nearestFreeInfo = [
                    'free_shop_id' => $nearestFree['shop_id'],
                    'free_shop_name' => $nearestFree['shop_name'],
                    'free_shop_thumb_image' => getImgWithDomain($nearestFree['shop_thumb_image']),
                    'order_count' => $nearestFree['order_count'],
                    'consume_amount' => $this->decimalFormat($nearestFree['consume_amount'], true),
                    'also_need_order_count' => $alsoNeedOrderCount < 0 ? 0 : $alsoNeedOrderCount,
                    'predict_free_money' => $nearestFree['order_count'] == 0 ? '0' :
                        $this->decimalFormat(decimalDiv($nearestFree['consume_amount'], $nearestFree['order_count']), true)
                ];
            }
            $data = [
                'user_info' => [
                    'nickname' => $userInfo['nickname'],
                    'avatar' => getImgWithDomain($userInfo['avatar']),
                    'thumb_avatar' => getImgWithDomain($userInfo['thumb_avatar']),
                ],
                'free_count' => $order['id_count'],
                'free_money_sum' => $this->decimalFormat($order['free_money_sum'], true),
                'nearest_free_info' => $nearestFreeInfo
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '用户中心接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 我的足迹
     * @return json
     */
    public function myFootprint()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 订单模型
            $orderModel = model('api/Order');
            $orderCondition = [
                'userId' => $userId,
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_2')
            ];
            // 查询我的足迹列表
            $order = $orderModel->getMyFootprintList($orderCondition);
            $footprintList = [];
            if (!empty($order)) {
                foreach ($order as $value) {
                    $alsoNeedOrderCount = $value['shop_free_order_frequency'] - $value['current_number'];
                    $info = [
                        'shop_id' => $value['shop_id'],
                        'shop_name' => $value['shop_name'],
                        'shop_thumb_image' => getImgWithDomain($value['shop_thumb_image']),
                        'order_count' => $value['current_number'],
                        'consume_amount' => $this->decimalFormat($value['consume_amount'], true),
                        'also_need_order_count' => $alsoNeedOrderCount < 0 ? 0 : $alsoNeedOrderCount,
                        'predict_free_money' => $value['current_number'] == 0 ? '0' :
                            $this->decimalFormat(decimalDiv($value['consume_amount'], $value['current_number']), true),
                        'free_flag' => $value['free_flag'],
                        'free_money' => $this->decimalFormat($value['free_money'], true)
                    ];
                    $footprintList[] = $info;
                }
            }
            $data = [
                'footprint_list' => $footprintList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '我的足迹接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 免单列表
     * @return json
     */
    public function freeOrderList()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 订单模型
            $orderModel = model('api/Order');
            $orderCondition = [
                'userId' => $userId,
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_2'),
                'freeFlag' => 1
            ];
            // 查询免单列表
            $order = $orderModel->getOrderList($orderCondition);
            $freeOrderList = [];
            if (!empty($order)) {
                foreach ($order as $value) {
                    $info = [
                        'shop_id' => $value['shop_id'],
                        'shop_name' => $value['shop_name'],
                        'shop_thumb_image' => getImgWithDomain($value['shop_thumb_image']),
                        'pay_money' => $value['payment_amount'],
                        'free_money' => $this->decimalFormat($value['free_money'], true),
                        'pay_time' => $value['payment_time'],
                    ];
                    $freeOrderList[] = $info;
                }
            }
            $data = [
                'free_order_list' => $freeOrderList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '免单列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 我的订单列表
     * @return json
     */
    public function myOrderList()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 订单模型
            $orderModel = model('api/Order');
            $orderCondition = [
                'userId' => $userId,
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_2')
            ];
            if (!empty($paramsArray['shop_id'])) {
                $orderCondition['shop_id'] = $paramsArray['shop_id'];
            }
            // 查询我的订单列表
            $order = $orderModel->getOrderList($orderCondition);
            $orderList = [];
            if (!empty($order)) {
                foreach ($order as $value) {
                    $alsoNeedOrderCount = $value['shop_free_order_frequency'] - $value['current_number'];
                    $info = [
                        'shop_id' => $value['shop_id'],
                        'shop_name' => $value['shop_name'],
                        'shop_thumb_image' => getImgWithDomain($value['shop_thumb_image']),
                        'pay_money' => $this->decimalFormat($value['payment_amount'], true),
                        'free_money' => $this->decimalFormat($value['free_money'], true),
                        'pay_time' => $value['payment_time'],
                        'free_flag' => $value['free_flag'],
                        'also_need_order_count' => $alsoNeedOrderCount < 0 ? 0 : $alsoNeedOrderCount
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
            $logContent = '我的订单列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 修改个人资料
     * @return json
     */
    public function saveUserInfo()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            $field = ['avatar', 'thumb_avatar', 'nickname'];
            foreach ($field as $value) {
                if (isset($paramsArray[$value]) && $paramsArray[$value] != '') {
                    $userData[$value] = $paramsArray[$value];
                }
            }
            if (empty($userData)) {
                return apiSuccess();
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化用户模型
            $userModel = model('api/User');
            $where = ['id' => $userId];
            $userInfo = $userModel->where($where)->find();
            if (empty($userInfo)) {
                // 用户不存在或被禁用！
                return apiError(config('response.msg9'));
            }
            $result = $userModel->where($where)->update($userData);
            if (!($result || $result == 0)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '修改个人资料接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

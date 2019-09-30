<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v2_0_0\{UserModel, OrderModel, FreeRuleModel};

class User extends Presenter
{
    /**
     * 用户中心
     *
     * @return \think\response\Json
     */
    public function userCenter()
    {
        try {
            // 获取用户信息
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $userId = $this->getUserId();
            $userInfo = $userModel->find($userId);
            // 查询订单统计信息
            /* @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $where = [
                'o.user_id' => $userId,
                'o.order_status' => 1,
                'o.free_flag' => 1
            ];
            $orderStatistics = $orderModel->getOrderStatistics($where);
            $data = [
                'user_info' => [
                    'user_id' => $userId,
                    'nickname' => $userInfo['nickname'],
                    'avatar' => getImgWithDomain($userInfo['avatar']),
                    'thumb_avatar' => getImgWithDomain($userInfo['thumb_avatar']),
                ],
                'free_count' => $orderStatistics['id_count'],
                'free_money_sum' => $this->decimalFormat($orderStatistics['free_money_sum'], true)
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
     *
     * @return \think\response\Json
     */
    public function myFootprint()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 查询我的足迹列表
            /* @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $orderCondition = [
                'userId' => $userId,
                'page' => $paramsArray['page'] ?? 0,
                'limit' => config('parameters.page_size_level_2')
            ];
            $myFootprintList = $orderModel->getMyFootprintList($orderCondition);
            $footprintList = [];
            if (!empty($myFootprintList)) {
                foreach ($myFootprintList as $value) {
                    $alsoNeedOrderCount = $value['shopFreeOrderFrequency'] - $value['sumCurrentNumber'];
                    $info = [
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shopName'],
                        'shop_thumb_image' => getImgWithDomain($value['shopThumbImage']),
                        'order_count' => $value['sumCurrentNumber'],
                        'consume_amount' => $this->decimalFormat($value['sumConsumeAmount'], true),
                        'also_need_order_count' => $alsoNeedOrderCount < 0 ? 0 : $alsoNeedOrderCount,
                        'predict_free_money' => $value['sumCurrentNumber'] == 0 ? '0' :
                            $this->decimalFormat(decimalDiv($value['sumConsumeAmount'], $value['sumCurrentNumber']), true),
                        'free_flag' => $value['freeFlag'],
                        'free_money' => $this->decimalFormat($value['freeMoney'], true)
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
     * 修改个人资料
     *
     * @return \think\response\Json
     */
    public function saveUserInfo()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            $field = ['avatar', 'thumb_avatar', 'nickname'];
            $domain = config('resources_domain');
            foreach ($field as $value) {
                if (isset($paramsArray[$value]) && $paramsArray[$value] != '') {
                    // 图片去除域名
                    $v = $paramsArray[$value];
                    $v = str_replace($domain, '', $v);
                    $userData[$value] = $v;
                }
            }
            if (empty($userData)) {
                return apiSuccess();
            }
            // 判断用户是否存在
            $userId = $this->getUserId();
            $userModel = model(UserModel::class);
            $userInfo = $userModel->find($userId);
            if (empty($userInfo)) {
                return apiError(config('response.msg9'));
            }
            // 更新信息
            $where = [
                'id' => $userId
            ];
            $result = $userInfo->force(true)->save($userData, $where);
            if (!$result) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '修改个人资料接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 我的订单列表
     *
     * @return \think\response\Json
     */
    public function myOrderList()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 获取订单列表
            /* @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $orderCondition = [
                'userId' => $userId,
                'page' => $paramsArray['page'] ?? 0,
                'limit' => config('parameters.page_size_level_2')
            ];
            $order = $orderModel->getOrderList($orderCondition);
            $orderList = [];
            if (!empty($order)) {
                foreach ($order as $value) {
                    $alsoNeedOrderCount = $value['shopFreeOrderFrequency'] - $value['currentOrderCount'];
                    $info = [
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shopName'],
                        'shop_thumb_image' => getImgWithDomain($value['shopThumbImage']),
                        'pay_money' => $this->decimalFormat($value['paymentAmount'], true),
                        'free_money' => $this->decimalFormat($value['freeMoney'], true),
                        'pay_time' => $value['paymentTime'],
                        'free_flag' => $value['freeFlag'],
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
     * 我的免单订单列表
     *
     * @return \think\response\Json
     */
    public function freeOrderList()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 获取订单列表
            /* @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $orderCondition = [
                'userId' => $userId,
                'page' => $paramsArray['page'] ?? 0,
                'limit' => config('parameters.page_size_level_2'),
                'freeFlag' => 1
            ];
            $order = $orderModel->getOrderList($orderCondition);
            $orderList = [];
            if (!empty($order)) {
                foreach ($order as $value) {
                    $info = [
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shopName'],
                        'shop_thumb_image' => getImgWithDomain($value['shopThumbImage']),
                        'pay_money' => $this->decimalFormat($value['paymentAmount'], true),
                        'free_money' => $this->decimalFormat($value['freeMoney'], true),
                        'pay_time' => $value['paymentTime'],
                    ];
                    $orderList[] = $info;
                }
            }
            $data = [
                'free_order_list' => $orderList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '我的免单订单列表异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 免单卡列表
     *
     * @return \think\response\Json
     */
    public function freeCardList()
    {
        // 分页码
        $page = $this->request->get('page/d', 0);
        $page < 0 && ($page = 0);
        try {
            $userId = $this->getUserId();
            $freeRuleModel = model(FreeRuleModel::class);
            $freeRule = $freeRuleModel->getFreeCardList([
                'userId' => $userId,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);
            $freeCardList = [];
            if (!empty($freeRule)) {
                foreach ($freeRule as $value) {
                    $info = [
                        'free_card_id' => $value['freeRuleId'],
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shopName'],
                        'shop_thumb_image' => getImgWithDomain($value['shopThumbImage']),
                        'predict_free_money' => $value['orderCount'] == 0 ? '0' : decimalDiv($value['consumeAmount'], $value['orderCount']),
                        'free_card_status' => $value['status'],
                        'free_money' => $this->decimalFormat($value['freeMoney']),
                    ];
                    $freeCardList[] = $info;
                }
            }
            $data = [
                'free_card_list' => $freeCardList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog("免单卡列表接口异常：{$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 免单卡详情
     *
     * @return \think\response\Json
     */
    public function freeCardDetail()
    {
        try {
            // 获取请求参数并校验
            $freeCardId = input('free_card_id/d', 0);
            if ($freeCardId <= 0) {
                return apiError(config('response.msg65'));
            }
            // 获取免单卡详情
            $userId = $this->getUserId();
            $freeRuleModel = model(FreeRuleModel::class);
            $where = [
                'userId' => $userId,
                'freeRuleId' => $freeCardId
            ];
            $freeCardInfo = $freeRuleModel->getFreeCardDetail($where);
            if (empty($freeCardInfo)) {
                return apiError(config('response.msg65'));
            }
            // 获取免单卡对应的订单列表
            $orderModel = model(OrderModel::class);
            $freeCardOrderList = $orderModel->getFreeCardOrderList($where);
            $orderList = [];
            foreach ($freeCardOrderList as $order) {
                $item = [];
                $item['order_id'] = $order['orderId'];
                $item['shop_id'] = $order['shopId'];
                $item['shop_name'] = $order['shopName'];
                $item['shop_image'] = getImgWithDomain($order['shopImage']);
                $item['shop_thumb_image'] = getImgWithDomain($order['shopThumbImage']);
                $item['payment_amount'] = $this->decimalFormat($order['paymentAmount']);
                $item['payment_time'] = $order['paymentTime'];
                array_push($orderList, $item);
            }
            // 响应信息
            $alsoNeed = $freeCardInfo['freeOrderFrequency'] - $freeCardInfo['orderCount'];
            $responseData = [
                'free_card_id' => $freeCardInfo['freeRuleId'],
                'free_card_status' => $freeCardInfo['status'],
                'free_order_frequency' => $freeCardInfo['freeOrderFrequency'], // 免单次数
                'order_count' => $freeCardInfo['orderCount'], // 已消费次数
                'also_need' => ($alsoNeed > 0) ? $alsoNeed : 0, // 还需消费次数
                'predict_free_money' => decimalDiv($freeCardInfo['consumeAmount'], $freeCardInfo['orderCount']), // 预计可免金额
                'free_money' => $this->decimalFormat($freeCardInfo['freeMoney']), // 免单金额
                'shop_id' => $freeCardInfo['shopId'],
                'shop_name' => $freeCardInfo['shopName'],
                'shop_thumb_image' => getImgWithDomain($freeCardInfo['shopThumbImage']),
                'order_list' => $orderList
            ];
            $responseData = StringHelper::nullValueToEmptyValue($responseData);
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '免单卡详情接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

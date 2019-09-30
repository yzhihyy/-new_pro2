<?php

namespace app\api\controller\v2_0_0\merchant;

use think\Response\Json;
use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v2_0_0\{FreeRuleModel, OrderModel, UserModel, UserHasShopModel};
use app\api\logic\v2_0_0\merchant\StatisticsLogic;

/**
 * 商家端 - 数据
 */
class Statistics extends Presenter
{
    /**
     * 数据首页
     *
     * @return Json
     */
    public function index()
    {
        try {
            // 当前选中的店铺
            $selectedShop = $this->request->selected_shop;
            $shopPivotModel = model(UserHasShopModel::class);
            $shopPivot = $shopPivotModel->getAuthorizedShop(['userId' => $this->request->user->id]);
            foreach ($shopPivot as $item) {
                $authorizedShopList[] = [
                    'shop_id' => $item['shopId'],
                    'shop_name' => $item['shopName'],
                    'shop_image' => getImgWithDomain($item['shopImage']),
                    'shop_thumb_image' => getImgWithDomain($item['shopThumbImage']),
                    'selected_shop_flag' => $item['selectedShopFlag']
                ];
            }
            $shopInfo = [
                'shop_name' => $selectedShop['shopName'],
                'collect_push_flag' => $selectedShop['collectPushFlag'],
                'authorized_shop_list' => $authorizedShopList ?? []
            ];

            $statisticsLogic = new StatisticsLogic();
            $dealData = $statisticsLogic->shopDealSimpleStatistic($selectedShop);

            return apiSuccess([
                'shop_info' => $shopInfo,
                'deal_data' => $dealData,
            ]);
        } catch (\Exception $e) {
            $logContent = '数据首页接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 店铺交易数据
     *
     * @return Json
     */
    public function shopData()
    {
        try {
            $shop = $this->request->selected_shop;
            $statisticsLogic = new StatisticsLogic();
            $info = [];
            // 总数据
            $info['total'] = $statisticsLogic->shopDealDetailedStatistic($shop['id']);
            // 近三天
            $info['three_days'] = $statisticsLogic->shopDealDetailedStatistic($shop['id'], $shop['tallyTime'], 'three_days');
            // 近七天
            $info['seven_days'] = $statisticsLogic->shopDealDetailedStatistic($shop['id'], $shop['tallyTime'], 'seven_days');
            // 近30天
            $info['thirty_days'] = $statisticsLogic->shopDealDetailedStatistic($shop['id'], $shop['tallyTime'], 'thirty_days');

            return apiSuccess(compact('info'));
        } catch (\Exception $e) {
            $logContent = '店铺交易数据接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 切换店铺
     *
     * @return Json
     */
    public function switchShop()
    {
        try {
            $userId = $this->request->user->id;
            $shopId = $this->request->post('shop_id/d');
            if (!$shopId) {
                return apiError(config('response.msg53'));
            }

            $shopPivotModel = model(UserHasShopModel::class);
            // 要切换的店铺信息
            $shop = $shopPivotModel->getAuthorizedShop(['userId' => $userId, 'shopId' => $shopId, 'type' => 2]);
            // 要切换的店铺未授权或不存在
            if (!$shop) {
                return apiError(config('response.msg56'));
            }

            $shop = reset($shop);
            // 店铺下线
            if (in_array($shop['onlineStatus'], [0, 2, 3])) {
                return apiError(config('response.msg67'));
            }

            $shopPivotModel->startTrans();
            // 更新要切换的店铺为选中状态
            $shopPivotModel->isUpdate(true)->save(['selected_shop_flag' => 1], ['id' => $shop['shopPivotId']]);
            // 更新其他店铺为非选中状态
            $shopPivotModel->isUpdate(true)->save(['selected_shop_flag' => 0], [['id', '<>', $shop['shopPivotId']], ['user_id', '=', $userId]]);
            $shopPivotModel->commit();

            return apiSuccess([
                'shop_id' => $shop['shopId'],
                'shop_name' => $shop['shopName'],
                'shop_image' => getImgWithDomain($shop['shopImage']),
                'shop_thumb_image' => getImgWithDomain($shop['shopThumbImage']),
            ]);
        } catch (\Exception $e) {
            $logContent = '切换店铺接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 客户列表
     *
     * @return Json
     */
    public function customerList()
    {
        // 分页码
        $page = $this->request->get('page/d', 0);
        $page < 0 && ($page = 0);
        // 排序方式,默认最新
        $sort = $this->request->get('sort/d', 1);

        try {
            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $merchantCustomerList = $orderModel->getMerchantCustomerList([
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
                'sort' => $sort,
                'shopId' => $this->request->selected_shop->id
            ]);
            foreach ($merchantCustomerList as &$customer) {
                $customer = [
                    'user_id' => $customer['buyerId'],
                    'user_nickname' => $customer['buyerNickname'],
                    'user_avatar' => getImgWithDomain($customer['buyerAvatar']),
                    'user_thumb_avatar' => getImgWithDomain($customer['buyerThumbAvatar']),
                    // 消费总额
                    'consume_amount' => $this->decimalFormat($customer['countMoney']),
                    // 消费次数
                    'consume_order' => $this->decimalFormat($customer['countOrder'], true),
                    // 客均价
                    'avg_money' => $this->decimalFormat($customer['avgMoney']),
                    // 最近消费金额
                    'last_pay_money' => $this->decimalFormat($customer['lastPayMoney']),
                    // 最近消费时间
                    'last_pay_time' => dateFormat($customer['lastPayTime']),
                ];
            }

            return apiSuccess(['customer_list' => $merchantCustomerList]);
        } catch (\Exception $e) {
            generateApiLog("商家客户列表接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 客户详情-消费数据
     *
     * @return Json
     */
    public function customerDetail()
    {
        try {
            $customerId = $this->request->get('user_id/d');
            if (!$customerId) {
                return apiError(config('response.msg53'));
            }

            // 客户信息
            $customer = model(UserModel::class)->find($customerId);
            if (!$customer) {
                return apiError(config('response.msg9'));
            }

            // 订单模型
            $orderModel = model(OrderModel::class);
            // 免单规则模型
            $freeRuleModel = model(FreeRuleModel::class);
            $shop = $this->request->selected_shop;
            // 在本店的消费数据
            $consumeData = $orderModel->userConsumeDataByShop([
                'userId' => $customerId,
                'shopId' => $shop['id']
            ]);
            // 免单规则
            $freeRule = $freeRuleModel->getUserValidRuleAtShop(['userId' => $customer['id'], 'shopId' => $shop['id']]);
            $freeOrderFrequency = min($freeRule['order_count'], $freeRule['shop_free_order_frequency']);
            $predictFreeMoney = $freeOrderFrequency ? decimalDiv($freeRule['consume_amount'], $freeOrderFrequency) : '0.00';
                $orderInfo = [
                'title_info' => [
                    'phone' => $customer['phone'],
                    'nickname' => $customer['nickname'],
                    'avatar' => getImgWithDomain($customer['avatar']),
                    'thumb_avatar' => getImgWithDomain($customer['thumb_avatar'])
                ],
                'consume_info' => [
                    'total_money' => decimalSub($consumeData['totalMoney'], 0), // 在该店的总交易额
                    'total_count' => $consumeData['totalCount'], // 消费次数
                    'avg_money' => decimalSub($consumeData['avgMoney'], 0), // 客均价
                    'consume_amount' => decimalSub($freeRule['consume_amount'], 0), // 该轮消费总额
                    'residue_count' => max($freeRule['shop_free_order_frequency'] - $freeRule['order_count'], 0), // 免单还需次数
                    'predict_free_money' => $predictFreeMoney, // 预计可免金额
                ]
            ];
            $orderInfo = StringHelper::nullValueToEmptyValue($orderInfo);

            return apiSuccess($orderInfo);
        } catch (\Exception $e) {
            $logContent = '客户详情消费数据接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 客户详情-订单记录
     *
     * @return Json
     */
    public function customerOrderRecord()
    {
        try {
            $customerId = $this->request->get('user_id/d');
            if (!$customerId) {
                return apiError(config('response.msg53'));
            }

            // 页码
            $page = $this->request->get('page/d');
            // 客户信息
            $customer = model(UserModel::class)->find($customerId);
            if (!$customer) {
                return apiError(config('response.msg9'));
            }

            // 订单模型
            $orderModel = model(OrderModel::class);
            // 免单规则模型
            $freeRuleModel = model(FreeRuleModel::class);
            $shop = $this->request->selected_shop;
            $limit = config('parameters.page_size_level_2');
            // 免单规则
            $freeRule = $freeRuleModel->getRuleColumnPagination([
                'shopId' => $shop['id'],
                'userId' => $customer['id'],
                'page' => $page,
                'limit' => $limit
            ]);
            if ($freeRule) {
                $orders = $orderModel->getOrdersByFreeRule([
                    'userId' => $customerId,
                    'freeRuleId' => array_keys($freeRule),
                    'shopId' => $shop['id']
                ]);
            }

            $response = ['order_records' => []];
            // 没有订单记录
            if (!isset($orders) || !$orders) {
                return apiSuccess($response);
            }

            // 根据免单规则组id分组后的订单数组
            $orderGroups = [];
            foreach($orders as $item) {
                $orderGroups[$item['freeRuleId']]['consume_amount'] = decimalAdd($orderGroups[$item['freeRuleId']]['consume_amount'] ?? 0, $item['payMoney']);
                $orderGroups[$item['freeRuleId']]['order_list'][] = [
                    'order_num' => $item['orderNum'],
                    'pay_time' => $item['payTime'],
                    'pay_money' => $item['payMoney'],
                    'order_money' => $item['orderMoney'],
                    'free_flag' => $item['freeFlag']
                ];
            }

            $orderGroups = StringHelper::nullValueToEmptyValue($orderGroups);
            $response['order_records'] = array_values($orderGroups);

            return apiSuccess($response);
        } catch (\Exception $e){
            $logContent = '客户详情订单记录接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

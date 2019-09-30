<?php

namespace app\api\controller\v3_0_0\merchant;

use app\api\Presenter;
use think\response\Json;
use app\api\logic\v2_0_0\merchant\StatisticsLogic;
use app\api\logic\v3_0_0\merchant\ShopLogic;
use app\api\model\v3_0_0\{UserModel, FollowRelationModel, OrderModel};
use app\common\utils\string\StringHelper;

class Operation extends Presenter
{
    /**
     * 运营首页
     *
     * @return Json
     */
    public function operationIndex()
    {
        try{
            $shopSelect = $this->request->selected_shop;
            $userId = $this->request->user->id;
            $shopInfo = [
                'shop_name'             => $shopSelect['shopName'],//店铺名称
                'collect_push_flag'     => $shopSelect['collectPushFlag'],//是否开启通知
                'authorized_shop_list'  => ShopLogic::authorizedShopList(['userId' => $userId]),//授权店铺列表
            ];

            $params = ['shop_id' => $shopSelect['id']];

            $fansCount = ShopLogic::shopFansCount($params);//粉丝数
            $customerCount = ShopLogic::shopCustomerCount($params);//客户数
            $topInfo = [
                'fans_count'        => $fansCount,
                'customer_count'    => $customerCount,
            ];

            $statistic = new StatisticsLogic();
            $data = [
                'shop_info'     => $shopInfo,//店铺信息
                'top_info'      => $topInfo,//顶部数据统计
                'fans_info'     => ShopLogic::fansInfo(['shop_id' => $shopSelect['id']]),//粉丝统计
                'video_info'    => ShopLogic::shopVideoInfo(['shop_id' => $shopSelect['id']]),//视频统计
                'deal_info'     => $statistic->shopDealDetailedStatistic($shopSelect['id'], $shopSelect['tallyTime'], 'today'),
            ];
            return apiSuccess($data);

        }catch (\Exception $e){
            generateApiLog("运营首页接口异常：{$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 我的粉丝列表
     *
     * @return Json
     */
    public function myFansList()
    {
        try {
            $shopInfo = $this->request->selected_shop;
            // 获取请求参数
            $paramsArray = input();
            // 实例化关注模型
            /** @var FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $condition = [
                'shopId' => $shopInfo['id'],
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3')
            ];
            // 获取我的粉丝列表
            $fansArray = $followRelationModel->getMyFansList($condition);
            $fansList = [];
            if (!empty($fansArray)) {
                foreach ($fansArray as $value) {
                    $info = [
                        'user_id' => $value['userId'],
                        'nickname' => $value['nickname'],
                        'thumb_avatar' => getImgWithDomain($value['thumbAvatar']),
                        'follow_time' => dateTransformer($value['followTime']),
                    ];
                    $fansList[] = $info;
                }
            }
            $data = [
                'fans_list' => $fansList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '我的粉丝列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 运营 - 客户详情
     *
     * @return Json
     */
    public function customerDetail()
    {
        $customerId = $this->request->get('user_id/d');
        if (!$customerId) {
            return apiError(config('response.msg53'));
        }

        try {
            // 页码
            $page = $this->request->get('page/d', 0);
            // 客户信息
            $customer = UserModel::find($customerId);
            if (!$customer) {
                return apiError(config('response.msg9'));
            }

            // 订单模型
            $orderModel = model(OrderModel::class);
            $shop = $this->request->selected_shop;
            $orderWhere = [
                'userId' => $customer->id,
                'shopId' => $shop->id,
            ];
            // 3.2.0 以下版本只查询买单订单
            if ($this->request->header('version') < '3.2.0') {
                $orderWhere['orderType'] = 1;
            }
            // 在本店的消费数据
            $consumeData = $orderModel->userConsumeDataByShop($orderWhere);
            // 订单记录
            $orders = $orderModel->getMerchantOrderList(array_merge($orderWhere, [
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]));
            $orderList = [];
            foreach ($orders as $item) {
                $orderList[] = [
                    'phone' => $customer->phone,
                    'nickname' => $customer->nickname,
                    'avatar' => getImgWithDomain($customer->avatar),
                    'thumb_avatar' => getImgWithDomain($customer->thumb_avatar),
                    'order_type' => $item['orderType'],
                    'order_num' => $item['orderNum'],
                    'verification_status' => $item['verificationStatus'] ?? -1,
                    'pay_time' => date('Y-m-d H:i', strtotime($item['payTime'])),
                    'pay_money' => $item['payMoney'],
                    'order_money' => $item['orderMoney'],
                    'prestore_money' => $item['prestoreMoney'],
                    'theme_activity_id' => $item['themeActivityId'],
                    'theme_activity_title' => $item['themeActivityTitle'],
                ];
            }
            $response = [
                'title_info' => [
                    'phone' => $customer->phone,
                    'nickname' => $customer->nickname,
                    'avatar' => getImgWithDomain($customer->avatar),
                    'thumb_avatar' => getImgWithDomain($customer->thumb_avatar)
                ],
                'consume_info' => [
                    'total_money' => decimalSub($consumeData->totalMoney, 0), // 在该店的总交易额
                    'total_count' => $consumeData->totalCount, // 消费次数
                    'avg_money' => decimalSub($consumeData->avgMoney, 0), // 客均价
                ],
                'order_list' => $orderList
            ];

            $response = StringHelper::nullValueToEmptyValue($response);

            return apiSuccess($response);
        } catch (\Exception $e) {
            $logContent = '客户详情消费数据接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

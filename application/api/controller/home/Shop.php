<?php

namespace app\api\controller\home;

use app\api\Presenter;

class Shop extends Presenter
{
    /**
     * 获取店铺列表
     *
     * @return \think\response\Json
     */
    public function getShopList()
    {
        /**
         * @var \app\api\model\Shop $shopModel
         * @var \app\api\model\ShopCategory $shopCategoryModel
         */
        try {
            // 校验参数
            $paramsArray = input();
            $validate = validate('api/Shop');
            $checkResult = $validate->scene('getShopList')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 校验分类是否存在
            $shopCategoryModel = model('api/ShopCategory');
            $shopCategory = $shopCategoryModel->find($paramsArray['shop_category_id']);
            if (empty($shopCategory)) {
                return apiError(config('response.msg19'));
            }
            // 获取店铺列表
            $shopModel = model('api/Shop');
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $shopCategoryId = $paramsArray['shop_category_id'];
            $longitude = $paramsArray['longitude'];
            $latitude = $paramsArray['latitude'];
            // 获取店铺列表
            $resultList = [];
            $where = [
                'page' => $pageNo,
                'shop_category_id' => $shopCategoryId,
                'limit' => config('parameters.page_size_level_2'),
                'longitude' => $longitude,
                'latitude' => $latitude,
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            $shopList = $shopModel->getShopList($where);
            foreach ($shopList as $shop) {
                $info = [];
                $info['shop_id'] = $shop['id'];
                $info['shop_name'] = $shop['shopName'];
                $info['shop_image'] = getImgWithDomain($shop['shopImage']);
                $info['shop_thumb_image'] = getImgWithDomain($shop['shopThumbImage']);
                $info['shop_address'] = $shop['shopAddress'];
                $info['shop_address_poi'] = $shop['shopAddressPoi'];
                $info['shop_category_name'] = $shop['shopCategoryName'];
                $info['free_order_frequency'] = $shop['freeOrderFrequency'];
                $info['how_many_people_bought'] = $shop['howManyPeopleBought'];
                $info['order_count'] = $shop['countOrder'];
                $info['distance'] = $shop['distance'];
                $info['show_distance'] = $this->showDistance($shop['distance']);
                $info['have_bought'] = isset($shop['haveBought']) ? $shop['haveBought'] : 0;
                array_push($resultList, $info);
            }
            // 响应数据
            $responseData = [
                'shop_list' => $resultList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取店铺列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取店铺详情
     *
     * @return \think\response\Json
     */
    public function getShopDetail()
    {
        /**
         * @var \app\api\model\Shop $shopModel
         * @var \app\api\model\Order $orderModel
         * @var \app\api\model\FreeRule $freeRuleModel
         */
        try {
            // 校验参数
            $paramsArray = input();
            $validate = validate('api/Shop');
            $checkResult = $validate->scene('getShopDetail')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 判断店铺是否存在
            $shopModel = model('api/Shop');
            $shop = $shopModel->field('id')->where([
                ['account_status', '=', 1],
                ['online_status', '=', 1]
            ])->find($paramsArray['shop_id']);
            if (empty($shop)) {
                return apiError(config('response.msg10'));
            }
            // 获取店铺详情
            $where = [
                'shop_id' => $paramsArray['shop_id'],
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude']
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            $shopDetail = $shopModel->getShopDetail($where);
            // 获取店铺最近免单记录
            $orderModel = model('api/Order');
            $where = [
                'shop_id' => $shopDetail['shopId'],
                'page' => 0,
                'limit' => config('parameters.page_size_level_3'),
            ];
            $lastFreeOrderList = $orderModel->getShopLastFreeOrderList($where);
            $shopLastFreeOrderList = [];
            foreach ($lastFreeOrderList as $order) {
                $orderInfo = [];
                $orderInfo['order_id'] = $order['orderId'];
                $orderInfo['avatar'] = getImgWithDomain($order['avatar']);
                $orderInfo['nickname'] = $order['nickname'];
                $orderInfo['payment_time'] = dateFormat($order['paymentTime']);
                //$orderInfo['discount_money'] = decimalSub($order['orderAmount'], $order['paymentAmount']); // 免单金额
                $orderInfo['discount_money'] = decimalAdd(0, $order['paymentAmount']); // 支付金额
                array_push($shopLastFreeOrderList, $orderInfo);
            }
            // 获取消费信息（已消费几次，再消费几次可免单）
            $countAlreadyBuyTimes = 0;
            $countAlsoNeedBuyTimes = 0;
            if ($userId) {
                $freeRuleModel = model('api/FreeRule');
                $where = [
                    'user_id' => $userId,
                    'shop_id' => $shopDetail['shopId'],
                ];
                $freeRule = $freeRuleModel->getFreeRuleInfo($where);
                if ($freeRule) {
                    $countAlreadyBuyTimes = $freeRule['orderCount'];
                    $countAlsoNeedBuyTimes = $freeRule['shopFreeOrderFrequency'] - $freeRule['orderCount'];
                } else {
                    $countAlsoNeedBuyTimes = $shopDetail['freeOrderFrequency'];
                }
            }
            // 响应信息
            $haveBought = isset($shopDetail['haveBought']) ? $shopDetail['haveBought'] : 0;
            $countNormalOrder = isset($shopDetail['countNormalOrder']) ? $shopDetail['countNormalOrder'] : 0;
            $countFreeOrder = isset($shopDetail['countFreeOrder']) ? $shopDetail['countFreeOrder'] : 0;
            $responseData = [
                'shop_id' => $shopDetail['shopId'],
                'shop_name' => $shopDetail['shopName'],
                'shop_image' => getImgWithDomain($shopDetail['shopImage']),
                'shop_thumb_image' => getImgWithDomain($shopDetail['shopThumbImage']),
                'shop_address' => $shopDetail['shopAddress'],
                'shop_address_poi' => $shopDetail['shopAddressPoi'],
                'shop_phone' => $shopDetail['shopPhone'],
                'operation_time' => $shopDetail['operationTime'], // 营业时间
                'shop_category_name' => $shopDetail['shopCategoryName'],
                'free_order_frequency' => $shopDetail['freeOrderFrequency'],  // 免单次数
                'how_many_people_bought' => $shopDetail['howManyPeopleBought'],  // 多少人买过
                'order_count' => $shopDetail['countOrder'],
                'distance' => $shopDetail['distance'],
                'show_distance' => $this->showDistance($shopDetail['distance']),
                'have_bought' => $haveBought, // 是否买过
                'count_already_buy_times' => $countAlreadyBuyTimes, // 已消费次数
                'count_also_need_buy_times' => $countAlsoNeedBuyTimes, // 还需消费次数
                'count_normal_order' => $countNormalOrder, // 消费次数(普通订单)
                'count_free_order' => $countFreeOrder, // 消费次数(免单订单)
                'last_free_order_list' => $shopLastFreeOrderList, // 最近免单
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取店铺详情接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

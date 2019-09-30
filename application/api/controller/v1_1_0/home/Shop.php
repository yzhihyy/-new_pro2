<?php

namespace app\api\controller\v1_1_0\home;

use app\api\Presenter;
use app\api\logic\v1_1_0\shop\PhotoAlbum as PhotoAlbumLogic;

class Shop extends Presenter
{
    /**
     * 获取商家活动列表
     *
     * @return \think\response\Json
     */
    public function getShopActivityList()
    {
        try {
            /**
             * @var \app\api\model\User $userModel
             * @var \app\api\model\v1_1_0\Shop $shopModel
             * @var \app\api\model\v1_1_0\ShopActivity $shopActivityModel
             * @var \app\api\model\v1_1_0\PhotoAlbum $photoAlbumModel
             */
            $userModel = model('api/User');
            $shopModel = model('api/v1_1_0/Shop');
            $shopActivityModel = model('api/v1_1_0/ShopActivity');
            $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            // 页码
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            // 店铺id
            $shopId = input('shop_id/d', 0);
            // 如果shopId为0，则说明是商家查看自己的活动列表
            if (!$shopId) {
                $shopId = $this->getShopId($userModel);
                if (!$shopId) {
                    return apiError(config('response.msg10'));
                }
            }
            // 获取商家信息
            if ($shopId < 0) {
                list($code, $msg) = explode('|', config('response.msg10'));
                return apiError($msg, $code);
            }
            $shop = $shopModel->getQuery()
                ->where([
                    'account_status' => 1,
                    'online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取商家活动列表
            $where = [
                'shopId' => $shopId,
                'page' => $pageNo,
                'limit' => config('parameters.page_size_level_2')
            ];
            $shopActivity = $shopActivityModel->getShopActivity($where);
            // 获取活动图片
            $shopIdArray = array_column($shopActivity, 'shopId');
            $shopActivityIdArray = array_column($shopActivity, 'shopActivityId');
            if ($shopIdArray) {
                $where = [
                    'shopIdArray' => $shopIdArray,
                    'shopActivityIdArray' => $shopActivityIdArray
                ];
                $shopActivityPhotoAlbum = $photoAlbumModel->getShopActivityPhotoAlbum($where);
                // 相册分组
                $logic = new PhotoAlbumLogic();
                $shopActivityPhotoAlbum = $logic->groupShopActivityPhotoAlbum($shopActivityPhotoAlbum);
            } else {
                $shopActivityPhotoAlbum = [];
            }
            // 返回数据
            $shopActivityList = [];
            foreach ($shopActivity as $activity) {
                $item = [];
                $item['activity_id'] = $activity['id']; // 活动id
                $item['activity_content'] = $activity['content']; // 活动内容
                $item['pageviews'] = $activity['pageviews']; // 浏览量
                //$item['generate_time'] = $activity['generateTime']; // 活动创建时间
                $item['generate_time'] = date('Y-m-d H:i', strtotime($activity['generateTime'])); // 活动创建时间
                // 活动相册
                $activityId = $activity['id'];
                $activityPhotoAlbum = isset($shopActivityPhotoAlbum[$activityId]) ? $shopActivityPhotoAlbum[$activityId] : [];
                $item['activity_photo_album'] = isset($activityPhotoAlbum[0]['thumbImage']) ? $activityPhotoAlbum[0]['thumbImage'] : '';
                // h5分享链接
                $item['share_url'] = config('app_host') . '/h5/activityDetail.html?activity_id=' . $activity['id'];
                array_push($shopActivityList, $item);
            }
            $responseData = [
                'shop_activity_list' => $shopActivityList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家活动列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取商家活动详情
     *
     * @return \think\response\Json
     */
    public function getShopActivityDetail()
    {
        /**
         * @var \app\api\model\v1_1_0\ShopActivity $shopActivityModel
         * @var \app\api\model\v1_1_0\PhotoAlbum   $photoAlbumModel
         */
        try {
            // 参数校验
            $paramsArray = input();
            $validate = validate('api/v1_1_0/Shop');
            $checkResult = $validate->scene('getShopActivityDetail')->check($paramsArray);
            if (!$checkResult) {
                return apiError($validate->getError());
            }
            // 获取店铺活动详情
            $shopActivityModel = model('api/v1_1_0/ShopActivity');
            $where = [
                'latitude' => $paramsArray['latitude'],
                'longitude' => $paramsArray['longitude'],
                'shopActivityId' => $paramsArray['activity_id']
            ];
            $shopActivityInfo = $shopActivityModel->getShopActivityInfo($where);
            if (empty($shopActivityInfo)) {
                return apiError(config('response.msg41'));
            }
            // 获取活动相册
            $shopIdArray = [$shopActivityInfo['shopId']];
            $shopActivityIdArray = [$shopActivityInfo['shopActivityId']];
            $where = [
                'shopIdArray' => $shopIdArray,
                'shopActivityIdArray' => $shopActivityIdArray,
            ];
            $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            $shopActivityPhotoAlbum = $photoAlbumModel->getShopActivityPhotoAlbum($where);
            $photoAlbum = [];
            foreach ($shopActivityPhotoAlbum as $item) {
                array_push($photoAlbum, getImgWithDomain($item['image']));
            }
            // 活动浏览量+1
            $data = [
                'pageviews' => $shopActivityModel->raw('pageviews + 1')
            ];
            $where = [
                'id' => $shopActivityInfo->id
            ];
            $shopActivityModel::update($data, $where);
            // 返回数据
            $responseData = [
                'shop_name' => $shopActivityInfo['shopName'],
                'shop_thumb_image' => getImgWithDomain($shopActivityInfo['shopThumbImage']),
                'shop_category_name' => $shopActivityInfo['shopCategoryName'],
                'shop_address_poi' => $shopActivityInfo['shopAddressPoi'],
                'distance' => $shopActivityInfo['distance'],
                'show_distance' => $this->showDistance($shopActivityInfo['distance']),
                'content' => $shopActivityInfo['content'],
                'pageviews' => $shopActivityInfo['pageviews'],
                //'generate_time' => $shopActivityInfo['generateTime'],
                'generate_time' => date('Y-m-d H:i', strtotime($shopActivityInfo['generateTime'])),
                'activity_photo_album_list' => $photoAlbum,
                'share_url' => config('app_host') . '/h5/activityDetail.html?activity_id=' . $paramsArray['activity_id'],
                'app_share_url' => config('app_host') . '/h5/activityDetail.html?activity_id=' . $paramsArray['activity_id'] . '&latitude=' . $paramsArray['latitude'] . '&longitude=' . $paramsArray['longitude'] . '&isApp=1'
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家活动详情接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取商家推荐列表
     *
     * @return \think\response\Json
     */
    public function getShopRecommendList()
    {
        try {
            /**
             * @var \app\api\model\User $userModel
             * @var \app\api\model\v1_1_0\Shop $shopModel
             * @var \app\api\model\v1_1_0\PhotoAlbum $photoAlbumModel
             */
            $userModel = model('api/User');
            $shopModel = model('api/v1_1_0/Shop');
            $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            // 页码
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $shopId = input('shop_id/d', 0);
            // 如果shopId为0，则说明是商家查看自己的推荐列表
            if (!$shopId) {
                $shopId = $this->getShopId($userModel);
                if (!$shopId) {
                    return apiError(config('response.msg10'));
                }
            }
            // 获取商家信息
            if ($shopId < 0) {
                list($code, $msg) = explode('|', config('response.msg10'));
                return apiError($msg, $code);
            }
            $shop = $shopModel->getQuery()
                ->where([
                    'account_status' => 1,
                    'online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取列表
            $where = [
                'shopId' => $shopId,
                'page' => $pageNo,
                'limit' => config('parameters.page_size_level_2')
            ];
            $recommendPhotoAlbum = $photoAlbumModel->getRecommendPhotoAlbum($where);
            // 返回数据
            $shopRecommendList = [];
            foreach ($recommendPhotoAlbum as $info) {
                $item = [];
                $item['photo_id'] = $info['id'];
                $item['name'] = $info['name'];
                $item['image'] = getImgWithDomain($info['image']);
                $item['thumb_image'] = getImgWithDomain($info['thumbImage']);
                array_push($shopRecommendList, $item);
            }
            $responseData = [
                'shop_recommend_list' => $shopRecommendList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家推荐列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取商家相册列表
     *
     * @return \think\response\Json
     */
    public function getShopPhotoAlbumList()
    {
        try {
            /**
             * @var \app\api\model\User $userModel
             * @var \app\api\model\v1_1_0\Shop $shopModel
             * @var \app\api\model\v1_1_0\PhotoAlbum $photoAlbumModel
             */
            $userModel = model('api/User');
            $shopModel = model('api/v1_1_0/Shop');
            $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            // 页码
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $shopId = input('shop_id/d', 0);
            // 如果shopId为0，则说明是商家查看自己的推荐列表
            if (!$shopId) {
                $shopId = $this->getShopId($userModel);
                if (!$shopId) {
                    return apiError(config('response.msg10'));
                }
            }
            // 获取商家信息
            if ($shopId < 0) {
                list($code, $msg) = explode('|', config('response.msg10'));
                return apiError($msg, $code);
            }
            $shop = $shopModel->getQuery()
                ->where([
                    'account_status' => 1,
                    'online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取列表
            $where = [
                'shopId' => $shopId,
                'page' => $pageNo,
                'limit' => config('parameters.page_size_level_2')
            ];
            $shopPhotoAlbum = $photoAlbumModel->getShopPhotoAlbum($where);
            // 返回数据
            $shopPhotoAlbumList = [];
            foreach ($shopPhotoAlbum as $info) {
                $item = [];
                $item['photo_id'] = $info['id'];
                $item['name'] = $info['name'];
                $item['image'] = getImgWithDomain($info['image']);
                $item['thumb_image'] = getImgWithDomain($info['thumbImage']);
                array_push($shopPhotoAlbumList, $item);
            }
            $responseData = [
                'shop_photo_album_list' => $shopPhotoAlbumList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家推荐列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取店铺ID
     *
     * @param \app\api\model\User $userModel
     *
     * @throws
     *
     * @return bool
     */
    private function getShopId($userModel)
    {
        $userId = $this->getUserId();
        if (empty($userId) || $userId <= 0) {
            return false;
        }
        $user = $userModel->getUserAndShop($userId);
        if (!$user) {
            return false;
        }
        if (!($user['shop_online_status'] == 1 && $user['shop_account_status'] == 1)) {
            return false;
        }
        return $user['shop_id'];
    }

    /**
     * 获取店铺列表
     *
     * @return \think\response\Json
     */
    public function getShopList()
    {
        /**
         * @var \app\api\model\v1_1_0\Shop $shopModel
         * @var \app\api\model\ShopCategory $shopCategoryModel
         */
        try {
            // 校验参数
            $paramsArray = input();
            $validate = validate('api/v1_1_0/Shop');
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
            $shopModel = model('api/v1_1_0/Shop');
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            // 获取店铺列表
            $resultList = [];
            $where = [
                'page' => $pageNo,
                'shop_category_id' => $paramsArray['shop_category_id'],
                'limit' => config('parameters.page_size_level_2'),
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude'],
                'sort' => $paramsArray['sort']
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
                $info['announcement'] = $shop['announcement'];
                $info['shop_image'] = getImgWithDomain($shop['shopImage']);
                $info['shop_thumb_image'] = getImgWithDomain($shop['shopThumbImage']);
                $info['shop_address'] = $shop['shopAddress'];
                $info['shop_address_poi'] = $shop['shopAddressPoi'];
                $info['shop_category_name'] = $shop['shopCategoryName'];
                $info['free_order_frequency'] = $shop['freeOrderFrequency'];
                $info['how_many_people_bought'] = $shop['howManyPeopleBought'];
                $info['order_count'] = $shop['countOrder'];
                $info['free_order_count'] = $shop['countFreeOrder'];
                $info['distance'] = $shop['distance'];
                $info['show_distance'] = $this->showDistance($shop['distance']);
                $info['have_bought'] = isset($shop['haveBought']) ? $shop['haveBought'] : 0;
                $info['count_also_need_buy_times'] = isset($shop['countAlsoNeedBuyTimes']) ? $shop['countAlsoNeedBuyTimes'] : 0;
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
         * @var \app\api\model\User $userModel
         * @var \app\api\model\v1_1_0\Shop $shopModel
         * @var \app\api\model\Order $orderModel
         * @var \app\api\model\FreeRule $freeRuleModel
         */
        try {
            // 校验参数
            $paramsArray = input();
            $validate = validate('api/v1_1_0/Shop');
            $checkResult = $validate->scene('getShopDetail')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 店铺id
            $shopId = input('shop_id/d', 0);
            // 如果shopId为0，则说明是商家查看自己的活动列表
            if (!$shopId) {
                $userModel = model('api/User');
                $shopId = $this->getShopId($userModel);
                if (!$shopId) {
                    return apiError(config('response.msg10'));
                }
                // 校验是否在其它设备登陆
                $token = $this->request->header('token');
                $userId = $this->getUserId();
                $user = $userModel->alias('u')
                    ->field('u.token')
                    ->find($userId);
                if ($user && $user['token'] != $token) {
                    list($code, $msg) = explode('|', config('response.msg18'));
                    return apiError($msg, $code);
                }
            }
            // 判断店铺是否存在
            if ($shopId < 0) {
                list($code, $msg) = explode('|', config('response.msg10'));
                return apiError($msg, $code);
            }
            $shopModel = model('api/v1_1_0/Shop');
            $shop = $shopModel->getQuery()
                ->where([
                    'account_status' => 1,
                    'online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取店铺详情
            $where = [
                'shop_id' => $shopId,
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude']
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            $shopDetail = $shopModel->getShopDetail($where);
            // 获取店铺最近订单记录
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
                $orderInfo['free_flag'] = $order['freeFlag']; // 支付金额
                $orderInfo['discount_money'] = decimalAdd(0, $order['paymentAmount']); // 支付金额
                array_push($shopLastFreeOrderList, $orderInfo);
            }
            // 获取消费信息（已消费几次，再消费几次可免单）
            $countAlreadyBuyTimes = isset($shopDetail['countAlreadyBuyTimes']) ? $shopDetail['countAlreadyBuyTimes'] : 0;
            $countAlsoNeedBuyTimes = isset($shopDetail['countAlsoNeedBuyTimes']) ? $shopDetail['countAlsoNeedBuyTimes'] : 0;;
            $haveBought = isset($shopDetail['haveBought']) ? $shopDetail['haveBought'] : 0;
            // 订单统计
            $countTotalOrder = isset($shopDetail['countTotalOrder']) ? $shopDetail['countTotalOrder'] : 0;
            $countNormalOrder = isset($shopDetail['countNormalOrder']) ? $shopDetail['countNormalOrder'] : 0;
            $countFreeOrder = isset($shopDetail['countFreeOrder']) ? $shopDetail['countFreeOrder'] : 0;
            // 商家相册列表
            $shopPhotoAlbumList = $this->getShopPhotoAlbumListOfShopDetail($shopId);
            // 商家推荐列表
            $shopRecommendList = $this->getShopRecommendListOfShopDetail($shopId);
            // 商家活动
            $shopActivityInfo = $this->getShopActivityListOfShop($shopId);
            // 响应信息
            $responseData = [
                'shop_id' => $shopDetail['shopId'],
                'shop_name' => $shopDetail['shopName'],
                'announcement' => $shopDetail['announcement'],
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
                'count_total_order' => $countTotalOrder, // 消费次数(全部订单)
                'count_normal_order' => $countNormalOrder, // 消费次数(普通订单)
                'count_free_order' => $countFreeOrder, // 消费次数(免单订单)
                'last_free_order_list' => $shopLastFreeOrderList, // 最近免单
                'shop_photo_album_list' => $shopPhotoAlbumList, // 商家相册
                'shop_recommend_list' => $shopRecommendList, // 商家推荐
                'shop_activity_info' => $shopActivityInfo, // 商家活动
                'share_url' => config('app_host') . '/h5/storeDetail.html?shop_id=' . $shopId
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取店铺详情接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取店铺详情的商家相册，限制返回6张
     *
     * @param $shopId
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getShopPhotoAlbumListOfShopDetail($shopId)
    {
        /**
         * @var \app\api\model\v1_1_0\PhotoAlbum $photoAlbumModel
         */
        $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
        $shopPhotoAlbum = $photoAlbumModel->getQuery()
            ->where([
                'shop_id' => $shopId,
                'type' => 1,
                'status' => 1
            ])
            ->order(['id' => 'desc'])
            ->limit(0, 6)
            ->select();

        $shopPhotoAlbumList = [];
        foreach ($shopPhotoAlbum as $info) {
            $item = [];
            $item['photo_id'] = $info['id'];
            $item['name'] = $info['name'];
            $item['image'] = getImgWithDomain($info['image']);
            $item['thumb_image'] = getImgWithDomain($info['thumbImage']);
            array_push($shopPhotoAlbumList, $item);
        }
        return $shopPhotoAlbumList;
    }

    /**
     * 获取店铺详情的商家推荐，限制返回6张
     *
     * @param $shopId
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getShopRecommendListOfShopDetail($shopId)
    {
        /**
         * @var \app\api\model\v1_1_0\PhotoAlbum $photoAlbumModel
         */
        $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
        $shopPhotoAlbum = $photoAlbumModel->getQuery()
            ->where([
                'shop_id' => $shopId,
                'type' => 2,
                'status' => 1
            ])
            ->order(['id' => 'desc'])
            ->limit(0, 6)
            ->select();

        $shopPhotoAlbumList = [];
        foreach ($shopPhotoAlbum as $info) {
            $item = [];
            $item['photo_id'] = $info['id'];
            $item['name'] = $info['name'];
            $item['image'] = getImgWithDomain($info['image']);
            $item['thumb_image'] = getImgWithDomain($info['thumbImage']);
            array_push($shopPhotoAlbumList, $item);
        }
        return $shopPhotoAlbumList;
    }

    /**
     * 获取店铺详情的商家活动，取最后一条
     *
     * @param $shopId
     *
     * @return array|object
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getShopActivityListOfShop($shopId)
    {
        /**
         * @var \app\api\model\v1_1_0\ShopActivity $shopActivityModel
         * @var \app\api\model\v1_1_0\PhotoAlbum $photoAlbumModel
         */
        $shopActivityModel = model('api/v1_1_0/ShopActivity');
        $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
        // 获取最新一条活动
        $shopActivity = $shopActivityModel->getQuery()
            ->where([
                'shop_id' => $shopId,
                'status' => 1
            ])
            ->order([
                'id' => 'desc'
            ])
            ->find();
        if (empty($shopActivity)) {
            return (object)[];
        }
        // 获取活动相册
        $shopIdArray = [$shopId];
        $shopActivityIdArray = [$shopActivity['shopActivityId']];
        $where = [
            'shopIdArray' => $shopIdArray,
            'shopActivityIdArray' => $shopActivityIdArray,
        ];
        $shopActivityPhotoAlbum = $photoAlbumModel->getShopActivityPhotoAlbum($where);
        $activityPhotoAlbum = '';
        if ($shopActivityPhotoAlbum) {
            foreach ($shopActivityPhotoAlbum as $item) {
                $activityPhotoAlbum = getImgWithDomain($item['thumbImage']);
                break;
            }
        }
        // 返回数据
        $shopActivityInfo = [
            'activity_id' => $shopActivity['id'],
            'activity_content' => $shopActivity['content'],
            'pageviews' => $shopActivity['pageviews'],
            'generate_time' => date('Y-m-d H:i', strtotime($shopActivity['generateTime'])),
            'activity_photo_album' => $activityPhotoAlbum
        ];
        return $shopActivityInfo;
    }
}

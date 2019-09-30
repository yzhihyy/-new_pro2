<?php

namespace app\api\controller\v2_0_0\user;

use app\api\logic\v3_6_0\EasemobLogic;
use app\api\model\v3_0_0\ThemeActivityShopModel;
use app\api\model\v3_0_0\UserModel;
use app\api\Presenter;
use app\api\model\v2_0_0\{ShopModel, ShopCategoryModel, ShopRecommendModel, PhotoAlbumModel};
use app\api\validate\v2_0_0\ShopValidate;

class Shop extends Presenter
{
    /**
     * 根据店铺分类获取店铺列表
     *
     * @return \think\response\Json
     */
    public function getShopListByCategoryId()
    {
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(ShopValidate::class);
            $checkResult = $validate->scene('getShopListByCategoryId')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 校验分类是否存在
            /* @var ShopCategoryModel $shopCategoryModel */
            $shopCategoryModel = model(ShopCategoryModel::class);
            $shopCategory = $shopCategoryModel->find($paramsArray['shop_category_id']);
            if (empty($shopCategory)) {
                return apiError(config('response.msg19'));
            }
            // 获取店铺列表
            /* @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 查询条件
            $where = [
                'page' => $pageNo,
                'shopCategoryId' => $paramsArray['shop_category_id'],
                'limit' => config('parameters.page_size_level_2'),
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude'],
                'sort' => $paramsArray['sort']
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            // 获取店铺列表
            $resultList = [];
            $shopList = $shopModel->getShopListByCategoryId($where);
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
                $info['have_bought'] = $shop['haveBought'] ?? 0;
                $info['count_also_need_buy_times'] = $shop['countAlsoNeedBuyTimes'] ?? 0;
                $info['views'] = $shop['views'];
                $info['show_views'] = $this->showViews($shop['views']);
                array_push($resultList, $info);
            }
            // 响应数据
            $responseData = [
                'shop_list' => $resultList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '根据店铺分类获取店铺列表接口异常信息：' . $e->getMessage();
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
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(ShopValidate::class);
            $checkResult = $validate->scene('getShopDetail')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 判断店铺是否存在
            $shopId = $paramsArray['shop_id'];
            /* @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $shop = $shopModel->getQuery()
                ->where([
                    's.account_status' => 1,
                    's.online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取店铺详情
            $where = [
                'shopId' => $shopId,
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude']
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
                // 是否已经拉黑
                $userModel = model(UserModel::class);
                $isInBlackList = $userModel->isInBlackList([
                    'type' => 2,
                    'loginUserId' => $userId,
                    'shopId' => $shopId
                ]);
            }
            $shopDetail = $shopModel->getShopDetail($where);
            // 获取消费信息（已消费几次，再消费几次可免单）
            $countAlreadyBuyTimes = $shopDetail['countAlreadyBuyTimes'] ?? 0;
            $countAlsoNeedBuyTimes = $shopDetail['countAlsoNeedBuyTimes'] ?? 0;
            $haveBought = $shopDetail['haveBought'] ?? 0;
            // 订单统计
            $countMyTotalOrder = $shopDetail['countMyTotalOrder'] ?? 0;
            $countMyNormalOrder = $shopDetail['countMyNormalOrder'] ?? 0;
            $countMyFreeOrder = $shopDetail['countMyFreeOrder'] ?? 0;
            // 更新店铺浏览量
            $data = [
                'views' => $shopModel->raw('views + 1')
            ];
            $where = [
                'id' => $shopId
            ];
            $shopModel::update($data, $where);
            // 获取商家参加的主题（最新一个）
            $theme = model(ThemeActivityShopModel::class)
                ->alias('tas')
                ->join('theme_activity ta', 'tas.theme_id = ta.id and ta.delete_status = 0 and ta.theme_status = 2')
                ->field([
                    'ta.id',
                    'ta.id as themeId',
                    'ta.theme_title as themeTitle'
                ])
                ->where([
                    'tas.shop_id' => $shopId,
                    'tas.status' => 2,
                    'tas.delete_status' => 0
                ])
                ->order([
                    'tas.generate_time' => 'desc'
                ])
                ->find();
            $themeTitle = $theme['themeTitle'] ?? '';
            $themeId = $theme['themeId'] ?? 0;
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
                'count_total_order' => $countMyTotalOrder, // 消费次数(全部订单)
                'count_normal_order' => $countMyNormalOrder, // 消费次数(普通订单)
                'count_free_order' => $countMyFreeOrder, // 消费次数(免单订单)
                'share_url' => config('app_host') . '/h5/v3_6_0/storeDetail.html?shop_id=' . $shopId,
                'views' => $shopDetail['views'], // 浏览量
                'show_views' => $this->showViews($shopDetail['views']),
                'longitude' => $shopDetail['longitude'],
                'latitude' => $shopDetail['latitude'],
                'is_follow' => ($userId == $shopDetail['shopUserId']) ? 1 : ($shopDetail['isFollow'] ?? 0),
                'is_own_shop' => $userId == $shopDetail['shopUserId'] ? 1 : 0,
                'in_black_list' => (isset($isInBlackList) && $isInBlackList) ? 1 : 0,
                'pay_setting_type' => $shopDetail['paySettingType'],
                'theme_title' => $themeTitle,
                'theme_id' => $themeId,
                'social_info' => EasemobLogic::getSocialUserInfo($shopDetail['shopUserId']),
                // 店铺的用户信息
                'shop_user_id' => (int)$shopDetail['shopUserId'],
                'shop_user_avatar' => getImgWithDomain($shopDetail['shop_user_avatar']),
                'shop_user_thumb_avatar' => getImgWithDomain($shopDetail['shop_user_thumb_avatar']),
                'shop_user_nickname' => $shopDetail['shop_user_nickname'],
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取店铺详情接口异常信息：' . $e->getMessage();
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
            /* @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            /* @var ShopRecommendModel $shopRecommendModel */
            $shopRecommendModel = model(ShopRecommendModel::class);
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 获取商家信息
            $shopId = input('shop_id/d', 0);
            if ($shopId <= 0) {
                return apiError(config('response.msg10'));
            }
            $shop = $shopModel->getQuery()
                ->where([
                    's.account_status' => 1,
                    's.online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取商家推荐列表
            $where = [
                'shopId' => $shopId,
                'page' => $pageNo,
                'limit' => config('parameters.page_size_level_2')
            ];
            $shopRecommendList = $shopRecommendModel->getShopRecommendList($where);
            // 返回数据
            $shopRecommendArray = [];
            foreach ($shopRecommendList as $value) {
                $item = [];
                $item['recommend_id'] = $value['id']; // 推荐id
                $item['content'] = $value['content']; // 推荐内容
                $item['pageviews'] = $value['pageviews']; // 浏览量
                $item['generate_time'] = date('Y-m-d H:i', strtotime($value['generateTime'])); // 创建时间
                $item['image'] = getImgWithDomain($value['image']); // 封面
                $item['thumb_image'] = getImgWithDomain($value['thumbImage']); // 封面缩略图
                array_push($shopRecommendArray, $item);
            }
            $responseData = [
                'shop_recommend_list' => $shopRecommendArray
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家推荐列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 获取商家推荐详情
     *
     * @return \think\response\Json
     */
    public function getShopRecommendDetail()
    {
        try {
            // 参数校验
            $paramsArray = input();
            $validate = validate(ShopValidate::class);
            $checkResult = $validate->scene('getShopRecommendDetail')->check($paramsArray);
            if (!$checkResult) {
                return apiError($validate->getError());
            }
            // 获取店铺推荐详情
            /* @var ShopRecommendModel $shopRecommendModel */
            $shopRecommendModel = model(ShopRecommendModel::class);
            $where = [
                'latitude' => $paramsArray['latitude'],
                'longitude' => $paramsArray['longitude'],
                'shopRecommendId' => $paramsArray['recommend_id']
            ];
            $shopRecommendInfo = $shopRecommendModel->getShopRecommendInfo($where);
            if (empty($shopRecommendInfo)) {
                return apiError(config('response.msg41'));
            }
            // 获取推荐相册
            $where = [
                'shopId' => $shopRecommendInfo['shopId'],
                'type' => 4,
                'relationId' => $paramsArray['recommend_id']
            ];
            /* @var PhotoAlbumModel $photoAlbumModel */
            $photoAlbumModel = model(PhotoAlbumModel::class);
            $shopActivityPhotoAlbum = $photoAlbumModel->getShopPhotoAlbum($where);
            $photoAlbum = [];
            foreach ($shopActivityPhotoAlbum as $item) {
                array_push($photoAlbum, getImgWithDomain($item['image']));
            }
            // 推荐 浏览量+1
            $data = [
                'pageviews' => $shopRecommendModel->raw('pageviews + 1')
            ];
            $where = [
                'id' => $shopRecommendInfo['id']
            ];
            $shopRecommendModel::update($data, $where);
            // 返回数据
            $responseData = [
                'shop_name' => $shopRecommendInfo['shopName'],
                'shop_image' => getImgWithDomain($shopRecommendInfo['shopImage']),
                'shop_thumb_image' => getImgWithDomain($shopRecommendInfo['shopThumbImage']),
                'shop_category_name' => $shopRecommendInfo['shopCategoryName'],
                'shop_address_poi' => $shopRecommendInfo['shopAddressPoi'],
                'distance' => $shopRecommendInfo['distance'],
                'show_distance' => $this->showDistance($shopRecommendInfo['distance']),
                'content' => $shopRecommendInfo['content'],
                'pageviews' => $shopRecommendInfo['pageviews'],
                'generate_time' => date('Y-m-d H:i', strtotime($shopRecommendInfo['generateTime'])),
                'recommend_photo_album_list' => $photoAlbum,
                'share_url' => config('app_host') . '/h5/v2_0_0/activityDetail.html?recommend_id=' . $paramsArray['recommend_id'],
                'app_share_url' => config('app_host') . '/h5/v2_0_0/activityDetail.html?recommend_id=' . $paramsArray['recommend_id'] . '&latitude=' . $paramsArray['latitude'] . '&longitude=' . $paramsArray['longitude'] . '&isApp=1',
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家推荐详情接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use app\api\logic\v2_0_0\user\HomeLogic;
use app\api\model\v2_0_0\ShopModel;
use think\Response\Json;

class Home extends Presenter
{
    /**
     * 首页
     *
     * @return Json
     */
    public function index()
    {
        try {
            $homeLogic = new HomeLogic();
            // 获取banner列表
            $bannerArray = $homeLogic->getBannerList();
            // 获取店铺分类列表
            $shopCategoryArray = $homeLogic->getShopCategoryList();
            // 获取推荐商家
            $userId = $this->getUserId();
            $where = [];
            if ($userId) {
                $where['userId'] = $userId;
            }
            $homeRecommendShopArray = $homeLogic->getRecommendShopList($where);
            // 响应数据
            $responseData = [
                'banner_list' => $bannerArray,
                'shop_category_list' => $shopCategoryArray,
                'recommend_shop_list' => $homeRecommendShopArray
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '首页接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 猜你喜欢
     *
     * @return Json
     */
    public function joyList()
    {
        try {
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 获取猜你喜欢列表
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            $longitude = input('longitude/f');
            $latitude = input('latitude/f');
            $sort = input('sort/d', 0);
            if (!in_array($sort, [1, 2, 3])) {
                return apiError('排序方式错误');
            }
            $joyList = [];
            if ($longitude && $latitude) {
                $where = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'longitude' => $longitude,
                    'latitude' => $latitude,
                    'sort' => $sort
                ];
                $shopList = $shopModel->getJoyList($where);
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
                    array_push($joyList, $info);
                }
            }
            return apiSuccess(['joy_list' => $joyList]);
        } catch (\Exception $e) {
            $logContent = '猜你喜欢接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

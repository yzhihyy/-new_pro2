<?php

namespace app\api\controller\home;

use app\api\Presenter;

class Index extends Presenter
{
    /**
     * 首页
     *
     * @return \think\response\Json
     */
    public function index()
    {
        try {
            /**
             * @var \app\api\model\Banner $bannerModel
             * @var \app\api\model\ShopCategory $shopCategoryModel
             */
            $bannerModel = model('api/Banner');
            $shopCategoryModel = model('api/ShopCategory');
            // 获取banner列表
            $bannerList = $bannerModel->getBannerList([
                'position' => ['=', 1],
                'status' => ['=', 1]
            ]);
            $bannerArray = [];
            foreach ($bannerList as $banner) {
                $info = [];
                $info['banner_id'] = $banner['id'];
                $info['title'] = $banner['title'];
                $info['image'] = getImgWithDomain($banner['image']);
                $info['thumb_image'] = getImgWithDomain($banner['thumbImage']);
                $info['ad_link'] = $banner['adLink'];
                array_push($bannerArray, $info);
            }
            // 获取店铺分类列表
            $shopCategoryList = $shopCategoryModel->getShopCategoryList([
                'status' => ['=', 1]
            ]);
            $shopCategoryArray = [];
            foreach ($shopCategoryList as $shopCategory) {
                $info = [];
                $info['shop_id'] = $shopCategory['id'];
                $info['name'] = $shopCategory['name'];
                $info['image'] = getImgWithDomain($shopCategory['image']);
                $info['thumb_image'] = getImgWithDomain($shopCategory['thumbImage']);
                array_push($shopCategoryArray, $info);
            }
            // 响应数据
            $responseData = [
                'banner_list' => $bannerArray,
                'shop_category_list' => $shopCategoryArray
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
     * @return \think\response\Json
     */
    public function joyList()
    {
        try {
            /**
             * @var \app\api\model\Shop $shopModel
             */
            $shopModel = model('api/Shop');
            // 获取猜你喜欢列表
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $longitude = input('longitude/f');
            $latitude = input('latitude/f');
            $joyList = [];
            if ($longitude && $latitude) {
                $where = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'longitude' => $longitude,
                    'latitude' => $latitude,
                ];
                $userId = $this->getUserId();
                if ($userId) {
                    $where['userId'] = $userId;
                }
                $shopList = $shopModel->getJoyList($where);
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
                    array_push($joyList, $info);
                }
            }
            // 响应数据
            $responseData = [
                'joy_list' => $joyList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '首页-猜你喜欢接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

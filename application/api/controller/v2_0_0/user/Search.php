<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use app\api\model\v2_0_0\ShopModel;

class Search extends Presenter
{
    public function shop()
    {
        try {
            /* @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            $keyword = input('keyword/s', '');
            $longitude = input('longitude/f');
            $latitude = input('latitude/f');
            // 校验关键字
            if ($keyword === '') {
                return apiError(config('response.msg24'));
            }
            $shopList = [];
            if ($longitude && $latitude) {
                $where = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'keyword' => $keyword,
                    'longitude' => $longitude,
                    'latitude' => $latitude
                ];
                $userId = $this->getUserId();
                if ($userId) {
                    $where['userId'] = $userId;
                }
                $results = $shopModel->searchShopList($where);
                foreach ($results as $shop) {
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
                    array_push($shopList, $info);
                }
            }
            // 响应数据
            $responseData = [
                'shop_list' => $shopList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '搜索接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

<?php

namespace app\api\controller\home;

use app\api\Presenter;

class Search extends Presenter
{
    /**
     * 搜索
     *
     * @return \think\response\Json
     */
    public function shop()
    {
        /**
         * @var \app\api\model\Shop $shopModel
         */
        try {
            $shopModel = model('api/Shop');
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $keyword = input('keyword/s', '');
            $longitude = input('longitude/f');
            $latitude = input('latitude/f');
            // 校验关键字
            if (empty($keyword)) {
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

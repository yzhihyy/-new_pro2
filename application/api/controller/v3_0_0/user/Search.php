<?php

namespace app\api\controller\v3_0_0\user;

use app\api\logic\v3_4_0\user\SearchLogic;
use app\api\Presenter;
use app\api\model\v3_0_0\{VideoModel};
use app\api\model\v2_0_0\{ShopModel};
use app\api\logic\v3_0_0\user\FollowLogic;
use app\api\validate\v3_0_0\SearchValidate;
use app\api\logic\v3_7_0\user\SearchLogic AS SearchNicknameLogic;
class Search extends Presenter
{
    /**
     * 搜索
     *
     * @return \think\response\Json
     */
    public function shop()
    {
        try {
            // 获取请求参数并校验
            $paramsArray = input();
            $validate = validate(SearchValidate::class);
            $checkResult = $validate->scene('search')->check($paramsArray);
            if (!$checkResult) {
                return apiError($validate->getError());
            }

            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            $keyword = $paramsArray['keyword'];
            $longitude = $paramsArray['longitude'] ?? null;
            $latitude = $paramsArray['latitude'] ?? null;
            $type = $paramsArray['type']; // 搜索类型，1搜索视频，2搜索店铺， 3搜索主题， 4搜索文章
            // 搜索视频
            $videoList = [];
            if ($type == 1) {
                $where = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'keyword' => $keyword
                ];
                $userId = $this->getUserId();
                if ($userId) {
                    $where['userId'] = $userId;
                }
                /* @var VideoModel $videoModel */
                $videoModel = model(VideoModel::class);
                $results = $videoModel->searchVideo($where);
                $followLogic = new FollowLogic();
                $videoList = $followLogic->handleVideoList($results, $userId);
            }
            // 搜索店铺
            $shopList = [];
            if ($type == 2) {
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
                /* @var ShopModel $shopModel */
                $shopModel = model(ShopModel::class);
                $results = $shopModel->searchShopList($where);
                $shopList = $this->handleShopList($results);
            }
            //搜索主题
            if($type == 3){
                $params = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'keyword' => $keyword
                ];
                $themeList = SearchLogic::searchThemeList($params);
            }
            //搜索文章
            if($type == 4){
                $params = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'keyword' => $keyword
                ];
                $themeArticleList = SearchLogic::searchThemeArticleList($params);
            }
            //搜索用户
            if($type == 5) {
                $params = [
                    'page' => $pageNo,
                    'limit' => config('parameters.page_size_level_2'),
                    'keyword' => $keyword
                ];
                $searchUserList = SearchNicknameLogic::searchByNickname($params);
            }

            // 响应数据
            $responseData = [
                'video_list' => $videoList,
                'shop_list' => $shopList,
                'theme_list' => $themeList ?? [],
                'theme_article_list' => $themeArticleList ?? [],
                'user_list' => $searchUserList ?? [],

            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '搜索接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 处理店铺列表
     *
     * @param array $shopList
     *
     * @return array
     */
    private function handleShopList($shopList)
    {
        $return = [];
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
            array_push($return, $info);
        }
        return $return;
    }
}

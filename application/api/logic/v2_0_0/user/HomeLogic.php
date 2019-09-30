<?php

namespace app\api\logic\v2_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\{BannerModel, ShopCategoryModel, ShopModel, OrderModel};
use think\db\exception\{DataNotFoundException, ModelNotFoundException};
use think\exception\DbException;

class HomeLogic extends BaseLogic
{
    /**
     * 获取banner列表
     *
     * @return array
     *
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getBannerList()
    {
        /** @var BannerModel $bannerModel */
        $bannerModel = model(BannerModel::class);
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
            $info['ad_link_type'] = $banner['adLinkType'];
            $info['ad_link'] = $banner['adLink'];
            array_push($bannerArray, $info);
        }
        return $bannerArray;
    }

    /**
     * 获取店铺分类列表
     *
     * @return array
     *
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getShopCategoryList()
    {
        /** @var ShopCategoryModel $shopCategoryModel */
        $shopCategoryModel = model(ShopCategoryModel::class);
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
        return $shopCategoryArray;
    }

    /**
     * 获取推荐商家列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getRecommendShopList($where = [])
    {
        // 获取最近买过的商家
        $recentlyConsumedShopArray = [];
        if (isset($where['userId'])) {
            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $recentlyConsumedShopList = $orderModel->getMyRecentlyConsumedShop($where);
            foreach ($recentlyConsumedShopList as $shop) {
                $info = [];
                $info['shop_id'] = $shop['id'];
                $info['shop_name'] = $shop['shopName'];
                $info['image'] = getImgWithDomain($shop['shopImage']);
                array_push($recentlyConsumedShopArray, $info);
            }
        }
        // 如果最近买过的商家不足4个，获取后台推荐的商家
        if (count($recentlyConsumedShopArray) < 4) {
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $where = [];
            if ($recentlyConsumedShopArray) {
                $shopIdArray = array_column($recentlyConsumedShopArray, 'shop_id');
                $where = [
                    ['s.id', 'not in', $shopIdArray]
                ];
            }
            $homeShopRecommendList = $shopModel->getHomeRecommendList($where);
            $homeShopRecommendArray = [];
            foreach ($homeShopRecommendList as $shop) {
                $info = [];
                $info['shop_id'] = $shop['id'];
                $info['shop_name'] = $shop['shopName'];
                $info['image'] = getImgWithDomain($shop['recommendImage']);
                array_push($homeShopRecommendArray, $info);
            }
            // 合并结果
            $result = array_merge($recentlyConsumedShopArray, $homeShopRecommendArray);
            return array_slice($result, 0, 4);
        } else {
            return $recentlyConsumedShopArray;
        }
    }
}
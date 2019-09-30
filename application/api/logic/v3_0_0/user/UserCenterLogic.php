<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\ShopModel;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_0_0\VideoModel;

class UserCenterLogic extends BaseLogic
{

    /**
     * 获取个人中心信息.
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    public function getUserCenterInfo($where)
    {
        $userId = $where['userId'];
        // 获取用户
        $userModel = model(UserModel::class);
        $user = $userModel->alias('u')
            ->leftJoin('social_user su', 'u.id = su.user_id')
            ->field('u.*')
            ->field([
                'su.id as social_user_id',
                'su.hx_uuid as social_hx_uuid',
                'su.hx_username as social_hx_username'
            ])
            ->find($userId);

        // 是否已关注该用户
        $isFollow = 0;
        $selfUserId = $this->getUserId();
        if ($selfUserId) {
            $followRelationModel = model(FollowRelationModel::class);
            $res = $followRelationModel->alias('fr')
                ->field('id')
                ->where([
                    'fr.from_user_id' => $selfUserId,
                    'fr.to_user_id' => $userId,
                    'fr.rel_type' => 1
                ])
                ->find();
            $isFollow = $res ? 1 : 0;
        }

        // 获取用户的店铺列表
        $shopList = [];
        $shopModel = model(ShopModel::class);
        $result = $shopModel->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id')
            ->field([
                's.id as shopId',
                's.shop_name as shopName',
                's.shop_thumb_image as shopThumbImage',
                'sc.name as shopCategoryName',
                's.shop_address as shopAddress',
                's.shop_address_poi as shopAddressPoi',
                's.views',
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                 THEN 0 
                 ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                 END as distance", // 距离
            ])
            ->where([
                's.user_id' => $userId,
                's.account_status' => 1,
                's.online_status' => 1
            ])
            ->select();
        foreach ($result as $shop) {
            $info = [];
            $info['shop_id'] = $shop['shopId'];
            $info['shop_name'] = $shop['shopName'];
            $info['shop_thumb_image'] = getImgWithDomain($shop['shopThumbImage']);
            $info['shop_category_name'] = $shop['shopCategoryName'];
            $info['shop_address'] = $shop['shopAddress'];
            $info['shop_address_poi'] = $shop['shopAddressPoi'];
            $info['views'] = $shop['views'];
            $info['show_views'] = $this->showViews($shop['views']);
            $info['distance'] = $shop['distance'];
            $info['show_distance'] = $this->showDistance($shop['distance']);
            array_push($shopList, $info);
        }

        // 统计视频数量
        $videoModel = model(VideoModel::class);
        $videoCount = $videoModel->alias('v')
            ->join('user u', 'v.user_id = u.id')
            ->where([
                'v.user_id' => $userId,
                'v.shop_id' => 0,
                'v.status' => 1,
                'v.visible' => 1,
                'u.account_status' => 1
            ])
            ->count();

        // 统计粉丝数量
        $followRelationModel = model(FollowRelationModel::class);
        $fansCount = $followRelationModel->alias('fr')
            ->join('user u', 'fr.from_user_id = u.id and u.account_status = 1')
            ->where([
                'fr.to_user_id' => $userId,
                'fr.to_shop_id' => 0,
                'fr.rel_type' => 1,
            ])
            ->group('fr.from_user_id')
            ->count();

        // 统计关注数量
        $followRelationModel = model(FollowRelationModel::class);
        $followCount = $followRelationModel->alias('fr')
            ->leftJoin('user u', 'fr.to_user_id = u.id')
            ->leftJoin('shop s', 'fr.to_shop_id = s.id')
            ->where([
                'fr.from_user_id' => $userId,
                'fr.rel_type' => 1,
            ])
            ->whereRaw("case when fr.to_user_id > 0 
            then u.account_status = 1
            else s.account_status = 1 and s.online_status = 1
            end")
            ->count();

        // 统计获赞数量
        $likeCount = $videoModel->where(['user_id' => $userId, 'shop_id' => 0, 'status' => 1])->sum('like_count');

        // 返回结果
        return [
            'user_info' => [
                'nickname' => $user->nickname,
                'avatar' => getImgWithDomain($user->thumb_avatar),
                'social_user_id' => $user->social_user_id,
                'social_hx_uuid' => $user->social_hx_uuid,
                'social_hx_username' => $user->social_hx_username
            ],
            'is_follow' => $isFollow,
            'shop_list' => $shopList,
            'video_count' => $videoCount,
            'fans_count' => $fansCount,
            'follow_count' => $followCount,
            'like_count' => $likeCount,
        ];
    }

    public function returnUserVideoCount($videoCountList)
    {
        $return = [];
        foreach ($videoCountList as $item) {
            $info = [
                'videoCount' => $item['videoCount']
            ];
            $return[$item['userId']] = $info;
        }
        return $return;
    }

    public function returnUserFansCount($fansCountList)
    {
        $return = [];
        foreach ($fansCountList as $item) {
            $info = [
                'fansCount' => $item['fansCount']
            ];
            $return[$item['userId']] = $info;
        }
        return $return;
    }

    public function returnUserLikeCount($likeCountList)
    {
        $return = [];
        foreach ($likeCountList as $item) {
            $info = [
                'likeCount' => $item['likeCount']
            ];
            $return[$item['userId']] = $info;
        }
        return $return;
    }
}

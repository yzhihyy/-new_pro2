<?php

namespace app\api\logic\v3_7_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\SettingModel;
use app\api\model\v3_0_0\ShopModel;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_0_0\TagModel;
use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_0_0\VideoModel;
use app\api\model\v3_7_0\AnchorUserModel;
use app\api\model\v3_7_0\UserCoverModel;
use app\api\model\v3_7_0\UserTagModel;
use app\common\utils\string\StringHelper;

class UserCenterLogic extends BaseLogic
{

    /**
     * 获取用户主页信息.
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
        $user = $this->getUserInfo($userId, $where['latitude'], $where['longitude']);

        // 获取用户封面列表
        $coverList = $this->getUserCoverList($userId);

        // 是否已关注该用户
        $isFollow = $this->isFollow($userId);

        // 获取用户的店铺列表
        $shopList = $this->getUserShopList($userId, $where['latitude'], $where['longitude']);

        // 统计视频数量
        $videoCount = $this->countUserVideo($userId);

        // 统计粉丝数量
        $fansCount = $this->countFans($userId);

        // 统计关注数量
        $followCount = $this->countFollow($userId);

        // 统计获赞数量
        $likeCount = $this->countLike($userId);

        // 兴趣爱好
        $all_hobby = $this->getTagList(2);
        $selected_hobby = $this->getUserTagList($userId, 2);

        // 个性标签
        $all_personalityLabel = $this->getTagList(3);
        $selected_personalityLabel = $this->getUserTagList($userId, 3);

        // 返回结果
        return [
            'user_info' => [
                'user_id' => $userId,
                'nickname' => $user->nickname,
                'avatar' => getImgWithDomain($user->avatar),
                'gender' => $user->gender,
                'age' => $user->age,
                'constellation' => $user->constellation,
                'height' => $user->height,
                'weight' => $user->weight,
                'hometown' => $user->hometown,
                'introduction' => $user->introduction,
                'longitude' => $user->longitude,
                'latitude' => $user->latitude,
                'location' => $user->location,
                'distance' => $user->distance,
                'show_distance' => $this->showDistance($user->distance),
                'social_user_id' => $user->social_user_id,
                'social_hx_uuid' => $user->social_hx_uuid,
                'social_hx_username' => $user->social_hx_username,
                'social_hx_nickname' => $user->social_hx_nickname,
                'social_hx_password' => $user->social_hx_password,
                'cover_list' => $coverList,
                'anchor_id' => $this->myAnchorId($userId),
                'identity_check_status' => $user->identity_check_status,
            ],
            'is_follow' => $isFollow,
            'shop_list' => $shopList,
            'video_count' => $videoCount,
            'fans_count' => $fansCount,
            'follow_count' => $followCount,
            'like_count' => $likeCount,
            'all_hobby' => $all_hobby,
            'selected_hobby' => $selected_hobby,
            'all_personality_label' => $all_personalityLabel,
            'selected_personality_label' => $selected_personalityLabel,
        ];
    }

    private function getUserInfo($userId, $latitude = null, $longitude = null)
    {
        $userModel = model(UserModel::class);
        $query = $userModel->alias('u')
            ->leftJoin('social_user su', 'u.id = su.user_id')
            ->field('u.*')
            ->field([
                'su.id as social_user_id',
                'su.hx_uuid as social_hx_uuid',
                'su.hx_username as social_hx_username',
                'su.hx_nickname as social_hx_nickname',
                'su.hx_password as social_hx_password',
            ]);
        if (($latitude == null) || ($longitude == null)) {
            $query->field("0 as distance");
        } else {
            $query->field("CASE WHEN (u.latitude is null or u.longitude is null) 
            THEN 0 
            ELSE (GLength(GeomFromText(CONCAT('LineString({$latitude} {$longitude},',u.latitude,' ',u.longitude,')')))/0.0000092592666666667) 
            END as distance");
        }
        $user = $query->find($userId);
        return $user;
    }

    private function getUserCoverList($userId)
    {
        $userCoverModel = new UserCoverModel();
        $result = $userCoverModel->getUserCoverList(['userId' => $userId]);
        $coverList = [];
        foreach ($result as $item) {
            $cover = getImgWithDomain($item['cover']);
            $coverList[] = $cover;
        }
        return $coverList;
    }

    private function isFollow($userId)
    {
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
        return $isFollow;
    }

    private function getUserShopList($userId, $latitude = null, $longitude = null)
    {
        $shopList = [];
        $shopModel = model(ShopModel::class);
        $query = $shopModel->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id')
            ->field([
                's.id as shopId',
                's.shop_name as shopName',
                's.shop_thumb_image as shopThumbImage',
                'sc.name as shopCategoryName',
                's.shop_address as shopAddress',
                's.shop_address_poi as shopAddressPoi',
                's.views',
            ])
            ->where([
                's.user_id' => $userId,
                's.account_status' => 1,
                's.online_status' => 1
            ]);
        if (($latitude == null) || ($longitude == null)) {
            $query->field("0 as distance");
        } else {
            $query->field("CASE WHEN (s.latitude is null or s.longitude is null) 
            THEN 0 
            ELSE (GLength(GeomFromText(CONCAT('LineString({$latitude} {$longitude},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667) 
            END as distance");
        }
        $result = $query->select();
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
        return $shopList;
    }

    private function countUserVideo($userId)
    {
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
        return $videoCount;
    }

    private function countFans($userId)
    {
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
        return $fansCount;
    }

    private function countFollow($userId)
    {
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
        return $followCount;
    }

    private function countLike($userId)
    {
        $videoModel = model(VideoModel::class);
        $likeCount = $videoModel->where([
            'user_id' => $userId,
            'shop_id' => 0,
            'status' => 1
        ])->sum('like_count');
        return $likeCount;
    }

    private function getUserTagList($userId, $type)
    {
        $model = new UserTagModel();
        $query = $model->alias('ut')
            ->join('tag t', 'ut.tag_id = t.id and ut.tag_type = t.tag_type')
            ->field([
                't.id as tag_id',
                't.tag_name',
            ])
            ->where([
                ['ut.user_id', '=', $userId],
                ['ut.tag_type', '=', $type],
            ]);
        $tagList = [];
        $result = $query->select();
        foreach ($result as $item) {
            $tag = [
                'tag_id' => $item['tag_id'],
                'tag_name' => $item['tag_name'],
            ];
            array_push($tagList, $tag);
        }
        return $tagList;
    }

    private function getTagList($type)
    {
        $model = new TagModel();
        $query = $model->alias('t')
            ->field([
                't.id as tag_id',
                't.tag_name'
            ])
            ->where([
                ['t.tag_type', '=', $type]
            ]);
        $tagList = [];
        $result = $query->select();
        foreach ($result as $item) {
            $tag = [
                'tag_id' => $item['tag_id'],
                'tag_name' => $item['tag_name'],
            ];
            array_push($tagList, $tag);
        }
        return $tagList;
    }


    /**
     * 随记生成6位不重复邀请码
     * @param int $limit
     * @return string
     * @throws
     */
    public static function randStr($limit = 6)
    {
        $model = model(UserModel::class);
        while(true){
            $inviteCode = StringHelper::uniqueCode($limit);
            $find = $model->where('invite_code', $inviteCode)->field('id')->find();
            if(empty($find)){
                return $inviteCode;
            }
        }
    }

    /**
     * 获取邀请一个好友所赠送的铜板数
     * @return int|mixed
     */
    public static function userInviteCopper()
    {
        $model = model(SettingModel::class);
        $result = $model->where(['group' => 'user_invite', 'key' => 'copper'])->value('value');
        if(!empty($result)){
            return $result;
        }
        return 0;
    }

    private function myAnchorId($userId)
    {
        return model(AnchorUserModel::class)->where('user_id', $userId)->value('id') ?: 0;
    }
}

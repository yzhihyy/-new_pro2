<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\TagModel;
use app\api\model\v3_0_0\UserTagRelationModel;
use app\api\model\v3_0_0\VideoModel;
use app\api\logic\shop\SettingLogic;

class HomeLogic extends BaseLogic
{
    /**
     * 获取首页视频列表
     *
     * @param array $paramsArray
     * @param int|null $userId
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHomeVideoList(array $paramsArray, ?int $userId)
    {
        // 查询条件
        $condition = [
            'userId' => $userId,
            'page' => $paramsArray['page'] ?? 0,
        ];
        // 指定城市
        if ($paramsArray['type'] == 2) {
            $condition['cityId'] = $paramsArray['city_id'];
        }

        /** @var VideoModel $videoModel */
        $videoModel = model(VideoModel::class);
        // 首页视频显示规则
        $displayRule = config('parameters.home_video_display_rule');
        // 要获取的视频总数
        $videoTotal = array_sum($displayRule);

        // TODO APP 审核时的视频显示规则：统一显示指定标签下的视频
        $reviewDisplayRule = config('parameters.home_video_display_rule_for_review');
        if ($reviewDisplayRule['enable_flag']) {
            $platform = $this->request->header('platform');
            $version = $this->request->header('version');
            if (in_array($platform, $reviewDisplayRule['platform']) && version_compare($version, $reviewDisplayRule['version']) == 0) {
                // 获取指定的标签视频
                $condition['type'] = 4;
                $condition['limit'] = $videoTotal;
                // 获取指定标签的ID
                /** @var TagModel $tagModel */
                $tagModel = model(TagModel::class);
                $tagIdArray = $tagModel->where('tag_type', 1)->whereIn('tag_name', $reviewDisplayRule['tagNames'])->column('id');
                $condition['tagIds'] = implode(',', array_values($tagIdArray));
                $videoList = $videoModel->getHomeVideoList($condition);
                if (empty($videoList)) {
                    $condition['contain_history_flag'] = true;
                    $videoList = $videoModel->getHomeVideoList($condition);
                }

                return [
                    'video_flag' => 1,
                    'video_list' => $this->transformVideoData($videoList)
                ];
            }
        }

        // 获取用户喜好的标签
        if (!empty($userId)) {
            /** @var UserTagRelationModel $userTagRelationModel */
            $userTagRelationModel = model(UserTagRelationModel::class);
            $userLikeTag = $userTagRelationModel->getUserLikeTag(['userId' => $userId]);
            $userLikeTag = array_column($userLikeTag, 'tagId');
            $condition['tagIds'] = implode(',', $userLikeTag);
        }

        $videoListFun = function () use ($userId, $videoModel, &$condition, $displayRule, $videoTotal) {
            // 获取系统推荐视频
            $condition['type'] = 1;
            $condition['limit'] = $displayRule[0];
            $recommendVideoList = $videoModel->getHomeVideoList($condition);

            // 用户喜好的标签视频ID数组
            $userLikeVideoList = $userLikeVideoIdArray = [];
            if (!empty($userId)) {
                $condition['type'] = 2;
                $condition['limit'] = $displayRule[1];

                // 获取用户喜好的标签视频
                $userLikeVideoList = $videoModel->getHomeVideoList($condition);
                $userLikeVideoIdArray = array_column($userLikeVideoList, 'id');
            }

            // 获取普通视频
            $condition['type'] = 3;
            $condition['limit'] = $videoTotal - count($recommendVideoList) - count($userLikeVideoIdArray);
            $condition['videoIds'] = $userLikeVideoIdArray;
            $ordinaryVideoList = $videoModel->getHomeVideoList($condition);

            return array_merge($recommendVideoList, $userLikeVideoList, $ordinaryVideoList);
        };

        $videoList = $videoListFun();
        if (empty($videoList)) {
            $condition['contain_history_flag'] = true;
            $videoList = $videoListFun();
        }

        return [
            // TODO video_flag 兼容V3.0.0
            'video_flag' => 1,
            'video_list' => $this->transformVideoData($videoList)
        ];
    }

    /**
     * 转换视频数据
     *
     * @param array $videoList
     * @param int $type
     * @return array
     */
    public function transformVideoData(array $videoList, int $type = 1)
    {
        $result = [];
        $transform = function ($video) {
            // 店铺设置逻辑
            $settingLogic = new SettingLogic();
            // 店铺设置转换
            $relatedShopSetting = $settingLogic->settingTransform($video['relatedShopSetting']);
            $result = [
                'video_id' => $video['videoId'],
                'video_user_id' => $video['videoUserId'],
                'video_type' => $video['videoShopId'] > 0 ? 2 : 1,
                'type' => $video['type'],
                'title' => $video['title'],
                'cover_url' => $video['coverUrl'],
                'video_url' => $video['videoUrl'],
                'video_width' => $video['videoWidth'],
                'video_height' => $video['videoHeight'],
                'like_count' => $video['likeCount'],
                'comment_count' => $video['commentCount'],
                'share_count' => $video['shareCount'],
                'nickname' => $video['videoShopId'] > 0 ? $video['shopName'] : $video['nickname'],
                'avatar' => $video['videoShopId'] > 0 ? getImgWithDomain($video['shopThumbImage']) : getImgWithDomain($video['thumbAvatar']),
                'gender' => $video['gender'],
                'age' => $video['age'],
                'location' => $video['location'],
                'shop_id' => (int)$video['shopId'],
                'is_follow' => ($this->getUserId() == $video['videoUserId']) ? 1 : ($video['isFollow'] ?? 0),
                'is_like' => $video['isLike'] ?? 0,
                'share_url' => config('app_host') . '/h5/v3_7_0/meetingVideo.html?video_id=' . $video['videoId'],
                'share_image' => $video['coverUrl'],
                'related_shop_id' => (int)$video['relatedShopId'],
                'related_shop_phone' => (string)$video['relatedShopPhone'],
                'related_shop_name' => (string)$video['relatedShopName'],
                'related_shop_image' => getImgWithDomain((string)$video['relatedShopImage']),
                'related_shop_thumb_image' => getImgWithDomain((string)$video['relatedShopThumbImage']),
                'related_shop_address' => (string)$video['relatedShopAddress'],
                'related_longitude' => (string)$video['relatedShopLongitude'],
                'related_latitude' => (string)$video['relatedShopLatitude'],
                'related_qq' => (string)$video['relatedShopQq'],
                'related_wechat' => (string)$video['relatedShopWechat'],
                'related_pay_setting_type' => (int)$video['related_pay_setting_type'],
                'show_send_sms' => $relatedShopSetting['show_send_sms'],
                'show_phone' => $relatedShopSetting['show_phone'],
                'show_enter_shop' => $relatedShopSetting['show_enter_shop'],
                'show_address' => $relatedShopSetting['show_address'],
                'show_wechat' => $relatedShopSetting['show_wechat'],
                'show_qq' => $relatedShopSetting['show_qq'],
                'show_payment' => $relatedShopSetting['show_payment'],
                'audit_status' => $video['auditStatus'],
            ];
            // TODO 兼容V3.0.0
            return array_merge($result, [
                'shop_name' => $result['nickname'],
                'shop_image' => $result['avatar'],
                'shop_thumb_image' => $result['avatar'],
            ]);
        };

        if ($type == 1) {
            foreach ($videoList as $video) {
                $result[] = $transform($video);
            }
        } else {
            $result = $transform($videoList);
        }

        return $result;
    }
}

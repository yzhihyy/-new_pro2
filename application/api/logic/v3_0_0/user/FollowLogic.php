<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\logic\v3_6_0\EasemobLogic;
use app\api\model\v2_0_0\{
    ShopModel, UserModel
};
use app\api\model\v3_0_0\{FollowRelationModel, UserMessageModel, VideoAlbumModel, VideoModel};
use app\common\utils\string\StringHelper;
use app\api\logic\shop\SettingLogic;

class FollowLogic extends BaseLogic
{
    /**
     * 处理视频列表
     * @param $videoList
     * @param int $userId
     * @param bool $commentFlag
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function handleVideoList($videoList, $userId = 0, $commentFlag = true)
    {
        $result = [];

        $infoList = [];
        if ($commentFlag) {
            // 根据视频ID获取点赞最多的一条评论
            $videoIdArr = array_column($videoList->toArray(), 'videoId');
            $infoList = model(VideoModel::class)->getVideoCommentLikeMostInfo([
                'videoIdArr' => $videoIdArr,
                'userId' => $userId
            ]);
            $infoList = array_column($infoList->toArray(), null, 'videoId');
        }

        $albumModel = model(VideoAlbumModel::class);
        $shopModel = model(ShopModel::class);
        $appHost = config('app_host');
        // 店铺设置逻辑
        $settingLogic = new SettingLogic();
        foreach ($videoList as $video) {
            $info = [];
            $info['video_id'] = $video['videoId'];              // 视频ID
            $info['video_type'] = $video['videoType'];          // 视频归属类型 1:用户,2:商家
            $info['type'] = $video['type'];                     // 视频类型 1:视频,2:随记
            $info['video_user_id'] = $video['videoUserId'];     // 视频用户ID
            $info['nickname'] = $video['nickname'];             // 用户昵称
            $info['avatar'] = getImgWithDomain($video['avatar']);// 用户头像
            $info['gender'] = $video['gender'] ?? 0;
            $info['age'] = $video['age'] ?? 0;
            $info['video_title'] = $video['videoTitle'];        // 视频标题
            $info['video_content'] = $video['videoContent'];    // 视频简介
            $info['video_url'] = $video['videoUrl'];            // 视频播放地址
            $info['cover_url'] = $video['coverUrl'];            // 视频封面
            $info['video_width'] = $video['videoWidth'];        // 视频宽度
            $info['video_height'] = $video['videoHeight'];      // 视频高度
            $info['location'] = $video['videoLocation'];        // 发布视频的地理位置
            $info['like_count'] = $video['likeCount'];          // 视频点赞数量
            $info['comment_count'] = $video['commentCount'];    // 视频评论数量
            $info['share_count'] = $video['shareCount'];        // 视频转发数量
            $info['play_count'] = $video['playCount'] ?? 0;        // 视频播放数量
            $info['is_like'] = $video['videoIsLike'] ?? 0;      // 是否已经点赞视频
            $info['is_follow'] = ($this->getUserId() == $video['videoUserId']) ? 1 : ($video['isFollow'] ?? 0); // 是否已经关注
            $info['generate_time'] = $video['generateTime'];    // 视频发布时间
            $info['audit_status'] = $video['auditStatus'] ?? 0;      // 视频审核状态
            $info['anchor_id'] = $video['anchorId'] ?? 0;      // 不为则是相亲视频
            // 店铺设置转换
            $setting = json_decode($video['setting'], true);
            $info['show_send_sms'] = $setting['show_send_sms'] ?? 0;
            $info['show_phone'] = $setting['show_phone'] ?? 0;
            $info['show_enter_shop'] = $setting['show_enter_shop'] ?? 0;
            $info['show_address'] = $setting['show_address'] ?? 0;
            $info['show_wechat'] = $setting['show_wechat'] ?? 0;
            $info['show_qq'] = $setting['show_qq'] ?? 0;
            $info['show_payment'] = $setting['show_payment'] ?? 0;
            $relationShopInfo = $this->getRelationShopInfo($video['relationShopId']);
            $info['related_qq'] = $relationShopInfo['related_qq'] ?? '';
            $info['related_wechat'] = $relationShopInfo['related_wechat'] ?? '';
            $info['related_shop_id'] = $video['relationShopId'] ?? 0;
            $info['related_shop_name'] = $relationShopInfo['related_shop_name'] ?? '';
            $info['related_shop_image'] = getImgWithDomain($relationShopInfo['related_shop_image'] ?? '');
            $info['related_shop_thumb_image'] = getImgWithDomain($relationShopInfo['related_shop_thumb_image'] ?? '');
            $info['related_shop_address'] = $relationShopInfo['related_shop_address'] ?? '';
            $info['related_shop_phone'] = $relationShopInfo['related_shop_phone'] ?? '';
            $info['related_longitude'] = $relationShopInfo['related_longitude'] ?? 0;
            $info['related_latitude'] = $relationShopInfo['related_latitude'] ?? 0;
            $info['related_pay_setting_type'] = $relationShopInfo['pay_setting_type'] ?? 0;

            if($relationShopInfo['account_status'] != 1 || $relationShopInfo['online_status'] != 1){
                $info['related_shop_id'] = 0;
            }
            if(isset($relationShopInfo['related_setting']) && !empty($relationShopInfo['related_setting']) && !empty($info['related_shop_id'])){
                // 店铺设置转换
                $setting = $settingLogic->settingTransform($relationShopInfo['related_setting']);
                $info['show_send_sms'] = $setting['show_send_sms'];
                $info['show_phone'] = $setting['show_phone'];
                $info['show_enter_shop'] = $setting['show_enter_shop'];
                $info['show_address'] = $setting['show_address'];
                $info['show_wechat'] = $setting['show_wechat'];
                $info['show_qq'] = $setting['show_qq'];
                $info['show_payment'] = $setting['show_payment'];
            }



            // TODO 是否置顶,V3.3.0已废弃
            if (isset($info['is_top'])) {
                $info['is_top'] = $video['isTop'];
            }

            // 店铺ID
            $info['shop_id'] = $video['shopId'];
            if (isset($video['shopName'])) {
                $info['shop_name'] = $video['shopName'];        // 店铺名称
                $info['shop_image'] = getImgWithDomain($video['shopImage']); // 店铺LOGO
                $info['shop_thumb_image'] = getImgWithDomain($video['shopThumbImage']);
                $info['shop_address'] = $video['shopAddress'];  // 店铺地址
            }

            // 分享缩略图
            $info['share_image'] = $video['coverUrl'];
            // 分享链接
            if ($video['type'] == 1) {
                $info['share_url'] = $appHost . '/h5/v3_7_0/meetingVideo.html?video_id=' . $video['videoId'];
            } else {
                $info['share_url'] = $appHost . '/h5/v3_5_0/note.html?video_id=' . $video['videoId'];
            }

            // 点赞数最多的一条评论
            $commentInfo = (object)[];
            // 有评论人用户ID，说明有评论
            $comment = $infoList[$video['videoId']] ?? [];
            if (!empty($comment)) {
                $commentInfo = [
                    'comment_id' => $comment['commentId'],          // 评论ID
                    'content' => $comment['commentContent'],        // 评论内容
                    'generate_time' => dateTransformer($comment['commentGenerateTime']),    // 评论时间
                    'like_count' => $comment['commentLikeCount'],   // 评论点赞数量
                    'is_like' => $comment['commentIsLike'] ?? 0,    // 是否已经点赞评论
                    'shop_id' => (int)$comment['commentShopId'],    // 评论人店铺ID
                    'user_id' => $comment['commentUserId'],         // 评论人用户ID
                    'nickname' => $comment['commentNickname'],      // 评论人昵称
                    'avatar' => getImgWithDomain($comment['commentAvatar']),    // 评论人头像
                ];
            }
            $info['comment_info'] = $commentInfo;

            // 随记图片列表
            $albumList = [];
            if ($video['type'] == 2) {
                $albumList = $albumModel->where('video_id', $video['videoId'])->column('image');
            }
            $info['album_list'] = $albumList;

            //获取用户环信信息
            $info['social_info'] = EasemobLogic::getSocialUserInfo($video['videoUserId']);

            array_push($result, $info);
        }

        $result = StringHelper::nullValueToEmptyValue($result);

        return $result;
    }

    /**
     * 关注/取消关注
     *
     * @param User $user 用户信息
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \Exception
     */
    public function followAction($user, $paramsArray)
    {
        $response = [
            'first_follow_flag' => 0,
            'is_follow' => 1
        ];
        // 被关注人的ID
        $followedId = $paramsArray['followed_id'];
        // 被关注人类型
        $followTo = $paramsArray['follow_to'];
        // 关注用户
        if ($followTo == 1) {
            // 不能关注自己
            if ($user->id == $followedId) {
                return $this->logicResponse([], $response);
            }

            $follower = UserModel::find($followedId);
        } else { // 关注店铺
            $follower = ShopModel::find($followedId);
        }

        // 要关注的人不存在
        if (empty($follower)) {
            return $this->logicResponse(config('response.msg11'));
        }

        $nowTime = date('Y-m-d H:i:s');
        // 关注用户
        if ($followTo == 1) {
            $follow = FollowRelationModel::where(['from_user_id' => $user->id, 'to_user_id' => $follower->id])->find();
            $followerId = ['to_user_id' => $follower->id];
        } else { // 关注店铺
            $follow = FollowRelationModel::where(['from_user_id' => $user->id, 'to_shop_id' => $follower->id])->find();
            $followerId = ['to_shop_id' => $follower->id];
        }

        // 有关注过
        if (!empty($follow)) {
            // 已经关注，则取消关注
            if ($follow->rel_type == 1 && $paramsArray['follow_type'] == 2) {
                $follow->rel_type = 2;
                $follow->cancel_time = $nowTime;
                $follow->save();
            }
            // 已取消关注，则重新关注
            if ($follow->rel_type == 2 && $paramsArray['follow_type'] == 1) {
                $follow->rel_type = 1;
                $follow->cancel_time = null;
                $follow->generate_time = $nowTime;
                $follow->save();
            }

            $isFollow = $follow->rel_type;
        }
        // 未关注过
        else {
            // 关注的操作
            if ($paramsArray['follow_type'] == 1) {
                // 首次关注才触发机器人规则
                $response['first_follow_flag'] = 1;
                $follow = FollowRelationModel::create(array_merge([
                    'from_user_id' => $user->id,
                    'rel_type' => 1,
                    'generate_time' => $nowTime
                ], $followerId));

                // 极光实时推送
                $pushMsgLogic = new PushMsgLogic();
                // 关注用户
                if ($followTo == 1) {
                    $pushMsgLogic->pushToUserMsg([$follower->registration_id], $user->nickname, 'follow_user', $follower->id);
                } else { // 关注店铺
                    $pushMsgLogic->pushToShopMsg($follower->id, $user->nickname, 'follow_shop');
                }
            }

            $isFollow = $follow->rel_type ?? 0;
        }

        // 关注的操作，写入消息
        if ($paramsArray['follow_type'] == 1) {
            $followMsg = UserMessageModel::where(array_merge(['from_user_id' => $user->id, 'msg_type' => 6], $followerId))->find();
            // 消息存在则只更新
            if ($followMsg) {
                $followMsg->read_status = 0;
                $followMsg->delete_status = 0;
                $followMsg->generate_time = $nowTime;
                $followMsg->save();
            } else { // 创建消息
                UserMessageModel::create(array_merge([
                    'msg_type' => 6, // 新增粉丝消息类型为6
                    'from_user_id' => $user->id,
                    'read_status' => 0,
                    'delete_status' => 0,
                    'generate_time' => $nowTime
                ], $followerId));
            }
        }

        $response['is_follow'] = $isFollow == 1 ? 1 : 0;


        //返回社交信息
        if($paramsArray['follow_to'] == 1 && $paramsArray['follow_type'] == 1){
            $socialInfo = EasemobLogic::getSocialUserInfo($paramsArray['followed_id']);
            $userInfo = [
                'user_id' => (int)$paramsArray['followed_id'] ?? 0,
                'nickname' => (string)$follower->nickname ?? '',
                'avatar' => getImgWithDomain($follower->avatar) ?? '',
                'thumb_avatar' => getImgWithDomain($follower->thumb_avatar) ?? '',
            ];
        }
        $response['user_info'] = [
            'user_id' => $userInfo['user_id'] ?? 0,
            'nickname' => $userInfo['nickname'] ?? '',
            'avatar' => $userInfo['avatar'] ?? '',
            'thumb_avatar' => $userInfo['thumb_avatar'] ?? '',
        ];

        $response['social_info'] = [
            'social_user_id' => $socialInfo['social_user_id'] ?? 0,
            'social_hx_uuid' => $socialInfo['social_hx_uuid'] ?? '',
            'social_hx_username' => $socialInfo['social_hx_username'] ?? '',
            'social_hx_nickname' => $socialInfo['social_hx_nickname'] ?? '',
            'social_hx_password' => $socialInfo['social_hx_password'] ?? '',
        ];


        return $this->logicResponse([], $response);
    }

    /**
     * 获取视频关联店铺信息
     * @param int $shopId
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRelationShopInfo(int $shopId)
    {

        return ( model(ShopModel::class)->field([
            'qq AS related_qq',
            'wechat AS related_wechat',
            'shop_name AS related_shop_name',
            'shop_image AS related_shop_image',
            'shop_thumb_image AS related_shop_thumb_image',
            'shop_address AS related_shop_address',
            'phone AS related_shop_phone',
            'longitude AS related_longitude',
            'latitude AS related_latitude',
            'setting AS related_setting',
            'pay_setting_type',
            'account_status',
            'online_status',
        ])->find($shopId));
    }
}

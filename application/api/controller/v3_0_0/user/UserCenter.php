<?php

namespace app\api\controller\v3_0_0\user;

use app\api\logic\v3_0_0\user\UserCenterLogic;
use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_0_0\VideoModel;
use app\api\Presenter;
use app\api\validate\v3_0_0\UserCenterValidate;
use app\common\utils\string\StringHelper;

class UserCenter extends Presenter
{

    /**
     * 他人中心.
     *
     * @return \think\response\Json
     */
    public function index()
    {
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(UserCenterValidate::class);
            $checkResult = $validate->scene('info')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 判断用户是否存在
            $userId = $paramsArray['user_id'];
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 获取信息
            $where = [
                'userId' => $userId,
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude'],
            ];
            $userCenterLogic = new UserCenterLogic();
            $info = $userCenterLogic->getUserCenterInfo($where);
            $info = StringHelper::nullValueToEmptyValue($info);

            // 是否已经拉黑
            $userModel = model(UserModel::class);
            $isInBlackList = $userModel->isInBlackList([
                'type' => 1,
                'loginUserId' => $this->getUserId(),
                'userId' => $userId,
            ]);
            $info['in_black_list'] = $isInBlackList ? 1 : 0;

            return apiSuccess($info);
        } catch (\Exception $e) {
            $logContent = '他人中心接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 用户关注的店铺列表.
     *
     * @return \think\response\Json
     */
    public function followShopList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取用户并判断是否存在
            $userId = input('user_id/d', 0);
            if ($userId <= 0) {
                return apiError(config('response.msg9'));
            }
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 获取列表
            /** @var \app\api\model\v3_0_0\FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $resultList = $followRelationModel->getUserFollowShopList($where);
            $countInfo = $followRelationModel->countUserFollow($where);
            // 返回数据
            $followShopList = [];
            foreach ($resultList as $shop) {
                $info = [];
                $info['shop_id'] = $shop['shopId'];
                $info['shop_name'] = $shop['shopName'];
                $info['shop_thumb_image'] = getImgWithDomain($shop['shopThumbImage']);
                $info['shop_address_poi'] = $shop['shopAddressPoi'];
                $info['video_count'] = $shop['videoCount'];
                array_push($followShopList, $info);
            }
            $responseData = [
                'user_total' => $countInfo['user_total'],
                'shop_total' => $countInfo['shop_total'],
                'follow_shop_list' => $followShopList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取用户关注的商家列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 用户关注的用户列表.
     *
     * @return \think\response\Json
     */
    public function followUserList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取用户并判断是否存在
            $userId = input('user_id/d', 0);
            if ($userId <= 0) {
                return apiError(config('response.msg9'));
            }
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 获取列表
            /** @var \app\api\model\v3_0_0\FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage,
                'selfUserId' => $this->getUserId()
            ];
            $resultList = $followRelationModel->getUserFollowUserList($where);
            $countInfo = $followRelationModel->countUserFollow($where);

            if ($resultList) {
                $userIdArr = array_column($resultList->toArray(), 'userId');
                // 获取用户的作品数量
                $userModel = model(UserModel::class);
                $videoCountList = $userModel->getUserVideoCount($userIdArr);
                // 获取用户的粉丝数量
                $fansCountList = $userModel->getUserFansCount($userIdArr);
                // 获取用户的获赞数量
                $likeCountList = $userModel->getUserLikeCount($userIdArr);

                $logic = new UserCenterLogic();
                $videoCountList = $logic->returnUserVideoCount($videoCountList);
                $fansCountList = $logic->returnUserFansCount($fansCountList);
                $likeCountList = $logic->returnUserLikeCount($likeCountList);
            }

            // 返回数据
            $followUserList = [];
            foreach ($resultList as $item) {
                $info = [];
                $info['user_id'] = $item['userId'];
                $info['nickname'] = $item['nickname'];
                $info['avatar'] = getImgWithDomain($item['avatar']);
                $info['phone'] = $item['phone'];
                $info['is_follow'] = empty($item['isFollow']) ? 0 : 1;

                $videoCountInfo = !empty($videoCountList[$item['userId']]) ? $videoCountList[$item['userId']] : null;
                $info['video_count'] = !empty($videoCountInfo['videoCount']) ? $videoCountInfo['videoCount'] : 0;

                $fansCountInfo = !empty($fansCountList[$item['userId']]) ? $fansCountList[$item['userId']] : null;
                $info['fans_count'] = !empty($fansCountInfo['fansCount']) ? $fansCountInfo['fansCount'] : 0;

                $likeCountInfo = !empty($likeCountList[$item['userId']]) ? $likeCountList[$item['userId']] : null;
                $info['like_count'] = !empty($likeCountInfo['likeCount']) ? $likeCountInfo['likeCount'] : 0;
                array_push($followUserList, $info);
            }
            $responseData = [
                'user_total' => $countInfo['user_total'],
                'shop_total' => $countInfo['shop_total'],
                'follow_user_list' => $followUserList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取用户关注的用户列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 用户的粉丝列表.
     *
     * @return \think\response\Json
     */
    public function fansList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取用户并判断是否存在
            $userId = input('user_id/d', 0);
            if ($userId <= 0) {
                return apiError(config('response.msg9'));
            }
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 获取列表
            /** @var \app\api\model\v3_0_0\FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $myUserId = $this->getUserId();
            if(empty($myUserId)){
                $myUserId = 0;
            }
            $where = [
                'myUserId' => $myUserId,
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $resultList = $followRelationModel->getUserFansList($where);

            if ($resultList) {
                $userIdArr = array_column($resultList->toArray(), 'userId');
                // 获取用户的作品数量
                $userModel = model(UserModel::class);
                $videoCountList = $userModel->getUserVideoCount($userIdArr);
                // 获取用户的粉丝数量
                $fansCountList = $userModel->getUserFansCount($userIdArr);
                // 获取用户的获赞数量
                $likeCountList = $userModel->getUserLikeCount($userIdArr);

                $logic = new UserCenterLogic();
                $videoCountList = $logic->returnUserVideoCount($videoCountList);
                $fansCountList = $logic->returnUserFansCount($fansCountList);
                $likeCountList = $logic->returnUserLikeCount($likeCountList);
            }

            // 返回数据
            $fansList = [];
            foreach ($resultList as $item) {
                $info = [];
                $info['user_id'] = $item['userId'];
                $info['nickname'] = $item['nickname'];
                $info['avatar'] = getImgWithDomain($item['avatar']);
                $info['phone'] = $item['phone'];
                $info['is_follow'] = $item['isFollow'];

                $videoCountInfo = !empty($videoCountList[$item['userId']]) ? $videoCountList[$item['userId']] : null;
                $info['video_count'] = !empty($videoCountInfo['videoCount']) ? $videoCountInfo['videoCount'] : 0;

                $fansCountInfo = !empty($fansCountList[$item['userId']]) ? $fansCountList[$item['userId']] : null;
                $info['fans_count'] = !empty($fansCountInfo['fansCount']) ? $fansCountInfo['fansCount'] : 0;

                $likeCountInfo = !empty($likeCountList[$item['userId']]) ? $likeCountList[$item['userId']] : null;
                $info['like_count'] = !empty($likeCountInfo['likeCount']) ? $likeCountInfo['likeCount'] : 0;
                array_push($fansList, $info);
            }
            $responseData = [
                'fans_list' => $fansList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取用户的粉丝列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 用户的视频列表.
     *
     * @return \think\response\Json
     */
    public function videoList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取用户并判断是否存在
            $userId = input('user_id/d', 0);
            if ($userId <= 0) {
                return apiError(config('response.msg9'));
            }
            $userModel = model(UserModel::class);
            $user = $userModel->where('account_status', 1)->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 获取视频列表
            /** @var \app\api\model\v3_0_0\VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $where = [
                'userId' => $userId,
                'loginUserId' => $this->getUserId(),
                'page' => $pageNo,
                'limit' => $perPage
            ];
            if ($selfUserId = $this->getUserId()) {
                $where['selfUserId'] = $selfUserId;
            }
            $result = $videoModel->getUserVideoList($where);
            $videoList = $result['videoList'];
            // 获取视频的评论信息
            $videoCommentInfoList = [];
            if($videoList) {
                $videoIdArr = array_column($videoList->toArray(), 'videoId');
                $videoCommentInfoList = $videoModel->getVideoCommentInfo([
                    'videoIdArr' => $videoIdArr,
                    'loginUserId' => $this->getUserId()
                ]);
            }
            // 返回数据
            $videoArray = [];
            foreach ($videoList as $video) {
                $info = [];
                $info['video_id'] = $video['videoId'];
                $info['title'] = $video['videoTitle'];
                $info['video_title'] = $video['videoTitle'];
                $info['cover_url'] = $video['coverUrl'];
                $info['video_url'] = $video['videoUrl'];
                $info['video_width'] = $video['videoWidth']; // 视频宽度
                $info['video_height'] = $video['videoHeight']; // 视频高度
                $info['like_count'] = $video['likeCount']; // 点赞数量
                $info['comment_count'] = $video['commentCount']; // 评论数量
                $info['share_count'] = $video['shareCount']; // 转发数量
                $info['shop_id'] = $video['shopId'] ?: 0; // 店铺ID
                $info['shop_image'] = getImgWithDomain($video['shopImage']); // 店铺图片
                $info['shop_thumb_image'] = getImgWithDomain($video['shopThumbImage']); // 店铺缩略图
                $info['is_follow'] = ($this->getUserId() == $video['videoUserId']) ? 1 : $video['isFollow']; // 是否关注
                $info['is_like'] = $video['isLike']; // 是否点赞
                $info['is_top'] = $video['isTop']; // 是否置顶
                $info['share_url'] = config('app_host') . '/h5/v3_0_0/videoDetail.html?video_id=' . $video['videoId'];
                $info['share_image'] = $video['coverUrl'];
                $info['avatar'] = getImgWithDomain($video['avatar']);
                $info['nickname'] = $video['nickname'];
                $info['video_user_id'] = $video['videoUserId'];
                $info['video_type'] = $video['videoType'];
                $info['generate_time'] = $video['generateTime'];
                $info['location'] = $video['location'];
                // 评论信息
                if (isset($videoCommentInfoList[$video['videoId']])) {
                    $info = $videoCommentInfoList[$video['videoId']];
                    $commentInfo = [
                        'comment_id' => $info['commentId'], // 评论ID
                        'content' => $info['commentContent'], // 评论内容
                        'generate_time' => date('m月d日 H:i', strtotime($info['commentGenerateTime'])), // 评论时间
                        'like_count' => $info['commentLikeCount'], // 评论点赞数量
                        'is_like' => $info['commentIsLike'] ?? 0, // 是否已经点赞评论
                        'shop_id' => (int) $info['commentShopId'], // 评论人店铺ID
                        'user_id' => $info['commentUserId'], // 评论人用户ID
                        'nickname' => $info['commentNickname'], // 评论人昵称
                        'avatar' => getImgWithDomain($info['commentAvatar']), // 评论人头像
                    ];
                } else {
                    $commentInfo = (object)[];
                }
                $info['comment_info'] = $commentInfo;
                array_push($videoArray, $info);
            }
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $videoArray
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取用户的视频列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

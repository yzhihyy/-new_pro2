<?php

namespace app\api\controller\v3_0_0\user;

use app\api\logic\v3_0_0\user\{
    OrderLogic, UserCenterLogic, UserLogic
};
use app\api\model\v3_0_0\{
    OrderModel, ShopModel, UserBlackListModel, UserInvitationModel, UserModel, UserReportTypeModel, VideoActionModel, FollowRelationModel, VideoCommentModel, VideoModel
};
use app\api\Presenter;
use app\api\validate\v3_0_0\UserValidate;
use app\common\utils\date\DateHelper;
use app\common\utils\xxtea\xxtea;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use app\common\utils\string\StringHelper;
use Hashids\Hashids;
use think\Db;
use app\api\logic\v3_7_0\user\UserCenterLogic AS UserCenterLogicFor;
use app\api\logic\v3_7_0\user\UserLogic AS NewUserLogic;

class User extends Presenter
{
    /**
     * 用户中心
     *
     * @return \think\response\Json
     */
    public function userCenter()
    {
        try {
            // 获取用户信息
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $userId = $this->getUserId();
            $userInfo = $userModel->find($userId);
            // 统计新增粉丝数量
            $followModel = model(FollowRelationModel::class);
            $newFansCount = $followModel->alias('fr')->where([
                'to_user_id' => $userId,
                'rel_type' => 1,
                'is_new_follow' => 1
            ])->count();
            // 统计新增评论数量
            $videoCommentModel = model(VideoCommentModel::class);
            $newCommentCount = $videoCommentModel->countMyCommentAndReplyOfUnread(['userId' => $userId]);

            $data = [
                'user_info' => [
                    'user_id' => $userId,
                    'nickname' => $userInfo['nickname'],
                    'avatar' => getImgWithDomain($userInfo['avatar']),
                    'thumb_avatar' => getImgWithDomain($userInfo['thumb_avatar']),
                ],
                'new_fans_count' => $newFansCount,
                'new_comment_count' => $newCommentCount
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '用户中心接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 获取我喜欢的视频列表.
     *
     * @return \think\response\Json
     */
    public function getMyJoyVideoList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取列表
            /** @var \app\api\model\v3_0_0\VideoActionModel $videoActionModel */
            $videoActionModel = model(VideoActionModel::class);
            $where = [
                'userId' => $this->getUserId(),
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $result = $videoActionModel->getMyJoyVideoList($where);
            $joyVideoList = $result['videoList'];
            // 返回数据
            $joyVideoArray = [];
            foreach ($joyVideoList as $video) {
                $info = [];
                $info['video_id'] = $video['videoId'];
                $info['title'] = $video['videoTitle'];
                $info['cover_url'] = $video['coverUrl'];
                $info['video_url'] = $video['videoUrl'];
                $info['video_width'] = $video['videoWidth']; // 视频宽度
                $info['video_height'] = $video['videoHeight']; // 视频高度
                $info['like_count'] = $video['likeCount']; // 点赞数量
                $info['comment_count'] = $video['commentCount']; // 评论数量
                $info['share_count'] = $video['shareCount']; // 转发数量
                $info['shop_id'] = $video['shopId']; // 店铺ID
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
                array_push($joyVideoArray, $info);
            }
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $joyVideoArray
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我喜欢的视频列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 我关注的商家列表.
     *
     * @return \think\response\Json
     */
    public function getMyFollowShopList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取列表
            /** @var \app\api\model\v3_0_0\FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $where = [
                'userId' => $this->getUserId(),
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
            $logContent = '获取我关注的商家列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 我关注的用户列表.
     *
     * @return \think\response\Json
     */
    public function getMyFollowUserList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取列表
            $where = [
                'userId' => $this->getUserId(),
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $responseData = $this->getFollowUserList($where);
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我关注的用户列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 他关注的用户列表.
     *
     * @return \think\response\Json
     */
    public function getUserFollowUserList()
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
                return apiError('用户不存在');
            }
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError('用户不存在');
            }
            // 获取列表
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $responseData = $this->getFollowUserList($where);
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取他关注的用户列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    private function getFollowUserList($where)
    {
        // 获取列表
        $followRelationModel = model(FollowRelationModel::class);
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

            $videoCountInfo = !empty($videoCountList[$item['userId']]) ? $videoCountList[$item['userId']] : null;
            $info['video_count'] = !empty($videoCountInfo['videoCount']) ? $videoCountInfo['videoCount'] : 0;

            $fansCountInfo = !empty($fansCountList[$item['userId']]) ? $fansCountList[$item['userId']] : null;
            $info['fans_count'] = !empty($fansCountInfo['fansCount']) ? $fansCountInfo['fansCount'] : 0;

            $likeCountInfo = !empty($likeCountList[$item['userId']]) ? $likeCountList[$item['userId']] : null;
            $info['like_count'] = !empty($likeCountInfo['likeCount']) ? $likeCountInfo['likeCount'] : 0;

            $info['social_info'] = [
                'social_user_id' => $item['social_user_id'],
                'social_hx_uuid' => $item['social_hx_uuid'],
                'social_hx_username' => $item['social_hx_username'],
                'social_hx_nickname' => $item['social_hx_nickname'],
                'social_hx_password' => $item['social_hx_password'],
            ];
            array_push($followUserList, $info);
        }
        $responseData = [
            'user_total' => $countInfo['user_total'],
            'shop_total' => $countInfo['shop_total'],
            'follow_user_list' => $followUserList
        ];
        $responseData = StringHelper::nullValueToEmptyValue($responseData);
        return $responseData;
    }


    /**
     * 我的评论回复列表.
     *
     * @return \think\response\Json
     */
    public function getMyCommentAndReplyList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取列表
            /** @var \app\api\model\v3_0_0\VideoCommentModel $videoCommentModel */
            $videoCommentModel = model(VideoCommentModel::class);
            $where = [
                'userId' => $this->getUserId(),
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $resultList = $videoCommentModel->getMyCommentAndReplyList($where);
            // 设置为已读
            $videoCommentModel->setMyCommentAndReplyHasRead(['userId' => $this->getUserId()]);

            $commentAndReplyList = [];
            foreach ($resultList as $value) {
                $info = [];
                $info['user_id'] = $value['userId'];
                $info['nickname'] = $value['nickname'];
                $info['avatar'] = getImgWithDomain($value['avatar']);
                $info['thumb_avatar'] = getImgWithDomain($value['thumbAvatar']);
                $info['cover_url'] = $value['coverUrl'];
                $info['type'] = $value['type'];
                $info['content'] = $value['content'];
                $info['generate_time'] = dateTransformer($value['generateTime']);
                $info['video_id'] = $value['videoId'];
                $info['comment_id'] = $value['commentId'];
                array_push($commentAndReplyList, $info);
            }
            $responseData = [
                'comment_and_reply_list' => $commentAndReplyList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '我的评论回复列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 我的作品列表.
     *
     * @return \think\response\Json
     */
    public function getMyVideoList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取列表
            /** @var \app\api\model\v3_0_0\VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $where = [
                'userId' => $this->getUserId(),
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $result = $videoModel->getMyVideoList($where);
            $videoList = $result['videoList'];
            // 返回数据
            $videoArray = [];
            foreach ($videoList as $video) {
                $info = [];
                $info['video_id'] = $video['videoId'];
                $info['title'] = $video['videoTitle'];
                $info['cover_url'] = $video['coverUrl'];
                $info['video_url'] = $video['videoUrl'];
                $info['video_width'] = $video['videoWidth']; // 视频宽度
                $info['video_height'] = $video['videoHeight']; // 视频高度
                $info['like_count'] = $video['likeCount']; // 点赞数量
                $info['comment_count'] = $video['commentCount']; // 评论数量
                $info['share_count'] = $video['shareCount']; // 转发数量
                $info['shop_id'] = $video['shopId']; // 店铺ID
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
                array_push($videoArray, $info);
            }
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $videoArray
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我喜欢的视频列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 我的粉丝列表.
     *
     * @return \think\response\Json
     */
    public function getMyFansList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 获取列表
            /** @var \app\api\model\v3_0_0\FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $where = [
                'myUserId' => $this->getUserId(),
                'userId' => $this->getUserId(),
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
            // 设置粉丝状态不是新关注的
            $followRelationModel->save(['is_new_follow' => 0], ['to_user_id' => $this->getUserId(), 'is_new_follow' => 1,]);

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
            $logContent = '获取我的粉丝列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 我的邀请.
     *
     * @return \think\response\Json
     */
    public function myInvitation()
    {
        $user = $this->request->user;
        $page = $this->request->get('page/d', 0);
        try {
            if(empty($user->invite_code)){
                $randStr = UserCenterLogicFor::randStr(6);
                $update = Db::name('user')->update(['id' => $user->id, 'invite_code' => $randStr]);
                if($update <= 0){
                    return apiError('邀请码生成失败');
                }
                $user->invite_code = $randStr;
            }
            $shareConfig = config('share.invitation_info');
            //邀请码
            $inviteCode = $user->invite_code;
            // 邀请链接
            $inviteUrl = $shareConfig['url'] . $inviteCode;
            $shareInfo = [
                'share_title' => $shareConfig['title'],
                'share_intr' => $shareConfig['describe'],
                'share_img' => config('app.resources_domain') . $shareConfig['image'],
                'share_url' => $inviteUrl
            ];

            // 文件上传根目录
            $fileRootPath = realpath(config('app.image_server_root_path'));
            // 邀请二维码路径
            $inviteQrcodePath = "/inviteQrcode/{$inviteCode}/";
            if (!file_exists($fileRootPath . $inviteQrcodePath)) {
                mkdirs($fileRootPath . $inviteQrcodePath);
            }
            // 邀请二维码
            $inviteQrcode = $inviteQrcodePath . $inviteCode . '.png';
            // 邀请二维码不存在，则生成
            if (!file_exists($fileRootPath . $inviteQrcode)) {
                // 生成二维码
                $qrCode = new Qrcode();
                $qrCode->setText($inviteUrl)
                    ->setSize(450)
                    ->setWriterByName('png')
                    ->setMargin(20)
                    ->setEncoding('UTF-8')
                    ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
                    ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
                    ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
                    ->setValidateResult(false);
                // 保存二维码
                $qrCode->writeFile($fileRootPath . $inviteQrcode);
            }

            // 用户邀请记录模型
            $userModel = model(UserModel::class);
            // 总邀请人数
            $invitedCount = $userModel->where('bind_invite_code', $user->invite_code)->count('id');
            // 成功邀请人数
            $validInvitedCount = $invitedCount;

            $inviteeList = NewUserLogic::userInviteList([
                'invite_code' => $user->invite_code,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);
            foreach ($inviteeList as &$item) {
                $item = [
                    'phone' => StringHelper::hidePartOfString($item['phone']),
                    'generate_time' => date('Y-m-d', strtotime($item['generateTime'])),
                    'install_time' => $item['installTime'] ? date('Y-m-d', strtotime($item['installTime'])) : ''
                ];
            }
            $inviteeList = StringHelper::nullValueToEmptyValue($inviteeList);

            return apiSuccess([
                'invite_qrcode' => config('app.resources_domain') . $inviteQrcode,
                'share_info' => $shareInfo,
                'invited_count' => $invitedCount,
                'valid_invited_count' => $validInvitedCount,
                'invited_copper' => UserCenterLogicFor::userInviteCopper(),
                'my_invite_code' => $user->invite_code,
                'invitee_list' => $inviteeList
            ]);
        } catch (\Exception $e) {
            $logContent = '我的邀请接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 用户举报类型列表.
     *
     * @return \think\response\Json
     */
    public function userReportTypeList()
    {
        try {
            $model = model(UserReportTypeModel::class);
            $list = $model
                ->field([
                    'id as report_type_id',
                    'report_name',
                ])
                ->where('status', '=', 1)
                ->order('sort', 'DESC')
                ->select();
            $info = [
                'list' => $list,
            ];
            return apiSuccess($info);
        } catch (\Exception $e) {
            generateApiLog("获取用户举报类型列表失败：{$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 用户举报.
     *
     * @return \think\response\Json
     */
    public function userReport()
    {
        try {
            $params = $this->request->post();
            $validate = validate(UserValidate::class);
            $check = $validate->scene('userReport')->check($params);
            if (!$check) {
                return apiError($validate->getError());
            }
            if ($params['report_type_id'] == 7 && empty($params['content'])) {
                return apiError(config('response.msg76'));
            }
            $data = [
                'user_id' => $this->request->user->id,
                'report_type_id' => $params['report_type_id'],
                'report_content' => $params['content'] ?? '',
                'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
            ];
            if ($params['type'] == 1) {
                $data['to_user_id'] = $params['user_id'];
            } else {
                $data['to_shop_id'] = $params['shop_id'];
            }
            $add = Db::name('user_report')->insert($data);
            if ($add) {
                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("用户举报接口请求失败：{$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 拉黑和取消拉黑.
     *
     * @return \think\response\Json
     */
    public function handleBlackList()
    {
        try {
            $params = $this->request->post();
            $validate = validate(UserValidate::class);
            $check = $validate->scene('handleBlackList')->check($params);
            if (!$check) {
                return apiError($validate->getError());
            }

            $userId = $this->request->user->id;

            if ($params['type'] == 1) {
                if ($userId == $params['user_id']) {
                    return apiError('不能拉黑自己');
                }
                $where = [
                    'ubl.user_id' => $userId,
                    'ubl.to_user_id' => $params['user_id'],
                    'ubl.to_shop_id' => 0,
                ];
                $createData = [
                    'user_id' => $this->request->user->id,
                    'to_user_id' => $params['user_id'],
                    'to_shop_id' => 0,
                    'status' => 1,
                    'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s')
                ];
            } else {
                $shop = model(ShopModel::class)->find($params['shop_id']);
                if ($shop && $shop['user_id'] == $userId) {
                    return apiError('不能拉黑自己');
                }
                $where = [
                    'ubl.user_id' => $userId,
                    'ubl.to_user_id' => 0,
                    'ubl.to_shop_id' => $params['shop_id'],
                ];
                $createData = [
                    'user_id' => $this->request->user->id,
                    'to_user_id' => 0,
                    'to_shop_id' => $params['shop_id'],
                    'status' => 1,
                    'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s')
                ];
            }
            // 判断是否已经拉黑，若已经拉黑者取消拉黑
            $model = model(UserBlackListModel::class);
            $item = $model->alias('ubl')
                ->field(['ubl.id', 'ubl.status'])
                ->where($where)
                ->find();
            if (empty($item)) {
                $model::create($createData);
                $info['in_black_list'] = 1;
                return apiSuccess($info);
            } else {
                $status = ($item->status == 1) ? 2 : 1;
                $item->save([
                    'status' => $status
                ]);
                $info['in_black_list'] = ($item->status == 1) ? 1 : 0;
                return apiSuccess($info);
            }
        } catch (\Exception $e) {
            generateApiLog("拉黑接口请求失败：{$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 申请合作
     *
     * @return \think\Response\Json
     */
    public function applyCooperation()
    {
        try {
            // 用户ID
            $userId = $this->request->user->id;
            // 条件
            $condition = ['shop_type' => 1, 'user_id' => $userId];
            // 店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 店铺信息
            $shop = $shopModel->where($condition)->find();

            $userLogic = new UserLogic();
            // 申请店铺|重新申请店铺
            if ($this->request->isGet()) {
                return apiSuccess($userLogic->getShopInfo($shop));
            }

            // 提交店铺申请
            if ($this->request->isPost()) {
                // 请求参数
                $paramsArray = $this->request->post();
                // 参数校验
                $validateResult = $this->validate($paramsArray, UserValidate::class . '.ApplyCooperation');
                if ($validateResult !== true) {
                    return apiError($validateResult);
                }

                if (!empty($shop) && in_array($shop['online_status'], [0, 1, 2])) {
                    $msgKey = $shop['online_status'] == 0 ? 'response.msg67' : ($shop['online_status'] == 1 ? 'response.msg21' : 'response.msg22');
                    return apiError(config($msgKey));
                }

                // 保存店铺申请
                $userLogic->saveShop($userId, $paramsArray, $shop, $shopModel);

                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("申请合作接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 我的订单列表
     *
     * @return \think\Response\Json
     */
    public function myOrderList()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->get();
            // 参数校验
            $validateResult = $this->validate($paramsArray, UserValidate::class . '.MyOrderList');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $condition = [
                'userId' => $this->request->user->id,
                'page' => $paramsArray['page'] ?? 0,
                'limit' => config('parameters.page_size_level_2'),
                'status' => $paramsArray['status'] ?? 0,
            ];
            $orderList = $orderModel->getUserOrderList($condition);
            $orderLogic = new OrderLogic();
            $result = $orderLogic->ordersHandle($orderList);

            return apiSuccess(['order_list' => $result]);
        } catch (\Exception $e) {
            generateApiLog("我的订单列表接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}

<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\logic\v3_0_0\RobotLogic;
use app\api\model\v2_0_0\{UserHasShopModel, ShopModel};
use app\api\model\v3_0_0\{
    UserMessageModel, UserVideoHistoryModel, VideoCommentModel, VideoModel, VideoActionModel, UserModel
};

class VideoLogic extends BaseLogic
{
    /**
     * 视频点赞
     *
     * @param User $user 用户信息
     * @param int $videoId 视频ID
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function videoLike($user, $videoId)
    {
        $nowTime = date('Y-m-d H:i:s');
        $video = VideoModel::where(['id' => $videoId, 'status' => 1])->find();
        if (!$video) {
            return $this->logicResponse(config('response.msg69'));
        }

        // 开启事务
        VideoModel::startTrans();

        $like = VideoActionModel::where(['action_type' => 1, 'video_id' => $video->id, 'user_id' => $user->id])->find();
        // 有点赞过
        if (!empty($like)) {
            // 已经点赞，则取消点赞
            if ($like->status == 1) {
                $like->status = 2;
                // 减少点赞量
                if ($video->like_count > 0) {
                    $video->like_count--;
                    $video->save();
                }
            }
            // 已取消点赞，则重新点赞
            elseif ($like->status == 2) {
                $like->status = 1;
                $like->generate_time = $nowTime;
                // 增加点赞量
                $video->like_count++;
                $video->save();
            }
            $like->save();
        }
        // 未点赞过
        else {
            // 添加点赞
            $like = VideoActionModel::create([
                'action_type' => 1,
                'video_id' => $video->id,
                'user_id' => $user->id,
                'status' => 1,
                'generate_time' => $nowTime
            ]);
            // 增加点赞量
            $video->like_count++;
            $video->save();

            // 不是点赞自己的视频
            if ($user->id != $video->user_id) {
                $pushMsgLogic = new PushMsgLogic();
                $messageData = [
                    'msg_type' => 3, // 视频点赞消息类型为3
                    'video_id' => $video->id,
                    'from_user_id' => $user->id,
                    'read_status' => 0,
                    'delete_status' => 0,
                    'generate_time' => $nowTime
                ];
                // 给店铺视频点赞
                if ($video->shop_id) {
                    $messageData['to_shop_id'] = $video->shop_id;
                    // 给店铺的所有管理员发消息
                    //$pushMsgLogic->pushToShopMsg($video->shop_id, $user->nickname, 'video_like');
                }
                // 给用户视频点赞
                else {
                    $messageData['to_user_id'] = $video->user_id;
                    $toUser = UserModel::where('id', $video->user_id)->find();
                    if ($toUser) {
                        // 给用户发消息
                        $pushMsgLogic->pushToUserMsg([$toUser->registration_id], $user->nickname, 'video_like', $toUser->id);
                    }
                }

                // 创建消息
                UserMessageModel::create($messageData);
            }
        }

        // 提交事务
        VideoModel::commit();

        return $this->logicResponse([], [
            'like_count' => $video->like_count,
            'is_like' => $like->status == 1 ? 1 : 0
        ]);
    }

    /**
     * 评论/回复点赞
     *
     * @param User $user 用户信息
     * @param int $commentId 评论ID
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function commentLike($user, $commentId)
    {
        $nowTime = date('Y-m-d H:i:s');
        $comment = VideoCommentModel::where(['id' => $commentId, 'status' => 1])->find();
        if (empty($comment)) {
            return $this->logicResponse(config('response.msg70'));
        }

        // 开启事务
        VideoCommentModel::startTrans();

        $like = VideoActionModel::where(['action_type' => 1, 'comment_id' => $comment->id, 'user_id' => $user->id])->find();
        // 有点赞过
        if (!empty($like)) {
            // 已经点赞，则取消点赞
            if ($like->status == 1) {
                $like->status = 2;
                // 减少点赞量
                if ($comment->like_count > 0) {
                    $comment->like_count--;
                    $comment->save();
                }
            }
            // 已取消点赞，则重新点赞
            elseif ($like->status == 2) {
                $like->status = 1;
                $like->generate_time = $nowTime;
                // 增加点赞量
                $comment->like_count++;
                $comment->save();
            }
            $like->save();
        }
        // 没点赞过
        else {
            // 添加点赞
            $like = VideoActionModel::create([
                'action_type' => 1,
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'status' => 1,
                'generate_time' => $nowTime
            ]);
            // 增加点赞量
            $comment->like_count++;
            $comment->save();

            // 不是点赞自己的评论
            if ($user->id != $comment->from_user_id) {
                $pushMsgLogic = new PushMsgLogic();
                $messageData = [
                    'msg_type' => 4, // 评论点赞消息类型为4
                    'video_id' => $comment->video_id,
                    'comment_id' => $comment->id,
                    'from_user_id' => $user->id,
                    'read_status' => 0,
                    'delete_status' => 0,
                    'generate_time' => $nowTime
                ];
                // 给店铺评论点赞
                if ($comment->from_shop_id) {
                    $messageData['to_shop_id'] = $comment->from_shop_id;
                    // 给店铺的所有管理员发消息
                    //$pushMsgLogic->pushToShopMsg($comment->from_shop_id, $user->nickname, 'comment_like');
                }
                // 给用户评论点赞
                else {
                    $messageData['to_user_id'] = $comment->from_user_id;
                    $toUser = UserModel::where('id', $comment->from_user_id)->find();
                    if ($toUser) {
                        // 给用户发消息
                        $pushMsgLogic->pushToUserMsg([$toUser->registration_id], $user->nickname, 'comment_like', $toUser->id);
                    }
                }

                // 创建消息
                UserMessageModel::create($messageData);
            }
        }
        // 提交事务
        VideoCommentModel::commit();

        return $this->logicResponse([], [
            'like_count' => $comment->like_count,
            'is_like' => $like->status == 1 ? 1 : 0
        ]);
    }

    /**
     * 视频评论
     *
     * @param User $user 用户信息
     * @param array $paramsArray 参数数组
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function comment($user, $paramsArray)
    {
        $nowTime = date('Y-m-d H:i:s');
        $video = VideoModel::where(['id' => $paramsArray['video_id'], 'status' => 1])->find();
        // 视频不存在
        if (!$video) {
            return $this->logicResponse(config('response.msg69'));
        }

        // TODO 过滤评论内容

        // 评论数据
        $commentData = [
            'video_id' => $video->id,
            'content' => $paramsArray['content'],
            'from_user_id' => $user->id,
            'from_shop_id' => 0,
            'to_user_id' => $video->user_id,
            'to_shop_id' => 0,
            'top_comment_id' => 0,
            'parent_comment_id' => 0,
            'like_count' => 0,
            'status' => 1,
            'generate_time' => $nowTime
        ];
        // 上级评论ID
        $parentCommentId = $paramsArray['parent_comment_id'] ?? '';
        // 以店铺身份评论，记录店铺ID
        if (isset($paramsArray['shop_reply_flag']) && $paramsArray['shop_reply_flag']) {
            $fromShopId = UserHasShopModel::where(['user_id' => $user->id, 'selected_shop_flag' => 1])->value('shop_id');
            $commentData['from_shop_id'] = $fromShopId ?: 0;
        }
        // 如果有上级评论ID，则为回复
        if ($parentCommentId) {
            // 判断要回复的内容是否存在
            $parentComment = VideoCommentModel::get(['id' => $parentCommentId, 'status' => 1]);
            if (!$parentComment) {
                return $this->logicResponse(config('response.msg70'));
            }

            // 如果上级评论是以商家身份评论的，则记录被回复人的店铺ID
            if ($parentComment->from_shop_id) {
                $commentData['to_shop_id'] = $parentComment->from_shop_id;
            }

            $commentData['to_user_id'] = $parentComment->from_user_id;
            // 如果回复的内容没有顶级ID，则回复的那一条就是顶级评论
            $commentData['top_comment_id'] = $parentComment->top_comment_id ?: $parentCommentId;
            $commentData['parent_comment_id'] = $parentCommentId;

            $toReplierId = $parentComment->from_user_id;
            // 被回复人是以店铺身份评论
            if ($parentComment->from_shop_id) {
                $toReplierName = ShopModel::where('id', $parentComment->from_shop_id)->value('shop_name');
            } else {
                $toReplierName = UserModel::where('id', $parentComment->from_user_id)->value('nickname');
            }
        }

        // 开启事务
        VideoCommentModel::startTrans();

        // 增加视频评论数
        $video->comment_count++;
        $video->save();
        // 保存评论/回复
        $comment = VideoCommentModel::create($commentData);
        // 评论人是以店铺身份评论
        if ($comment->from_shop_id) {
            $fromReplier = ShopModel::field(true)->find($comment->from_shop_id);
        }

        // 不是给自己评论/回复
        if ($user->id != $comment->to_user_id) {
            $pushMsgLogic = new PushMsgLogic();
            $msgType = $parentCommentId ? 'comment_reply' : 'video_comment';
            $msgFromNickname = isset($fromReplier) ? $fromReplier->shop_name : $user->nickname;
            // 判断消息是发送给店铺还是用户
            $msgToShop = $parentCommentId ? $comment->to_shop_id : $video->shop_id;
            $messageData = [
                'msg_type' => $parentCommentId ? 5 : 2, // 回复消息类型是5，评论消息类型是2
                'video_id' => $video->id,
                'comment_id' => $comment->id,
                'content' => $paramsArray['content'],
                'read_status' => 0,
                'delete_status' => 0,
                'generate_time' => $nowTime
            ];
            // 评论人是以店铺身份评论
            if ($comment->from_shop_id) {
                $messageData['from_shop_id'] = $comment->from_shop_id;
            } else { // 评论人以用户身份评论
                $messageData['from_user_id'] = $user->id;
            }

            // 给店铺发送消息&&推送
            if ($msgToShop) {
                $messageData['to_shop_id'] = $msgToShop;
                // 给店铺的所有管理员发消息
            //    $pushMsgLogic->pushToShopMsg($msgToShop, $msgFromNickname, $msgType);
            } else { // 给用户推送
                $messageData['to_user_id'] = $comment->to_user_id;
                $toUser = UserModel::where('id', $comment->to_user_id)->find();
                if ($toUser) {
                    // 给用户推送
                    $pushMsgLogic->pushToUserMsg([$toUser->registration_id], $msgFromNickname, $msgType, $toUser->id);
                }
            }
            // 创建消息
            UserMessageModel::create($messageData);
        }

        // 提交事务
        VideoCommentModel::commit();

        return $this->logicResponse([], [
            'from_user_id' => isset($fromReplier) ? $fromReplier->user_id : $user->id,
            'from_user_avatar' => getImgWithDomain(isset($fromReplier) ? $fromReplier->shop_thumb_image : $user->thumbAvatar),
            'from_user_nickname' => isset($fromReplier) ? $fromReplier->shop_name : $user->nickname,
            'to_user_id' => $toReplierId ?? 0,
            'to_nickname' => $toReplierName ?? '',
            'comment_id' => $comment->id,
            'content' => $comment->content,
            'generate_time' => dateTransformer($comment->generate_time)
        ]);
    }

    /**
     * 视频评论列表
     *
     * @param int $videoId 视频ID
     * @param int $page 页码
     *
     * @return array
     */
    public function commentList($videoId, $page)
    {
        // 评论列表
        $comments = [];
        $userId = $this->getUserId();
        $commentModel = model(VideoCommentModel::class);
        // 总评论数量
        $commentCount = $commentModel->where(['video_id' => $videoId, 'status' => 1])->count(1);
        // 获取一级评论列表
        $topComments = $commentModel->getTopLevelCommentList([
            'page' => $page,
            'userId' => $userId,
            'videoId' => $videoId,
            'limit' => config('parameters.page_size_level_2')
        ]);
        if ($topComments) {
            $commentIds = array_column($topComments, 'commentId');
            // 评论的回复信息
            $reply = $commentModel->findOneHotCommentReply(compact('userId', 'commentIds'));
            // 一级评论下的回复数量数组
            $replyCountArr = [];
            if ($reply) {
                $replyCountArr = $commentModel->getCommentReplyCount(compact('commentIds'));
            }

            foreach ($topComments as $key => $item) {
                $comments[$key] = [
                    'comment_id' => $item['commentId'],
                    'like_count' => $item['likeCount'],
                    'content' => $item['content'],
                    'user_id' => $item['userId'],
                    'nickname' => $item['nickname'],
                    'avatar' => getImgWithDomain($item['avatar']),
                    'is_like' => $item['isLike'],
                    'generate_time' => dateTransformer($item['generateTime']),
                    'hot_reply' => (object)[]
                ];
                foreach ($reply as $k => $value) {
                    if ($item['commentId'] == $value['parentCommentId']) {
                        $temp = [
                            'reply_id' => $value['commentId'],
                            'like_count' => $value['likeCount'],
                            'content' => $value['content'],
                            'from_user_id' => $value['userId'],
                            'from_avatar' => getImgWithDomain($value['avatar']),
                            'from_nickname' => $value['nickname'],
                            'is_like' => $value['isLike'],
                            'generate_time' => dateTransformer($value['generateTime'])
                        ];
                        foreach ($replyCountArr as $i => $v) {
                            if ($value['topCommentId'] == $v['topCommentId']) {
                                // 剩余的回复数量
                                $surplusReplyCount = $v['replyCount'] > 0 ? ($v['replyCount'] - 1) : 0;
                                $temp = array_merge($temp, ['surplus_reply_count' => $surplusReplyCount]);
                                unset($replyCountArr[$i]);
                            }
                        }

                        $comments[$key]['hot_reply'] = $temp;
                        unset($reply[$k]);
                    }
                }
            }
        }

        return [
            'comment_count' => $commentCount,
            'comment_list' => $comments
        ];
    }

    /**
     * 视频评论的回复列表
     *
     * @param array $paramsArray 参数数组
     * @param int $page 页码
     *
     * @return array
     */
    public function commentReplyList($paramsArray, $page)
    {
        $response = [];
        $userId = $this->getUserId();
        $commentModel = model(VideoCommentModel::class);
        // 获取评论的回复列表
        $replies = $commentModel->getCommentReplyList([
            'userId' => $userId,
            'hotReplyId' => $paramsArray['hot_reply_id'],
            'commentId' => $paramsArray['comment_id'],
            'page' => $page,
            'limit' => config('parameters.page_size_level_2')
        ]);
        if ($replies) {
            $replies = array_column($replies, null, 'replyId');
            foreach ($replies as $item) {
                $temp = [
                    'reply_id' => $item['replyId'],
                    'like_count' => $item['likeCount'],
                    'content' => $item['content'],
                    'generate_time' => dateTransformer($item['generateTime']),
                    'from_user_id' => $item['fromUserId'],
                    'from_nickname' => $item['fromNickname'],
                    'from_avatar' => getImgWithDomain($item['fromAvatar']),
                    'is_like' => $item['isLike'],
                    'to_user_id' => 0,
                    'to_nickname' => '',
                ];
                if ($item['parentCommentId'] != $item['topCommentId']) {
                    $temp['to_user_id'] = $item['toUserId'];
                    $temp['to_nickname'] = $item['toNickname'];
                }

                $response[] = $temp;
            }
        }

        return [
            'reply_list' => $response
        ];
    }

    /**
     * 视频转发
     *
     * @param int $userId 用户ID
     * @param int $videoId 视频ID
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function videoShare($userId, $videoId)
    {
        $video = VideoModel::where(['id' => $videoId, 'status' => 1])->find();
        // 视频不存在
        if (!$video) {
            return $this->logicResponse(config('response.msg69'));
        }

        // 增加转发数
        VideoModel::where(['id' => $videoId])->setInc('share_count');
        // 用户登录的情况下转发，增加转发记录
        if ($userId) {
            $user = UserModel::find($userId);
            // 用户不存在，不返回错误
            if (!$user) {
                return $this->logicResponse();
            }

            $videoAction = VideoActionModel::where(['action_type' => 2, 'video_id' => $video->id, 'user_id' => $user->id])->find();
            // 没有转发记录才创建
            if (!$videoAction) {
                // 添加转发记录
                VideoActionModel::create([
                    'action_type' => 2,
                    'video_id' => $video->id,
                    'user_id' => $user->id,
                    'status' => 1,
                    'generate_time' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return $this->logicResponse();
    }

    /**
     * 保存视频播放历史
     *
     * @param array $videoList
     * @param int|null $userId
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \Exception
     */
    public function savePlayHistory(array $videoList, ?int $userId)
    {
        $videoIdArray = array_unique(array_filter(array_column($videoList, 'video_id')));
        if (!empty($videoIdArray)) {
            if (!is_null($userId)) {
                /** @var UserVideoHistoryModel $userVideoHistoryModel */
                $userVideoHistoryModel = model(UserVideoHistoryModel::class);
                $videoHistory = $userVideoHistoryModel->getUserVideoHistory(['userId' => $userId, 'videoIds' => $videoIdArray]);
                $videoHistory = array_column($videoHistory, null, 'videoId');

                $insertData = $updateData = [];
                $nowTime = date('Y-m-d H:i:s');
                foreach ($videoList as $item) {
                    $video = $videoHistory[$item['video_id']] ??  [];
                    $playFinished = $item['is_finished'] == 1 ? 1 : 0;
                    if (empty($video)) {
                        $insertData[] = [
                            'user_id' => $userId,
                            'video_id' => $item['video_id'],
                            'play_finished' => $playFinished,
                            'generate_time' => $nowTime,
                        ];
                    } else {
                        $updateData[] = [
                            'id' => $video['id'],
                            'play_count' => $video['playCount'] + 1,
                            'play_finished' => $playFinished
                        ];
                    }
                }

                if (!empty($insertData)) {
                    $userVideoHistoryModel->saveAll($insertData);
                }

                if (!empty($updateData)) {
                    $userVideoHistoryModel->saveAll($updateData);
                }

                // 处理用户视频浏览
                $robotLogic = new RobotLogic();
                $robotLogic->handleVideoOrTopicView($userId, 1, count($videoIdArray));
            }

            // 更新视频总的播放次数
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $videoModel->whereIn('id', $videoIdArray)->setInc('play_count');
        }
    }
}

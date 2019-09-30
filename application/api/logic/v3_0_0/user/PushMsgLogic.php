<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\UserHasShopModel;
use app\api\model\v3_0_0\UserMessageModel;
use app\common\utils\jPush\JpushHelper;

class PushMsgLogic extends BaseLogic
{
    /**
     * 极光实时推送店铺消息
     *
     * @param int $toShopId 要推送的店铺ID
     * @param string $nickname 昵称
     * @param string $msgType 消息类型
     */
    public function pushToShopMsg($toShopId, $nickname, $msgType = 'video_comment')
    {
        $shopPivotModel = model(UserHasShopModel::class);
        // 获取需要推送的店铺用户极光ID
        $shopPivot = $shopPivotModel->getSelectedShopUser(['shopId' => $toShopId]);
        $pushIds = array_filter(array_column($shopPivot, 'registrationId'));
        if ($pushIds) {
            switch ($msgType) {
                // 视频评论
                case 'video_comment':
                    $pushMsg = sprintf(config('response.msg71'), $nickname);
                    break;
                // 视频点赞
                case 'video_like':
                    $pushMsg = sprintf(config('response.msg72'), $nickname);
                    break;
                // 评论点赞
                case 'comment_like':
                    $pushMsg = sprintf(config('response.msg79'), $nickname);
                    break;
                // 回复评论
                case 'comment_reply':
                    $pushMsg = sprintf(config('response.msg80'), $nickname);
                    break;
                // 关注店铺
                case 'follow_shop':
                    $pushMsg = sprintf(config('response.msg68'), $nickname);
                    break;
                default:
                    $pushMsg = '';
                    break;
            }
            $msgCount = UserMessageModel::where(['to_shop_id' => $toShopId, 'read_status' => 0, 'delete_status' => 0])->count(1);
            JpushHelper::push($pushIds, [
                'title' => $pushMsg,
                'message' => $pushMsg,
                'contentType' => 'merchantMsgType',
                'extras' => ['msg_count' => $msgCount]
            ]);
        }
    }

    /**
     * 极光实时推送用户消息
     *
     * @param array $pushIds 要推送的极光ID
     * @param string $nickname 昵称
     * @param string $msgType 消息类型
     * @param int $toUserId 要推送的用户ID
     * @param array $extras 扩展参数
     */
    public function pushToUserMsg(array $pushIds, $nickname, $msgType = 'video_comment', $toUserId = 0, $extras = [])
    {
        $pushIds = array_filter($pushIds);
        if ($pushIds) {
            switch ($msgType) {
                // 视频评论
                case 'video_comment':
                    $pushMsg = sprintf(config('response.msg71'), $nickname);
                    break;
                // 视频点赞
                case 'video_like':
                    $pushMsg = sprintf(config('response.msg72'), $nickname);
                    break;
                // 评论点赞
                case 'comment_like':
                    $pushMsg = sprintf(config('response.msg79'), $nickname);
                    break;
                // 回复评论
                case 'comment_reply':
                    $pushMsg = sprintf(config('response.msg80'), $nickname);
                    break;
                // 关注
                case 'follow_user':
                    $pushMsg = sprintf(config('response.msg81'), $nickname);
                    break;
                // 用户发布新视频
                case 'user_new_video':
                // 店铺发布新视频
                case 'shop_new_video':
                    $pushMsg = sprintf(config('response.msg82'), $nickname);
                    break;
                default:
                    $pushMsg = '';
                    break;
            }
            $pushExtras = ['push_type' => $msgType];
            if ($toUserId) {
                // 未读的消息数量
                $pushExtras['msg_count'] = UserMessageModel::where(['to_user_id' => $toUserId, 'read_status' => 0, 'delete_status' => 0])->count(1);
            }
            JpushHelper::push($pushIds, [
                'title' => $pushMsg,
                'message' => $pushMsg,
                'extras' => array_merge($pushExtras, $extras)
            ]);
        }
    }
}

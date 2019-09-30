<?php

namespace app\api\controller\v3_0_0\merchant;

use app\api\Presenter;
use think\response\Json;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\UserMessageModel;
use app\api\validate\v3_0_0\MessageValidate;

class Message extends Presenter
{
    /**
     * 消息首页
     *
     * @return Json
     */
    public function index()
    {
        $shop = $this->request->selected_shop;
        try {
            // 消息数组
            $messages = model(UserMessageModel::class)->getMessageIndex(['toShopId' => $shop->id]);
            $response = [
                'total_unread_msg_count' => array_sum(array_column($messages, 'unreadMsgCount')),
                'shop_collect' => (object)[],
                'video_comment' => (object)[],
                'video_like' => (object)[],
                'new_fans' => (object) []
            ];
            foreach ($messages as $item) {
                $temp = [
                    'msg_type' => $item['msgType'] == 6 ? 0 : $item['msgType'], // 兼容旧版本新增粉丝类型为0
                    'unread_msg_count' => $item['unreadMsgCount'],
                    'nickname' => $item['nickname'],
                    'content' => $item['content'],
                    'generate_time' => dateTransformer($item['generateTime'])
                ];
                switch ($temp['msg_type']) {
                    case 1:
                        $response['shop_collect'] = $temp;
                        break;
                    case 2:
                    case 5:
                        $response['video_comment'] = $temp;
                        break;
                    case 3:
                    case 4:
                        $response['video_like'] = $temp;
                        break;
                    case 0:
                        $response['new_fans'] = $temp;
                        break;
                }
            }

            return apiSuccess($response);
        } catch (\Exception $e) {
            $logContent = '消息首页接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 消息列表
     *
     * @return Json
     */
    public function list()
    {
        $shop = $this->request->selected_shop;
        $page = $this->request->post('page/d', 0);
        // 获取请求参数
        $paramsArray = $this->request->post();

        // 实例化验证器
        $validate = validate(MessageValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('merchantList')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $response = ['msg_list' => []];
            // 消息类型分组
            switch ($paramsArray['msg_type']) {
                // 新增粉丝
                case 0:
                    $msgType = [6];
                    break;
                // 评论，包含视频评论和回复
                case 2:
                    $msgType = [2, 5];
                    break;
                // 点赞，包含视频点赞和评论点赞
                case 3:
                    $msgType = [3, 4];
                    break;
                // 商家收款
                default:
                    $msgType = [1];
                    break;
            }
            $messageModel = model(UserMessageModel::class);
            // 点赞消息，先给未读消息分组
            if ($paramsArray['msg_type'] == 3) {
                // 未读消息分组
                $unreadMessageGroup = $messageModel->getUnreadMessageGroup(['toShopId' => $shop->id]);
                foreach ($unreadMessageGroup as $item) {
                    // 更新未读消息组别
                    UserMessageModel::whereIn('id', explode(',', $item['msgId']))->update(['group_id' => StringHelper::uniqueCode()]);
                }
            }

            $messages = $messageModel->getMerchantMsgListByType([
                'toShopId' => $shop->id,
                'msgType' => $msgType,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
            ]);

            $groupUser = [];
            // 点赞消息才有分组
            if ($paramsArray['msg_type'] == 3) {
                // 消息组下用户头像
                $hasGroup = array_filter($messages, function ($item) {
                    return $item['msgGroupCount'] > 1;
                });
                $groupUser = $messageModel->getGroupUser([
                    'excludeId' => array_column($hasGroup, 'id'),
                    'groupId' => array_column($hasGroup, 'groupId')
                ]);
            }

            foreach ($messages as $item) {
                $temp = [
                    'msg_type' => $item['msgType'] == 6 ? 0 : $item['msgType'], // 兼容旧版本新增粉丝类型为0
                    'title' => $item['title'],
                    'content' => $item['content'],
                    'user_id' => $item['userId'],
                    'nickname' => $item['nickname'],
                    'avatar' => getImgWithDomain($item['avatar']),
                    'thumb_avatar' => getImgWithDomain($item['thumbAvatar']),
                    'generate_time' => dateTransformer($item['generateTime'])
                ];
                // 评论和点赞
                if (in_array($item['msgType'], [2, 3, 4, 5])) {
                    $temp = array_merge($temp, [
                        'comment_id' => $item['commentId'] ?: 0,
                        'video_id' => $item['videoId'],
                        'video_url' => $item['videoUrl'],
                        'cover_url' => $item['coverUrl'],
                        'video_status' => $item['videoStatus'] == 1 ? 1 : 0,
                        'group_count' => $item['msgGroupCount'],
                        'group_user' => []
                    ]);
                }

                foreach ($groupUser as $k => $group) {
                    if ($item['groupId'] == $group['groupId']) {
                        $temp['group_user'][] = [
                            'user_id' => $group['userId'],
                            'thumb_avatar' => getImgWithDomain($group['thumbAvatar'])
                        ];

                        unset($groupUser[$k]);
                    }
                }

                $response['msg_list'][] = $temp;
            }

            // 更新消息为已读
            $messageModel->where(['to_shop_id' => $shop->id, 'msg_type' => $msgType])->update(['read_status' => 1]);

            return apiSuccess($response);
        } catch (\Exception $e) {
            $logContent = '消息列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

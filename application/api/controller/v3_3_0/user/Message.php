<?php

namespace app\api\controller\v3_3_0\user;

use app\api\Presenter;
use app\api\validate\v3_0_0\MessageValidate;
use app\api\model\v3_0_0\UserMessageModel;

class Message extends Presenter
{
    /**
     * 消息首页
     *
     * @return \think\response\Json
     */
    public function index()
    {
        $user = $this->request->user;
        try {
            // 消息数组
            $messages = model(UserMessageModel::class)->getMessageIndex(['toUserId' => $user->id]);
            $response = [
                'total_unread_msg_count' => array_sum(array_column($messages, 'unreadMsgCount')),
                'new_fans' => (object) [],
                'video_comment' => (object) [],
                'video_like' => (object) []
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
        $user = $this->request->user;
        $page = $this->request->post('page/d', 0);
        // 获取请求参数
        $paramsArray = $this->request->post();

        // 实例化验证器
        $validate = validate(MessageValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('userList')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $response = ['msg_list' => []];
            // 消息类型分组
            switch ($paramsArray['msg_type']) {
                // 评论，包含视频评论和回复
                case 2:
                    $msgType = [2, 5];
                    break;
                // 点赞，包含视频点赞和评论点赞
                case 3:
                    $msgType = [3, 4];
                    break;
                // 新增粉丝
                default:
                    $msgType = [6];
                    break;
            }
            $messageModel = model(UserMessageModel::class);
            $messages = $messageModel->getUserMsgListByType([
                'toUserId' => $user->id,
                'msgType' => $msgType,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);
            foreach ($messages as $item) {
                $temp = [
                    'msg_type' => $item['msgType'] == 6 ? 0 : $item['msgType'], // 兼容旧版本新增粉丝类型为0
                    'title' => $item['title'],
                    'content' => $item['content'],
                    'user_id' => (int) $item['userId'],
                    'nickname' => (string) $item['nickname'],
                    'avatar' => getImgWithDomain($item['avatar']),
                    'thumb_avatar' => getImgWithDomain($item['thumbAvatar']),
                    'generate_time' => dateTransformer($item['generateTime']),
                    'shop_id' => (int) $item['shopId']
                ];
                // 评论和点赞
                if (in_array($item['msgType'], [2, 3, 4, 5])) {
                    $temp = array_merge($temp, [
                        'comment_id' => $item['commentId'] ?: 0,
                        'video_id' => $item['videoId'],
                        'video_type' => $item['videoType'],
                        'video_url' => $item['videoUrl'],
                        'cover_url' => $item['coverUrl'],
                        'video_status' => $item['videoStatus'] == 1 ? 1 : 0,
                    ]);
                }

                $response['msg_list'][] = $temp;
            }

            // 更新消息为已读
            $messageModel->where(['to_user_id' => $user->id, 'msg_type' => $msgType])->update(['read_status' => 1]);

            return apiSuccess($response);
        } catch (\Exception $e) {
            $logContent = '消息列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

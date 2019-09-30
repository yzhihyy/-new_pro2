<?php

namespace app\api\controller\v3_3_0\user;

use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_3_0\VideoModel;
use app\api\logic\v3_0_0\user\FollowLogic;
use app\api\Presenter;

class UserCenter extends Presenter
{
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
            // 类型
            $type = input('type', 0);
            // 获取视频列表
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
            if ($type) {
                $where['type'] = $type;
            }
            $result = $videoModel->getUserVideoList($where);
            $videoList = $result['videoList'];

            // 处理返回数据
            $logic = new FollowLogic();
            $videoList = $logic->handleVideoList($videoList, $selfUserId);
            // 返回数据
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $videoList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取用户的视频列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

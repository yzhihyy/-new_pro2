<?php

namespace app\api\controller\v3_5_0\user;

use app\api\Presenter;
use app\api\model\v3_5_0\VideoActionModel;
use app\api\model\v3_5_0\VideoModel;
use app\api\logic\v3_0_0\user\FollowLogic;

class User extends Presenter
{
    /**
     * 我喜欢的作品列表.
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
            // 类型
            $type = input('type', 0);
            // 获取列表
            $videoActionModel = model(VideoActionModel::class);
            $userId = $this->getUserId();
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            if ($type) {
                $where['type'] = $type;
            }
            $result = $videoActionModel->getMyJoyVideoList($where);
            $joyVideoList = $result['videoList'];
            // 处理返回数据
            $logic = new FollowLogic();
            $joyVideoList = $logic->handleVideoList($joyVideoList, $userId, true);
            // 返回数据
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $joyVideoList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我喜欢的视频列表接口异常信息：' . $e->getMessage();
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
            // 类型
            $type = input('type', 0);
            // 获取列表
            $videoModel = model(VideoModel::class);
            $userId = $this->getUserId();
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            if ($type) {
                $where['type'] = $type;
            }
            $result = $videoModel->getMyVideoList($where);
            $videoList = $result['videoList'];
            // 处理返回数据
            $logic = new FollowLogic();
            $videoList = $logic->handleVideoList($videoList, $userId, false);
            // 返回数据
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $videoList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我的作品列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

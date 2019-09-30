<?php

namespace app\api\controller\v3_3_0\user;

use app\api\Presenter;
use think\Response\Json;
use app\api\logic\v3_0_0\user\{
    ThemeActivityLogic, FollowLogic
};
use app\api\logic\v3_3_0\user\{
    TopicLogic, VideoLogic
};
use app\api\model\v3_3_0\{
    TopicModel, VideoModel
};
use app\api\validate\v3_3_0\VideoValidate;

class Home extends Presenter
{
    /**
     * 首页 - 推荐
     *
     * @return Json
     */
    public function index()
    {
        $page = $this->request->get('page', 0);
        $page < 0 && ($page = 0);

        try {
            // 获取首页话题列表
            $params = [
                'page' => $page,
            ];
            $topicLogic = new TopicLogic();
            $topicList = $topicLogic->getHomeTopicList($params);

            // 若无话题,则展示所有进行中的主题;否则每4个视频插入一个主题
            $themeLogic = new ThemeActivityLogic();
            $themeList = $themeLogic->themeActivityList([
                'theme_status' => [2],
                'page' => $page,
                'limit' => empty($topicList) ? config('parameters.page_size_level_2') : (ceil(count($topicList) / 4)),
            ]);

            return apiSuccess([
                'topic_list' => $topicList,
                'theme_list' => $themeList,
            ]);
        } catch (\Exception $e) {
            generateApiLog("首页接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 首页 - 地区
     *
     * @return Json
     */
    public function region()
    {
        $page = $this->request->get('page', 0);
        $page < 0 && ($page = 0);

        try {
            // 请求参数
            $paramsArray = $this->request->get();
            // 参数校验
            $validateResult = $this->validate($paramsArray, VideoValidate::class . '.Region');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $topicLogic = new TopicLogic();
            $condition = array_merge($topicLogic->getReviewDisplayRule(), [
                'type' => 2,
                'cityId' => $paramsArray['city_id'],
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
            ]);
            // 获取首页地区的列表数据
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $videoList = $videoModel->getHomeVideoList($condition);
            $videoList = (new VideoLogic())->transformVideoData($videoList->toArray());

            return apiSuccess([
                'video_list' => $videoList,
            ]);
        } catch (\Exception $e) {
            generateApiLog("首页地区接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 获取指定[话题|城市]下的视频和随记列表 或 获取指定视频或随记
     *
     * @return Json
     */
    public function getVideoList()
    {
        $page = $this->request->get('page', 0);
        $page < 0 && ($page = 0);

        try {
            // 请求参数
            $paramsArray = $this->request->get();
            // 参数校验
            $validateResult = $this->validate($paramsArray, VideoValidate::class . '.VideoList');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $topicLogic = new TopicLogic();
            $userId = $this->getUserId();
            // 0:获取视频和随记, 1:获取视频
            $type = $paramsArray['type'] ?? -1;
            $condition = array_merge($topicLogic->getReviewDisplayRule(), [
                'userId' => $userId,
                'type' => $type,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
                'audit_status' => 1,
            ]);
            // 处理视频数据
            $followLogic = new FollowLogic();
            $commentFlag = $type == 1 ? false : true;

            // 获取指定话题下的视频和随记
            if ($paramsArray['mode'] == 1) {
                $condition['topicId'] = $paramsArray['topic_id'];
                /** @var TopicModel $topicModel */
                $topicModel = model(TopicModel::class);
                $videoList = $topicModel->getTopicVideoList($condition);
            }
            // 获取指定城市下的视频和随记|获取指定随记详情
            else {
                // 获取指定城市下的视频和随机
                $condition['videoId'] = $paramsArray['video_id'];
                $condition['mode'] = $paramsArray['mode'];
                /** @var VideoModel $videoModel */
                $videoModel = model(VideoModel::class);

                if ($paramsArray['mode'] == 2) {
                    $condition['cityId'] = $paramsArray['city_id'];
                    $videoList = $videoModel->getVideoList($condition);

                }elseif($paramsArray['mode'] == 3){ // 获取指定随记详情
                    unset($condition['audit_status']);
                    $videoList = $videoModel->getVideoList($condition);
                    $responseData = $followLogic->handleVideoList($videoList, $userId);
                    return apiSuccess(array_shift($responseData));
                }
            }

            return apiSuccess([
                'video_list' => $followLogic->handleVideoList($videoList, $userId, $commentFlag)
            ]);
        } catch (\Exception $e) {
            generateApiLog("获取视频或随记列表接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}

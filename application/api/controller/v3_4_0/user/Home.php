<?php

namespace app\api\controller\v3_4_0\user;

use app\api\logic\v3_0_0\user\FollowLogic;
use app\api\logic\v3_4_0\user\ThemeActivityLogic;
use app\api\logic\v3_4_0\user\TopicLogic;
use app\api\model\v3_0_0\VideoModel;
use app\api\Presenter;

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
        $themePage = $this->request->get('theme_page', 0);
        $cityId = $this->request->get('city_id', 0);
        $page < 0 && ($page = 0);
        $themePage < 0 && ($themePage = 0);


        try {
            // 获取首页话题列表
            $params = [
                'page' => $page,
                'cityId' => $cityId,
            ];
            $topicLogic = new TopicLogic();
            $topicList = $topicLogic->getHomeTopicList($params);

            // 若无话题,则展示所有进行中的主题;否则每4个视频插入一个主题
            /*$themeLogic = new ThemeActivityLogic();
            $themeList = $themeLogic->themeActivityList([
                'theme_status' => [2],
                'page' => $themePage,
                'limit' => 3,//每页最多3个，因为话题每页最多10个， ceil(10/4)=3
                'city_id' => $cityId,
            ]);*/
            return apiSuccess([
                'topic_list' => $topicList,
                'theme_list' => [],
            ]);
        } catch (\Exception $e) {
            generateApiLog("首页接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 首页 - 关注
     *
     * @return Json
     */
    public function follow()
    {
        $user = $this->request->user;
        $page = $this->request->get('page/d', 0);
        try {
            $videos = model(VideoModel::class)->searchVideo([
                'userId' => $user->id,
                'followedFlag' => true,
                'followedOrderFlag' => true,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);

            // 如果关注页无关注人发布作品，则展示所有进行中的主题
            if ($videos->isEmpty()) {
                $themeLimit = config('parameters.page_size_level_2');
            } else {
                // 每5个视频插入一个主题
                $themeLimit = ceil(count($videos) / 5);
            }
            $themeLogic = new ThemeActivityLogic();
            $theme = $themeLogic->themeActivityList([
                'page' => $page,
                'limit' => $themeLimit,
                'theme_status' => [2] // 进行中
            ]);

            $followLogic = new FollowLogic();
            $response = [
                'theme_limit' => $theme,
                'video_list' => $followLogic->handleVideoList($videos, $user->id)
            ];

            return apiSuccess($response);
        } catch (\Exception $e) {
            $logContent = '关注首页接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use think\response\Json;
use app\api\validate\v3_0_0\FollowValidate;
use app\api\logic\v3_0_0\RobotLogic;
use app\api\logic\v3_0_0\user\{FollowLogic, ThemeActivityLogic};
use app\api\model\v3_0_0\{VideoModel};

class Follow extends Presenter
{
    /**
     * 关注首页
     *
     * @return Json
     */
    public function index()
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

    /**
     * 关注/取消关注
     *
     * @return Json
     */
    public function followAction()
    {
        $user = $this->request->user;
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(FollowValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('followAction')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $followLogic = new FollowLogic();
            $result = $followLogic->followAction($user, $paramsArray);
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            // 首次关注他人时，增加机器人粉丝量
            if ($result['data']['first_follow_flag'] == 1) {
                $robotLogic = new RobotLogic();
                $robotLogic->handleFollowOthers($user->id);
            }

            return apiSuccess([
                'is_follow' => $result['data']['is_follow'],
                'user_info' => $result['data']['user_info'],
                'social_info' => $result['data']['social_info'],
            ]);
        } catch (\Exception $e) {
            $logContent = '关注/取消关注接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

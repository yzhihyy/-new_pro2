<?php

namespace app\api\controller\v3_3_0\user;

use app\api\Presenter;
use think\Response\Json;
use app\api\logic\v3_0_0\RobotLogic;
use app\api\validate\v3_3_0\TopicValidate;

class Topic extends Presenter
{
    /**
     * 保存话题浏览历史
     *
     * @return Json
     */
    public function saveTopicViewHistory()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, TopicValidate::class . '.History');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $userId = $this->getUserId();
            // 处理用户话题浏览
            $robotLogic = new RobotLogic();
            $robotLogic->handleVideoOrTopicView($userId, 2, 1);

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("保存话题浏览历史接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}
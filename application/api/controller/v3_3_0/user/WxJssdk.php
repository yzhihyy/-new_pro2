<?php

namespace app\api\controller\v3_3_0\user;

use app\api\Presenter;
use app\api\service\WxJssdkService;
use app\api\validate\v3_3_0\WxJssdkValidate;

class WxJssdk extends Presenter
{
    /**
     * 分享接口
     *
     * @return \think\response\Json
     */
    public function share()
    {
        // 获取请求参数
        $paramsArray = $this->request->get();
        // 实例化验证器
        $validate = validate(WxJssdkValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('share')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $service = new WxJssdkService();
            $response = $service->buildConfig([
                'updateAppMessageShareData',
                'updateTimelineShareData',
                'onMenuShareWeibo',
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareQZone'
            ], $paramsArray['url']);

            return apiSuccess($response);
        } catch (\Exception $e) {
            $logContent = '微信jssdk分享接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}

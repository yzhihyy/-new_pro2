<?php

namespace app\api\controller\v3_0_0\user;

use app\api\logic\v3_0_0\user\HomeLogic;
use app\api\model\v3_0_0\AppFlashModel;
use app\api\Presenter;
use app\api\validate\v3_0_0\VideoValidate;
use app\common\utils\date\DateHelper;
use think\Response\Json;

class Home extends Presenter
{
    /**
     * 首页
     *
     * @return Json
     */
    public function index()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->get();
            // 参数校验
            $validateResult = $this->validate($paramsArray, VideoValidate::class . '.Home');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $userId = $this->getUserId();
            // 获取首页视频列表
            $homeLogic = new HomeLogic();
            $responseData = $homeLogic->getHomeVideoList($paramsArray, $userId);

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog("首页接口异常：{$e->getMessage()}");
        }

        return apiError();
    }


    /**
     * 首页闪屏接口
     * @return Json
     */
    public function flashShow()
    {
        try{
            $params = $this->request->get();
            $flashShow = model(AppFlashModel::class)->flashShow($params);

            if(!empty($flashShow)){
                $flashShow['image_url'] = config('app.resources_domain').$flashShow['image_url'];
                $info = $flashShow;
            }
            $data = [
                'flash_info' => $info ?? (object)[]
            ];
            return apiSuccess($data);

        }catch (\Exception $e){
            generateApiLog("首页闪屏接口异常: {$e->getMessage()}");
        }
        return apiError();
    }
}

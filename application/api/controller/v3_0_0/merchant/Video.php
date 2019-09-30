<?php

namespace app\api\controller\v3_0_0\merchant;

use app\api\Presenter;
use think\response\Json;
use app\api\model\v3_0_0\VideoModel;

class Video extends Presenter
{
    /**
     * 视频置顶或取消置顶.
     *
     * @return Json
     */
    public function handleTop()
    {
        try {
            $videoId = input('video_id', 0);
            if ($videoId <= 0) {
                return apiError('视频不存在');
            }
            // 获取视频并判断是否存在
            $shopInfo = $this->request->selected_shop;
            $shopId = $shopInfo['id'];
            $videoModel = model(VideoModel::class);
            $video = $videoModel->where([
                'id' => $videoId,
                'status' => 1,
                'shop_id' => $shopId,
            ])->find();
            if (empty($video)) {
                return apiError(config('response.msg77'));
            }
            // 判断置顶状态，如果已经置顶则取消置顶，如果未置顶则置顶。
            if ($video->is_top == 1) {
                // 取消置顶
                $videoModel::update(['is_top' => 0], ['id' => $videoId]);
            } else {
                // 视频置顶
                $videoModel::update(['is_top' => 0], ['shop_id' => $shopId]);
                $videoModel::update(['is_top' => 1], ['id' => $videoId]);
            }
            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("视频置顶接口请求失败：{$e->getMessage()}");
        }
        return apiError();
    }
}

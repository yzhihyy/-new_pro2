<?php

namespace app\api\controller\v3_6_0\user;

use app\api\Presenter;
use app\api\model\v3_6_0\VideoModel;
use app\api\logic\v3_0_0\user\FollowLogic;

class Shop extends Presenter
{
    /**
     * 获取店铺视频列表.
     *
     * @return \think\response\Json
     */
    public function getShopVideoList()
    {
        try {
            // APP审核时不返还视频
            $reviewDisplayRule = config('parameters.shop_detail_video_display_rule_for_review');
            if ($reviewDisplayRule['enable_flag']) {
                $platform = $this->request->header('platform');
                $version = $this->request->header('version');
                if (in_array($platform, $reviewDisplayRule['platform']) && version_compare($version, $reviewDisplayRule['version']) == 0) {
                    $responseData = [
                        'shop_video_list' => []
                    ];
                    return apiSuccess($responseData);
                }
            }
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 店铺ID
            $shopId = input('shop_id/d', 0);
            if ($shopId <= 0) {
                return apiError(config('response.msg10'));
            }
            // 获取商家视频列表
            $where = [
                'shopId' => $shopId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            // 类型
            $type = input('type', 0);
            if ($type) {
                $where['type'] = $type;
            }
            // 是否查为商家自己查看自己的视频列表
            $where['seeMySelf'] = input('see_myself/d', 0);
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            $videoModel = model(VideoModel::class);
            $shopVideoList = $videoModel->getShopVideoList($where);
            // 处理返回数据
            $logic = new FollowLogic();
            $videoList = $logic->handleVideoList($shopVideoList, $userId);
            $responseData = [
                'shop_video_list' => $videoList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取店铺视频列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

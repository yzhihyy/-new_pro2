<?php

namespace app\api\controller\v3_0_0\user;

use app\api\logic\v3_0_0\RobotLogic;
use app\api\model\v3_5_0\VideoAlbumModel;
use app\api\model\v3_0_0\VideoReportTypeModel;
use app\api\Presenter;
use app\common\utils\date\DateHelper;
use think\Db;
use think\response\Json;
use app\api\model\v3_0_0\VideoModel;
use app\api\logic\v3_0_0\user\{
    VideoLogic, HomeLogic
};
use app\api\validate\v3_0_0\VideoValidate;

class Video extends Presenter
{
    /**
     * 点赞/取消点赞(视频/评论)
     *
     * @return Json
     */
    public function doLikeAction()
    {
        $user = $this->request->user;
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(VideoValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('like')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        if (empty($paramsArray['video_id']) && empty($paramsArray['comment_id'])) {
            return apiError(config('response.msg53'));
        }

        try {
            $videoLogic = new VideoLogic();
            // 视频点赞
            if (isset($paramsArray['video_id']) && $paramsArray['video_id']) {
                $result = $videoLogic->videoLike($user, $paramsArray['video_id']);
            }
            // 评论/回复点赞
            elseif (isset($paramsArray['comment_id']) && $paramsArray['comment_id']) {
                $result = $videoLogic->commentLike($user, $paramsArray['comment_id']);
            }
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            return apiSuccess($result['data']);
        } catch (\Exception $e) {
            $logContent = '点赞/取消点赞(视频/评论)接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 视频评论/回复
     *
     * @return Json
     */
    public function comment()
    {
        $user = $this->request->user;
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(VideoValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('comment')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $videoLogic = new VideoLogic();
            $result = $videoLogic->comment($user, $paramsArray);
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            return apiSuccess($result['data']);
        } catch (\Exception $e) {
            $logContent = '视频评论接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 视频评论列表
     *
     * @return Json
     */
    public function commentList()
    {
        // 获取请求参数
        $paramsArray = $this->request->get();
        $page = $this->request->get('page/d', 0);
        // 实例化验证器
        $validate = validate(VideoValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('commentList')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $videoLogic = new VideoLogic();
            $result = $videoLogic->commentList($paramsArray['video_id'], $page);

            return apiSuccess($result);
        } catch (\Exception $e) {
            $logContent = '视频评论列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 视频评论的回复列表
     *
     * @return Json
     */
    public function commentReplyList()
    {
        // 获取请求参数
        $paramsArray = $this->request->get();
        $page = $this->request->get('page/d', 0);
        // 实例化验证器
        $validate = validate(VideoValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('commentReplyList')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $videoLogic = new VideoLogic();
            $result = $videoLogic->commentReplyList($paramsArray, $page);

            return apiSuccess($result);
        } catch (\Exception $e) {
            $logContent = '视频评论回复列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 视频转发
     *
     * @return Json
     */
    public function doShareAction()
    {
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(VideoValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('share')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $videoLogic = new VideoLogic();
            $result = $videoLogic->videoShare($this->getUserId(), $paramsArray['video_id']);
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            // 用户登录情况下，分享视频时，增加机器人粉丝量
            if ($this->getUserId()) {
                $robotLogic = new RobotLogic();
                $robotLogic->handleVideoShare($this->getUserId());
            }

            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '点赞/取消点赞(视频/评论)接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 保存视频播放历史
     *
     * @return Json
     */
    public function savePlayHistory()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, VideoValidate::class . '.History');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $videoList = json_decode($paramsArray['video_list'], true);
            if (empty($videoList) || !is_array($videoList)) {
                return apiError(config('response.msg53'));
            }

            $userId = $this->getUserId();
            // 保存视频播放历史
            $videoLogic = new VideoLogic();
            $videoLogic->savePlayHistory($videoList, $userId);

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("保存视频播放历史：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 获取视频详情
     *
     * @return Json
     */
    public function getVideoDetail()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->get();
            // 参数校验
            $validateResult = $this->validate($paramsArray, VideoValidate::class . '.VideoDetail');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $userId = $this->getUserId();
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            // 获取视频详情
            $videoDetail = $videoModel->getVideoDetail([
                'userId' => $userId,
                'videoId' => $paramsArray['video_id']
            ]);
            if (empty($videoDetail)) {
                return apiError(config('response.msg69'));
            }

            $responseData = (new HomeLogic())->transformVideoData($videoDetail->toArray(), 2);
            // 随记相册
            $videoAlbumModel = model(VideoAlbumModel::class);
            $videoAlbumData = $videoAlbumModel->getVideoAlbum(['videoId' => $paramsArray['video_id']]);
            $videoAlbum = [];
            if (!empty($videoAlbumData)) {
                foreach ($videoAlbumData as $value) {
                    foreach ($value as $v) {
                        $videoAlbum[] = $v;
                    }
                }
            }
            $responseData['album_list'] = $videoAlbum;
            return apiSuccess(['video_info' => $responseData]);
        } catch (\Exception $e) {
            generateApiLog("获取视频详情：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 获取视频举报类型列表
     * @return Json
     */
    public function videoReportTypeList()
    {
        try{
            $model = model(VideoReportTypeModel::class);
            $list = $model
                ->field(['id as report_type_id', 'report_name'])
                ->where('status', '=', 1)
                ->order('sort','DESC')
                ->select();

            $info = [
                'list' => $list,
            ];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog("获取视频举报类型列表失败：{$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 视频举报接口
     * @return Json
     */
    public function videoReport()
    {
        try {
            $params = $this->request->post();
            $validate = validate(VideoValidate::class);
            $check = $validate->scene('videoReport')->check($params);
            if (!$check) {
                return apiError($validate->getError());
            }
            if ($params['report_type_id'] == 7 && empty($params['content'])) {
                return apiError(config('response.msg76'));
            }
            $data = [
                'user_id' => $this->request->user->id,
                'video_id' => $params['video_id'],
                'report_type_id' => $params['report_type_id'],
                'report_content' => $params['content'] ?? '',
                'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
            ];
            $add = Db::name('video_report')->insert($data);
            if ($add) {
                return apiSuccess();
            }
        }catch (\Exception $e){
            generateApiLog("视频举报接口请求失败：{$e->getMessage()}");
        }
        return apiError();
    }

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
            $userId = $this->getUserId();
            $videoModel = model(VideoModel::class);
            $video = $videoModel->where([
                'id' => $videoId,
                'status' => 1,
                'user_id' => $userId,
                'shop_id' => 0,
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
                $videoModel::update(['is_top' => 0], ['user_id' => $userId, 'shop_id' => 0]);
                $videoModel::update(['is_top' => 1], ['id' => $videoId]);
            }
            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("视频置顶接口请求失败：{$e->getMessage()}");
        }
        return apiError();
    }
}

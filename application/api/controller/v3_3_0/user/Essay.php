<?php

namespace app\api\controller\v3_3_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\{
    AreaModel, VideoModel as VideoModelBefore
};
use app\api\model\v3_3_0\{
    VideoModel, VideoAlbumModel
};
use app\api\validate\v3_3_0\{
    EssayValidate
};
use app\api\logic\v3_0_0\user\{
    FollowLogic
};
use app\api\logic\v3_0_0\RobotLogic;

class Essay extends Presenter
{
    /**
     * 新增随记
     * @return json
     */
    public function addEssay()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(EssayValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('addEssay')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 图片数组
            $imageArray = json_decode($paramsArray['image_list'], true);
            if (empty($imageArray)) {
                return apiError(config('response.msg38'));
            }
            // 上传图片张数限制
            $uploadMaxCount = config('parameters.upload_max_count_level_9');
            // 图片不能超过%d张
            if (count($imageArray) > $uploadMaxCount) {
                $msg = config('response.msg40');
                return apiError(sprintf($msg, $uploadMaxCount));
            }
            $user = $this->request->user;
            // 用户id
            $userId = $user->id;
            $date = date('Y-m-d H:i:s');
            // 视频数据
            $videoData = [
                'type' => 2,
                'user_id' => $userId,
                'cover_url' => $paramsArray['cover'],
                'video_width' => $paramsArray['cover_width'],
                'video_height' => $paramsArray['cover_height'],
                'generate_time' => $date,
                'title' => isset($paramsArray['title']) ? $paramsArray['title'] : '',
            ];
            if (!empty($paramsArray['adcode'])) {
                $videoData['adcode'] = $paramsArray['adcode'];
                // 实例化区域表模型
                $provinceModel = model(AreaModel::class);
                $cityArray = [
                    'adcode' => $paramsArray['adcode']
                ];
                // 获取省市区id
                $cityIdArray = $provinceModel->getCityIdByAdcode($cityArray);
                if (!empty($cityIdArray)) {
                    $videoData['province_id'] = $cityIdArray['provinceId'];
                    $videoData['city_id'] = $cityIdArray['cityId'];
                    $videoData['area_id'] = $cityIdArray['areaId'];
                }
            }
            if (!empty($paramsArray['location'])) {
                $videoData['location'] = $paramsArray['location'];
            }
            if (!empty($paramsArray['longitude']) && !empty($paramsArray['latitude'])) {
                $videoData['longitude'] = $paramsArray['longitude'];
                $videoData['latitude'] = $paramsArray['latitude'];
            }
            // v3.5.0版本新增关联店铺ID
            if (!empty($paramsArray['relation_shop_id'])) {
                $videoData['relation_shop_id'] = $paramsArray['relation_shop_id'];
            }
            // 实例化视频模型
            $model = $videoModel = model(VideoModel::class);
            // 启动事务
            $model->startTrans();
            $videoResult = $videoModel->insertGetId($videoData);
            if (empty($videoResult)) {
                return apiError(config('response.msg11'));
            }

            // 实例化相册模型
            $albumModel = model(VideoAlbumModel::class);
            $albumData = [];
            foreach ($imageArray as $imageValue) {
                if (empty($imageValue['image'])) {
                    return apiError(config('response.msg39'));
                }
                $tempArray = [
                    'video_id' => $videoResult,
                    'image' => $imageValue['image'],
                    'generate_time' => $date
                ];
                if (isset($imageValue['sort'])) {
                    $tempArray['sort'] = $imageValue['sort'];
                }
                $albumData[] = $tempArray;
            }
            $albumResult = $albumModel->insertAll($albumData);
            if (empty($albumResult)) {
                return apiError(config('response.msg11'));
            }
            // 提交事务
            $model->commit();
            $data = [
                'video_id' => $videoResult,
                'nickname' => $user->nickname,
                'avatar' => getImgWithDomain($user->avatar)
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            // 机器人逻辑
            $robotLogic = new RobotLogic();
            $robotLogic->handleVideoRelease($videoResult, $userId, 1);
            return apiSuccess($data);
        } catch (\Exception $e) {
            // 回滚事务
            isset($model) && $model->rollback();
            $logContent = '新增随记接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 随记列表
     * @return json
     */
    public function essayAndVideoList()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化视频模型
            $videoModel = model(VideoModelBefore::class);
            $userId = $this->getUserId();
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3'),
                'userId' => $userId
            ];
            // 获取随记列表
            $essayArray = $videoModel->searchVideo($condition);
            $followLogic = new FollowLogic();
            $data = [
                'video_list' => $followLogic->handleVideoList($essayArray, $userId)
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '随记列表异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\{
    MusicModel, UserHasShopModel, VideoModel, AreaModel
};
use app\api\validate\v3_0_0\{
    EditVideoValidate, VideoValidate
};
use app\api\logic\v3_0_0\RobotLogic;

class EditVideo extends Presenter
{
    /**
     * 获取音乐列表
     * @return json
     */
    public function getMusicList()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化音乐模型
            $musicModel = model(MusicModel::class);
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3')
            ];
            if (!empty($paramsArray['keyword'])) {
                $condition['keyword'] = $paramsArray['keyword'];
            }
            // 获取音乐列表
            $musicArray = $musicModel->getMusicList($condition);
            $musicList = [];
            if (!empty($musicArray)) {
                foreach ($musicArray as $value) {
                    $info = [
                        'music_id' => $value['id'],
                        'music_name' => $value['music_name'],
                        'music_url' => $value['music_url'],
                        'artist_name' => $value['artist_name'],
                        'cover_url' => $value['cover_url'],
                        'duration' => $value['duration'],
                    ];
                    $musicList[] = $info;
                }
            }
            $data = [
                'music_list' => $musicList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '获取音乐列表异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 获取我的店铺列表
     * @return json
     */
    public function getMyShopList()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化用户和店铺关联表模型
            $userHasShopModel = model(UserHasShopModel::class);
            $user = $this->request->user;
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3'),
                'userId' => $user->id
            ];
            // 获取音乐列表
            $shopArray = $userHasShopModel->getMyShopList($condition);
            $shopList = [];
            if (!empty($shopArray)) {
                foreach ($shopArray as $value) {
                    $info = [
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shopName'],
                    ];
                    $shopList[] = $info;
                }
            }
            $data = [
                'shop_list' => $shopList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '获取我的店铺列表异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 新增视频
     * @return json
     */
    public function addVideo()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(EditVideoValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('addVideo')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $user = $this->request->user;
            // 用户id
            $userId = $user->id;
            // 视频数据
            $videoData = [
                'user_id' => $userId,
                'title' => $paramsArray['title'],
                'video_url' => $paramsArray['video'],
                'cover_url' => $paramsArray['cover'],
                'video_width' => $paramsArray['video_width'],
                'video_height' => $paramsArray['video_height'],
                'generate_time' => date('Y-m-d H:i:s'),
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
            $robotItemId = $userId;
            $robotItemType = 1;
            if (!empty($paramsArray['shop_id'])) {
                $videoData['shop_id'] = $paramsArray['shop_id'];
                $robotItemId = $paramsArray['shop_id'];
                $robotItemType = 2;
            }
            // v3.5.0版本新增关联店铺ID
            if (!empty($paramsArray['relation_shop_id'])) {
                $videoData['relation_shop_id'] = $paramsArray['relation_shop_id'];
            }
            // 实例化视频模型
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $result = $videoModel->insertGetId($videoData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            // 机器人逻辑
            $robotLogic = new RobotLogic();
            $robotLogic->handleVideoRelease($result, $robotItemId, $robotItemType);
            return apiSuccess([
                'video_id' => $result,
                'nickname' => $user->nickname,
                'avatar' => getImgWithDomain($user->avatar)
            ]);
        } catch (\Exception $e) {
            $logContent = '新增视频接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 用户删除视频接口
     * @return json
     */
    public function deleteVideo()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(VideoValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('deleteVideo')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化视频模型
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $videoData = ['status' => 2];
            $videoWhere = [
                ['user_id', '=', $userId],
                ['status', '=', 1],
                ['id', 'in', trim($paramsArray['video_id'], ',')]
            ];
            // 删除视频
            $result = $videoModel->where($videoWhere)->update($videoData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '用户删除视频接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

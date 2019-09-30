<?php

namespace app\api\controller\v3_0_0\merchant;

use app\api\model\v3_0_0\{
    PhotoAlbumModel, ProvinceModel, VideoModel
};
use app\api\Presenter;
use app\api\validate\v3_0_0\PublishValidate;
use app\api\validate\v3_0_0\VideoValidate;
use app\api\service\UploadService;

class Publish extends Presenter
{
    /**
     * 新增视频
     * @return json
     */
    public function addVideo()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PublishValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('addVideo')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            $shopInfo = $this->request->selected_shop;
            if (empty($paramsArray['province_id']) && empty($paramsArray['city_id']) && empty($paramsArray['area_id'])) {
                // 实例化省份模型
                /** @var ProvinceModel $provinceModel */
                $provinceModel = model(ProvinceModel::class);
                $cityArray = [
                    'province' => mb_substr($shopInfo['shopProvince'], 0, -1, 'utf-8'),
                    'city' => mb_substr($shopInfo['shopCity'], 0, -1, 'utf-8'),
                    'area' => mb_substr($shopInfo['shopArea'], 0, -1, 'utf-8'),
                ];
                // 获取省市区id
                $cityIdArray = $provinceModel->getCityId($cityArray);
                $paramsArray['province_id'] = !empty($cityIdArray['provinceId']) ? $cityIdArray['provinceId'] : 0;
                $paramsArray['city_id'] = !empty($cityIdArray['cityId']) ? $cityIdArray['cityId'] : 0;
                $paramsArray['area_id'] = !empty($cityIdArray['areaId']) ? $cityIdArray['areaId'] : 0;
            }
            // 实例化视频模型
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $videoData = [
                'user_id' => $userId,
                'shop_id' => $shopInfo['id'],
                'title' => $paramsArray['title'],
                'video_url' => $paramsArray['video'],
                'cover_url' => $paramsArray['cover'],
                'province_id' => $paramsArray['province_id'],
                'city_id' => $paramsArray['city_id'],
                'area_id' => $paramsArray['area_id'],
                'video_width' => $paramsArray['video_width'],
                'video_height' => $paramsArray['video_height'],
                'generate_time' => date('Y-m-d H:i:s'),
            ];
            $result = $videoModel->insertGetId($videoData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess(['video_id' => $result]);
        } catch (\Exception $e) {
            $logContent = '新增视频接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 新增商家相册
     * @return json
     */
    public function addMerchantAlbum()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PublishValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('addMerchantAlbum')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $shopInfo = $this->request->selected_shop;
            // 实例化相册模型
            /** @var PhotoAlbumModel $photoAlbumModel */
            $photoAlbumModel = model(PhotoAlbumModel::class);
            $uploadService = new UploadService();
            $image = filterImgDomain($paramsArray['image']);
            $photoAlbumData = [
                'shop_id' => $shopInfo['id'],
                'type' => 1,
                'image' => $image,
                'thumb_image' => $uploadService->getImageThumbName($image, 2),
                'generate_time' => date('Y-m-d H:i:s'),
            ];
            if (isset($paramsArray['name']) && $paramsArray['name'] != '') {
                $photoAlbumData['name'] = $paramsArray['name'];
            }
            $result = $photoAlbumModel->insertGetId($photoAlbumData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess(['photo_id' => $result]);
        } catch (\Exception $e) {
            $logContent = '新增商家相册接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 删除视频接口
     * @return json
     */
    public function deleteVideo()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
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
            $shopInfo = $this->request->selected_shop;
            // 实例化视频模型
            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $videoData = ['status' => 2];
            $videoWhere = [
                ['shop_id', '=', $shopInfo['id']],
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
            $logContent = '删除视频接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 删除商家相册接口
     * @return json
     */
    public function deleteMerchantAlbum()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PublishValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('deleteMerchantAlbum')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $shopInfo = $this->request->selected_shop;
            // 实例化相册模型
            $photoAlbumModel = model(PhotoAlbumModel::class);
            $photoAlbumData = [
                'status' => 0
            ];
            $photoAlbumWhere = [
                ['shop_id', '=', $shopInfo['id']],
                ['status', '=', 1],
                ['id', 'in', trim($paramsArray['photo_id'], ',')]
            ];
            $result = $photoAlbumModel->where($photoAlbumWhere)->update($photoAlbumData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '删除商家相册接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}

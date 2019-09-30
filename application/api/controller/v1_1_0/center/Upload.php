<?php

namespace app\api\controller\v1_1_0\center;

use app\api\Presenter;
use app\api\controller\center\Upload as CommonUpload;

class Upload extends Presenter
{
    /**
     * 上传图片
     * @param int $fromType 来源接口
     * @return json
     */
    public function uploadImg($fromType)
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        switch ($fromType) {
            case 1;
                $imgUploadPath = config('parameters.merchant_album_upload_path');
                $apiText = '上传商家相册图片接口';
                break;
            case 2;
                $imgUploadPath = config('parameters.merchant_recommend_upload_path');
                $apiText = '上传店铺推荐图片接口';
                break;
            case 3;
                $imgUploadPath = config('parameters.merchant_activity_upload_path');
                $apiText = '上传商家活动图片接口';
                break;
        }
        try {
            $imageMaxSize = config('parameters.upload_size_level_3');
            $imageThumbSize = config('parameters.img_thumb_size_level_3');
            $uploadPath = $imgUploadPath . '/' . date('Ymd', time());
            $commonUpload = new CommonUpload();
            // 获取图片并保存
            $imageInfo = $commonUpload->getImageAndSave($imageMaxSize, $imageThumbSize, $uploadPath);
            if (!$imageInfo['result']) {
                return apiError($imageInfo['error']);
            }
            $responseData = [
                'image' => $imageInfo['image'],
                'thumb_image' => $imageInfo['thumb_image'],
                'image_with_domain' => $imageInfo['image_with_domain'],
                'thumb_image_with_domain' => $imageInfo['thumb_image_with_domain']
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = $apiText.'异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 上传商家相册图片
     * @return json
     */
    public function uploadMerchantAlbumImg()
    {
        return $this->uploadImg(1);
    }

    /**
     * 上传店铺推荐图片
     * @return json
     */
    public function uploadMerchantRecommendImg()
    {
        return $this->uploadImg(2);
    }

    /**
     * 上传商家活动图片
     * @return json
     */
    public function uploadMerchantActivityImg()
    {
        return $this->uploadImg(3);
    }
}

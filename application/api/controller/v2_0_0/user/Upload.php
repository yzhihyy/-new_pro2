<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use app\api\service\UploadService;

class Upload extends Presenter
{
    /**
     * 上传用户头像
     *
     * @return \think\response\Json
     */
    public function userAvatar()
    {
        $uploadImage = input('file.file');
        try {
            $uploadService = new UploadService();
            $uploadResult  = $uploadService->setConfig([
                'image_upload_path' => config('parameters.user_avatar_upload_path'),
                'image_max_size'    => config('parameters.user_avatar_max_size'),
                // 图片地址是否拼接域名
                'image_domain_flag' => true,
                // 缩略图配置
                'image_thumb_flag'  => true,
                'image_thumb_size'  => config('parameters.user_thumb_avatar_size'),
                // 水印配置
                'image_water_flag'  => false,
                'image_water_locate',
                'image_water_alpha'
            ])->uploadImage($uploadImage);
            if (!$uploadResult['code']) {
                return apiError($uploadResult['msg']);
            }
            $responseData = [
                'image' => $uploadResult['data']['image'],
                'thumb_image' => $uploadResult['data']['thumb_image']
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog("上传用户头像接口异常：{$e->getMessage()}");
        }
        return apiError();
    }
}

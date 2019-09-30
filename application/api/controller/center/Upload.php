<?php

namespace app\api\controller\center;

use app\api\Presenter;
use think\Image as ThinkImage;

class Upload extends Presenter
{
    /**
     * 上传用户头像
     *
     * @return \think\response\Json
     */
    public function userAvatar()
    {
        if ($this->request->isPost()) {
            try {
                $imageMaxSize = config('parameters.user_avatar_max_size');
                $imageThumbSize = config('parameters.user_thumb_avatar_size');
                $uploadPath = config('parameters.user_avatar_upload_path') . '/' . date('Ymd', time());
                // 获取图片并保存
                $imageInfo = $this->getImageAndSave($imageMaxSize, $imageThumbSize, $uploadPath);
                if ($imageInfo['result']) {
                    $responseData = [
                        'image' => $imageInfo['image'],
                        'thumb_image' => $imageInfo['thumb_image'],
                        'image_with_domain' => $imageInfo['image_with_domain'],
                        'thumb_image_with_domain' => $imageInfo['thumb_image_with_domain']
                    ];
                    return apiSuccess($responseData);
                } else {
                    return apiError($imageInfo['error']);
                }
            } catch (\Exception $e) {
                $logContent = '文件上传异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }

    /**
     * 获取图片并保存
     *
     * @param $imageMaxSize
     * @param $imageThumbSize
     * @param $uploadPath
     *
     * @return array
     */
    public function getImageAndSave($imageMaxSize, $imageThumbSize, $uploadPath)
    {
        /**
         * @var \think\File $file
         */
        $return = [
            'result' => false,
            'error' => '',
            'image' => '',
            'thumb_image' => '',
            'image_with_domain' => '',
            'thumb_image_with_domain' => ''
        ];
        try {
            // 获取图片并校验
            $file = input('file.file');
            if (empty($file)) {
                $return['error'] = '文件不可为空';
                return $return;
            }
            $imageExt = config('parameters.image_ext');
            $imageMime = config('parameters.image_mime');
            list($thumb_w, $thumb_h) = $imageThumbSize;
            $checkResult = $file->check(['size' => 1024 * 1024 * $imageMaxSize, 'ext' => implode(',', $imageExt), 'type' => implode(',', $imageMime)]);
            if (!$checkResult) {
                $return['error'] = $file->getError();
                return $return;
            }
            // 保存图片
            $image = ThinkImage::open($file);
            $savePath = config('app.image_server_root_path') . $uploadPath;
            $fileInfo = $file->rule('uniqid')->move($savePath);
            if ($fileInfo) {
                // 居中裁剪
                $fileName = $fileInfo->getFilename();
                list($name, $ext) = explode('.', $fileName);
                $thumbFileName = $name . '_thumb.' . $ext;
                $image->thumb($thumb_w, $thumb_h, ThinkImage::THUMB_CENTER)->save($savePath . '/' . $thumbFileName);
                // 返回图片和缩略图
                $domain = config('app.resources_domain');
                $return['result'] = true;
                $return['image'] = $uploadPath . '/' . $fileName;
                $return['thumb_image'] = $uploadPath . '/' . $thumbFileName;
                $return['image_with_domain'] = $domain . $uploadPath . '/' . $fileName;
                $return['thumb_image_with_domain'] = $domain . $uploadPath . '/' . $thumbFileName;
                return $return;
            } else {
                // 上传失败获取错误信息
                $return['error'] = $file->getError();
                return $return;
            }
        } catch (\Exception $e) {
            $logContent = '文件上传异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            $return['error'] = config('response.msg5');
            return $return;
        }
    }
}

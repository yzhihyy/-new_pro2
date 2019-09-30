<?php

namespace app\api\service;

use app\api\Presenter;
use think\File;
use think\Image;

class UploadService extends Presenter
{
    private $config;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->config = [
            'image_allowed_extension' => config('parameters.image_ext'),
            'image_allowed_mime' => config('parameters.image_mime'),
            'image_max_size' => config('parameters.image_max_size'),
            'image_default_water' => config('parameters.image_default_water'),
        ];
    }

    /**
     * Set Config.
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 上传图片
     *
     * @param File $uploadImage
     *
     * @return array
     *
     * @throws \Exception
     */
    public function uploadImage($uploadImage): array
    {
        if (empty($uploadImage) || !($uploadImage instanceof File)) {
            return $this->response(config('response.msg53'));
        }

        // 图片校验
        $checkResult = $uploadImage->check([
            'size' => 1024 * 1024 * $this->config['image_max_size'],
            'ext' => implode(',', $this->config['image_allowed_extension']),
            'type' => implode(',', $this->config['image_allowed_mime'])
        ]);
        if (!$checkResult) {
            return $this->response($uploadImage->getError());
        }

        // 图片服务器根目录
        $imageServerRootPath = config('app.image_server_root_path');
        // 图片上传目录
        $imageUploadPath = $this->config['image_upload_path'];
        // 图片的命名规则
        $imageRule = $this->config['image_rule'] ?? 'date';

        try {
            // 保存图片
            $image = Image::open($uploadImage);
            /** @var File $fileInfo */
            $fileInfo = $uploadImage->rule($imageRule)->move($imageServerRootPath . $imageUploadPath);
            if ($fileInfo) {
                // 拼接域名
                $imageDomainFlag = $this->config['image_domain_flag'] ?? true;
                // 原图
                $originalImage = str_replace('\\', '/', $imageUploadPath . '/' . $fileInfo->getSaveName());

                // 生成水印
                $imageWaterFlag = $this->config['image_water_flag'] ?? false;
                if ($imageWaterFlag) {
                    $waterLocate = $this->config['image_water_locate'] ?? Image::WATER_SOUTHEAST;
                    $waterAlpha = $this->config['image_water_alpha'] ?? 100;
                    $image->water($imageServerRootPath . $this->config['image_default_water'], $waterLocate, $waterAlpha)->save($imageServerRootPath . $originalImage);
                }

                // 生成缩略图,默认生成
                $thumbImageFlag = $this->config['image_thumb_flag'] ?? true;
                $thumbImage = '';
                if ($thumbImageFlag) {
                    // 缩略图名称类型,前缀或后缀
                    $thumbImageNameType = $this->config['image_thumb_name_type'] ?? 1;
                    // 缩略图名称
                    $thumbImage = $this->getImageThumbName($originalImage, $thumbImageNameType);
                    // 缩略图大小
                    $thumbImageSize = $this->config['image_thumb_size'] ?? [500, 500];
                    // 裁剪方式
                    $thumbImageType = $this->config['image_thumb_type'] ?? Image::THUMB_CENTER;
                    // 生成缩略图
                    $image->thumb($thumbImageSize[0], $thumbImageSize[1], $thumbImageType)->save($imageServerRootPath . $thumbImage);
                    // 缩略图生成失败
                    if (!file_exists($imageServerRootPath . $thumbImage)) {
                        @unlink($imageServerRootPath . $originalImage);
                        return $this->response(config('response.msg54'));
                    }

                    $imageDomainFlag && ($thumbImage = getImgWithDomain($thumbImage));
                }

                return $this->response('', 1, [
                    'image' => $imageDomainFlag ? getImgWithDomain($originalImage) : $originalImage,
                    'thumb_image' => $thumbImage,
                ]);
            }

            return $this->response($fileInfo->getError());
        } catch (\Exception $e) {
            generateApiLog("文件上传异常信息：{$e->getMessage()}");
        }

        return $this->response(config('response.msg54'));
    }

    /**
     * 获取缩略图名称
     *
     * @param string $originalImage
     * @param int $thumbImageNameType
     *
     * @return string
     */
    public function getImageThumbName($originalImage, $thumbImageNameType = 1)
    {
        if ($thumbImageNameType == 1) {
            $index = strripos($originalImage, '/');
            $thumbPrefix = 'thumb_';
            if ($index !== false) {
                return mb_substr($originalImage, 0, $index + 1) . $thumbPrefix . mb_substr($originalImage, $index + 1);
            }

            return $thumbPrefix . $originalImage;
        }

        list ($imageName, $imageExt) = explode('.', $originalImage);
        return $imageName . '_thumb.' . $imageExt;
    }

    /**
     * Response.
     *
     * @param string $msg
     * @param int $code
     * @param array $data
     *
     * @return array
     */
    public function response($msg = '', $code = 0, $data = [])
    {
        return compact('msg', 'code', 'data');
    }
}

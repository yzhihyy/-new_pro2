<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use app\api\model\v2_0_0\{BannerModel};

class Share extends Presenter
{
    /**
     * 分享
     *
     * @return \think\response\Json
     */
    public function index()
    {
        try {
            $responseData = [
                'title' => config('share.title'),
                'describe' => config('share.describe'),
                'image' => config('resources_domain') . config('share.image'),
                'url' => config('share.url'),
                'face2face_qrcode_image' => $this->generateFace2FaceShareQrCode(),
                'banner_list' => $this->getBannerList()
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '分享接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 生存面对面分享二维码
     *
     * @return string
     *
     * @throws \Endroid\QrCode\Exception\InvalidWriterException
     */
    private function generateFace2FaceShareQrCode()
    {
        $key = 'face2face_qrcode_image';
        $qrcodeImage = getCustomCache($key);
        if (empty($qrcodeImage)) {
            // 文件上传根目录
            $fileRootPath = config('image_server_root_path');
            $fileRootPath = realpath($fileRootPath);
            // 面对面二维码路径
            $qrcodePath = '/face2face';
            // 创建二维码保存目录
            if (!file_exists($fileRootPath . $qrcodePath)) {
                mkdirs($fileRootPath . $qrcodePath);
            }
            // 二维码内容
            $qrcodeContent = config('share.url');
            // 生成二维码
            $qrCode = new QrCode();
            $qrCode->setText($qrcodeContent)
                ->setSize(450)
                ->setWriterByName('png')
                ->setMargin(20)
                ->setEncoding('UTF-8')
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
                ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
                ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
                ->setValidateResult(false);
            // 保存二维码
            $qrcodeImgPath = $qrcodePath . '/' . $this->generateNum() . '.png';
            $writeFile = $fileRootPath . $qrcodeImgPath;
            $qrCode->writeFile($writeFile);
            $qrcodeImage = config('resources_domain') . $qrcodeImgPath;
            // 缓存
            setCustomCache($key, $qrcodeImage);
            return $qrcodeImage;
        } else {
            return $qrcodeImage;
        }
    }

    /**
     * 生成随机数
     *
     * @param string $prefix
     *
     * @return string
     */
    private function generateNum($prefix = '')
    {
        $rndStr = date('YmdHis');
        list($mt) = explode(' ', microtime());
        $millisecondsStr = str_pad(intval($mt * 1000), 3, '0', STR_PAD_LEFT);
        $rnd = rand(1000, 9999);
        return $prefix . $rndStr . $millisecondsStr . $rnd;
    }

    /**
     * 获取分享头部banner列表
     * 
     * @return array
     */
    private function getBannerList()
    {
        /** @var BannerModel $bannerModel */
        $bannerModel = model(BannerModel::class);
        $bannerArray = [];
        try {
            $bannerList = $bannerModel->getBannerList([
                'position' => ['=', 2],
                'status' => ['=', 1]
            ]);
            foreach ($bannerList as $banner) {
                array_push($bannerArray, getImgWithDomain($banner['image']));
            }
        } catch (\Exception $e) {
            $logContent = '获取分享Banner异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return $bannerArray;
    }
}

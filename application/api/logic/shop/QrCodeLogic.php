<?php

namespace app\api\logic\shop;

use app\api\logic\BaseLogic;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Knp\Snappy\Image;
use think\Image as ThinkImage;

class QrCodeLogic extends BaseLogic
{
    /**
     * 生成店铺收款码
     *
     * @param $shop
     * @param $qrcodeContent
     * @param $qrcodePath
     *
     * @return string
     *
     * @throws \Endroid\QrCode\Exception\InvalidWriterException
     */
    public function generateShopQrcode($shop, $qrcodeContent, $qrcodePath)
    {
        // 文件上传根目录
        $fileRootPath = config('image_server_root_path');
        $fileRootPath = realpath($fileRootPath);
        // 创建二维码保存目录
        if (! file_exists($fileRootPath . $qrcodePath)) {
            mkdirs($fileRootPath . $qrcodePath);
        }
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

        // 删除旧文件
        if (! empty($shop['receiptQrCode'])) {
            @unlink($fileRootPath . $shop['receiptQrCode']);
        }

        return $qrcodeImgPath;
    }

    /**
     * 生成店铺海报
     *
     * @param $shop
     * @param $qrcode
     * @param $posterSavePath
     *
     * @return string
     */
    public function generateShopPoster($shop, $qrcode, $posterSavePath)
    {
        // 文件上传根目录
        $fileRootPath = config('image_server_root_path');
        $fileRootPath = realpath($fileRootPath);
        // 创建海报保存目录
        if (! file_exists($fileRootPath . $posterSavePath)) {
            mkdirs($fileRootPath . $posterSavePath);
        }
        // 生成html
        $qrcode = $fileRootPath . $qrcode;
        $logo = $fileRootPath . $shop['shopImage'];
        $bg = $fileRootPath . config('parameters.shop_receipt_qrcode_img_dir') . config('parameters.shop_receipt_qrcode_bg_name');
        $shopName = $shop['shopName'];
        $html = $this->generateHtml($qrcode, $logo, $bg, $shopName);
        // 生成海报
        $osName = PHP_OS;
        if (strpos($osName, 'Linux') !== false) {
            $binary = config('cache_server_root_path') . '/bin/h4cc/wkhtmltoimage-amd64';
        } else {
            $binary = config('cache_server_root_path') . '/bin/wemersonjanuario/64bit/wkhtmltoimage.exe';
        }
        $snappy = new Image($binary);
        $option = [
            'width' => 1417,
            'height' => 2126,
            'quality' => 100
        ];
        // 保存图片
        $posterImage = $posterSavePath . '/' . $this->generateNum() . '.png';
        $savePosterImagePath = $fileRootPath . $posterImage;
        $snappy->generateFromHtml($html, $savePosterImagePath, $option, true);
        // 裁剪
        $image = ThinkImage::open($savePosterImagePath);
        $image->crop(1417, 2126)->save($savePosterImagePath, null, 100);
        // 删除旧文件
        if (! empty($shop['receiptQrCodePoster'])) {
            @unlink($fileRootPath . $shop['receiptQrCodePoster']);
        }
        return $posterImage;
    }

    /**
     * 构造html
     *
     * @param $qrcode
     * @param $logo
     * @param $bg
     * @param $shopName
     *
     * @return string
     */
    private function generateHtml($qrcode, $logo, $bg, $shopName)
    {
        $bg = $this->base64EncodeImage($bg);
        $qrcode = $this->base64EncodeImage($qrcode);
        $logo = $this->base64EncodeImage($logo);
        $name = $shopName;
        // 生成html
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title></title>
    <style>
        body,
        html {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family:"Microsoft YaHei" !important;
        }
        .container {
            width: 100%;
            height: 100%;
            display: flex;
            position: relative;
        }
        .img-bg {
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        .aaa {
            position: relative;
            left: 20.3%;
            top: 27.7%;
            width: 59.06%;
            height: 37.05%;
            z-index: 999;
            border-radius: 7px;
        }
        .img-qrcode {
            position: absolute;
            top: 24%;
            left: 5%;
            width: 90%;
            height: 90%;
        }
        .img-logo {
            position: absolute;
            top: 59%;
            left: 40%;
            width: 20%;
            height: 20%;
        }
        .title {
            position: relative;
            top: 115%;
            display: block;
            width: 88.5%;
            margin: 0 auto;
            font-size: 55px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{$bg}" class="img-bg">
        <div class="aaa">
            <img src="{$qrcode}" class="img-qrcode">
            <img src="{$logo}" class="img-logo">
            <span class="title">{$name}</span>
        </div>
    </div>
</body>
</html>
HTML;
        return $html;
    }

    /**
     * 图片转base64
     *
     * @param $imageFile
     *
     * @return string
     */
    private function base64EncodeImage($imageFile)
    {
        $imageInfo = getimagesize($imageFile);
        $imageData = fread(fopen($imageFile, 'r'), filesize($imageFile));
        $base64Image = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode($imageData));
        return $base64Image;
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
}

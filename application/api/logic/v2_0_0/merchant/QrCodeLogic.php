<?php

namespace app\api\logic\v2_0_0\merchant;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\ShopModel;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Knp\Snappy\Image;
use think\Image as ThinkImage;

class QrCodeLogic extends BaseLogic
{
    /**
     * 获取店铺收款二维码和海报
     * ps: try {} catch {} 内调用
     *
     * @param array $shop
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getShopQrCodeAndPoster($shop)
    {
        /** @var ShopModel $shopModel */
        $shopModel = model(ShopModel::class);
        // 店铺ID
        $shopId = $shop['id'];
        // 店铺二维码
        $qrcode = $shop['receiptQrCode'];
        // 店铺海报
        $poster = $shop['receiptQrCodePoster'];
        // 二维码缓存文件名
        $qrcodeCacheName = sprintf(config('parameters.shop_qrcode_cache_name'), $shopId);
        // 二维码缓存信息
        $cacheArray = unserialize(getCustomCache($qrcodeCacheName));
        // 二维码和海报不存在|没有缓存|缓存内容与商家信息不匹配
        if ((empty($qrcode) && empty($poster)) || empty($cacheArray)
            || ($cacheArray['shop_name'] != $shop['shopName'] || $cacheArray['shop_image'] != $shop['shopImage'])) {
            // 二维码目录
            $qrcodePath = config('parameters.shop_receipt_qrcode_img_dir') . '/' . date('Ymd');
            // 二维码内容
            $qrcodeContent = url('/h5/pay', ['shop_id' => $shopId], true, true);
            // 生成二维码
            $qrcode = $this->generateShopQrcode(['receiptQrCode' => $shop['receiptQrCode']], $qrcodeContent, $qrcodePath);
            // 生成海报
            $poster = $this->generateShopPoster([
                'shopName' => $shop['shopName'],
                'shopImage' => $shop['shopImage'],
                'receiptQrCodePoster' => $shop['receiptQrCodePoster']
            ], $qrcode, $qrcodePath);

            // 更新商家信息
            $shopModel->save([
                'receipt_qr_code' => $qrcode,
                'receipt_qr_code_poster' => $poster,
            ], ['id' => $shopId]);
            // 更新缓存
            setCustomCache($qrcodeCacheName, serialize([
                'shop_image' => $shop['shopImage'],
                'shop_name' => $shop['shopName']
            ]));
        }

        return compact('qrcode', 'poster');
    }

    /**
     * 生成店铺收款码
     *
     * @param array $shop
     * @param string $qrcodeContent
     * @param string $qrcodePath
     *
     * @return string
     *
     * @throws \Endroid\QrCode\Exception\InvalidWriterException
     */
    public function generateShopQrcode($shop, $qrcodeContent, $qrcodePath)
    {
        // 图片服务器根目录
        $fileRootPath = config('image_server_root_path');
        $fileRootPath = realpath($fileRootPath);
        // 创建二维码保存目录
        if (!file_exists($fileRootPath . $qrcodePath)) {
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
        if (!empty($shop['receiptQrCode'])) {
            @unlink($fileRootPath . $shop['receiptQrCode']);
        }

        return $qrcodeImgPath;
    }

    /**
     * 生成店铺海报
     *
     * @param array $shop
     * @param string $qrcode
     * @param string $posterSavePath
     *
     * @return string
     */
    public function generateShopPoster($shop, $qrcode, $posterSavePath)
    {
        // 图片服务器根目录
        $fileRootPath = config('image_server_root_path');
        $fileRootPath = realpath($fileRootPath);
        // 创建海报保存目录
        if (!file_exists($fileRootPath . $posterSavePath)) {
            mkdirs($fileRootPath . $posterSavePath);
        }

        // 生成html
        $qrcode = $fileRootPath . $qrcode;
        $logo = $fileRootPath . $shop['shopImage'];
        $bg = $fileRootPath . config('parameters.shop_receipt_qrcode_img_dir') . config('parameters.shop_receipt_qrcode_bg_name');
        $html = $this->generateHtml($qrcode, $logo, $bg, $shop['shopName']);
        // 生成海报
        $osName = PHP_OS;
        if (strpos($osName, 'Linux') !== false) {
            $binary = config('cache_server_root_path') . '/bin/h4cc/wkhtmltoimage-amd64';
        } else {
            $binary = config('cache_server_root_path') . '/bin/wemersonjanuario/64bit/wkhtmltoimage.exe';
        }

        $snappy = new Image($binary);
        $option = [
            'width' => 1535,
            'height' => 2126,
            'quality' => 100
        ];
        // 保存图片
        $posterImage = $posterSavePath . '/' . $this->generateNum() . '.png';
        $savePosterImagePath = $fileRootPath . $posterImage;
        $snappy->generateFromHtml($html, $savePosterImagePath, $option, true);
        // 裁剪
        $image = ThinkImage::open($savePosterImagePath);
        $image->crop(1535, 2126)->save($savePosterImagePath, null, 100);

        // 删除旧文件
        if (!empty($shop['receiptQrCodePoster'])) {
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
            top: 23%;
            left: 8%;
            width: 84%;
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
            position: absolute;
            top: 113%;
            width: 84%;
            left: 8%;
            height: 140px;
            margin: 0 auto;
            font-size: 55px;
            line-height: 70px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .t-ellipsis2 {
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
            <span class="title"><span class="t-ellipsis2">{$name}</span></span>
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

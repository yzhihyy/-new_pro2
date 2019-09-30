<?php

namespace app\common\utils\qrcode;

class Qrcode
{
    public function __construct()
    {
        include_once(__DIR__ . '/phpqrcode/phpqrcode.php');
    }

    /**
     * QR二维码生成图片,不带logo的二维码
     *
     * @param array $params
     * Index Key: value 生成二维码的值； level：容错级别可以默认； size：二维码大小；file_path：二维码文件物理路径，上传用；file_name：二维码文件相对域名路径，显示用
     * @return file_name 二维码图片显示地址
     */

    /**
     * @param $params
     *
     * text 表示生成二位的的信息文本
     * outfile 表示是否输出二维码图片文件，默认否
     * level 表示容错率，也就是有被覆盖的区域还能识别，分别是 L(QR_ECLEVEL_L，7%)，M(QR_ECLEVEL_M，15%)，Q(QR_ECLEVEL_Q，25%)，H(QR_ECLEVEL_H，30%)
     * size 表示生成图片大小，默认是3
     * margin 表示二维码周围边框空白区域间距值
     * saveandprint 表示是否保存二维码并显示
     *
     * @return bool
     */
    public function generateQrcode ($params)
    {
        //二维码内容
        $value = isset($params['value']) ? $params['value'] : 'please input qrcode value!';
        //容错级别
        $errorCorrenctionLevel = isset($params['level']) ? $params['level'] : 'L';
        //二维码图片大小
        $matrixPointSize = isset($params['size']) ? $params['size'] : 2;
        // 文件路径
        $filePath = isset($params['file_path']) ? $params['file_path'] : false;
        // 二维码图边距
        $margin = isset($params['margin']) ? $params['margin'] : 4;
        // 二维码固定宽度
        $qrcodeFixedWidth = isset($params['qrcode_fixed_width']) ? $params['qrcode_fixed_width'] : false;
        // 二维码固定高度
        $qrcodeFixedHeight = isset($params['qrcode_fixed_height']) ? $params['qrcode_fixed_height'] : false;
        // 如果原来图片存在则返回图片路径
        if (file_exists($filePath)) {
            return isset($params['file_name']) ? $params['file_name'] : false;
        }
        //生成二维码
        \Qrcode::png($value, $filePath, $errorCorrenctionLevel, $matrixPointSize, $margin);
        // 返回图片路径
        if (file_exists($filePath)) {
            // 生成固定宽高的二维码
            if ($qrcodeFixedWidth && $qrcodeFixedHeight) {
                list($imgW, $imgH) = getimagesize($filePath);
                $origin = imagecreatefrompng($filePath);
                $img = imagecreatetruecolor($qrcodeFixedWidth, $qrcodeFixedWidth);
                imagecopyresampled($img, $origin, 0, 0, 0, 0, $qrcodeFixedWidth, $qrcodeFixedWidth, $imgW, $imgH);
                @imagepng($img, $filePath);
                imagedestroy($img);
            }

            return isset($params['file_name']) ? $params['file_name'] : false;
        }
        return false;
    }

    /**
     * QR二维码生成图片,带logo的二维码
     *
     * @param array $params
     * Index Key: value 生成二维码的值； level：容错级别可以默认； size：二维码大小；file_path：二维码文件物理路径，上传用；file_name：二维码文件相对域名路径，显示用
     *        logo_path：logo文件物理路径，上传用；logo_name：logo文件相对域名路径，显示用
     * @return file_name 二维码图片显示地址
     */
    public function generateQrcodeWithLogo ($params)
    {
        // logo的文件物理路径
        $logoPath = isset($params['logo_path']) ? $params['logo_path'] : false;
        // 二维码物理文件路径
        $filePath = isset($params['file_path']) ? $params['file_path'] : false;
        // LOGO固定宽度
        $logoFixedWidth = isset($params['logo_fixed_width']) ? $params['logo_fixed_width'] : false;
        // LOGO固定高度
        $logoFixedHeight = isset($params['logo_fixed_height']) ? $params['logo_fixed_height'] : false;
        // LOGO留白
        $logoPadding = isset($params['logo_padding']) ? $params['logo_padding'] : 0;
        if (file_exists($logoPath) && $filePath) {
            // 没带logo的二维码图片相对路径
            $fileName = $this->generateQrcode($params);
            // 没带logo的二维码图片文件字符串
            $qrFile = imagecreatefromstring(file_get_contents($filePath));
            // logo的图片文件字符串
            $logoFile = imagecreatefromstring(file_get_contents($logoPath));
            if (imageistruecolor($logoFile))
            {
                //imagetruecolortopalette($logoFile, false, 65535);//添加这行代码来解决颜色失真问题
                //imagecreatetruecolor($fileWidth, $fileWidth);
//                imagealphablending($logoFile, true);
//                imagesavealpha($logoFile, true);
//                $transColour = imagecolorallocatealpha($logoFile, 0, 0, 0, 127);
//                imagefill($logoFile, 0, 0, $transColour);
            }

            // 生成固定宽度的LOGO并留白
            if ($logoFixedWidth && $logoFixedWidth) {
                list($logoImgW, $logoImgH) = getimagesize($logoPath);
                $logoImg = imagecreatetruecolor($logoFixedWidth, $logoFixedWidth);
                $logoBgColor = imagecolorallocate($logoImg, 255, 255, 255);
                if ($logoPadding) {
                    $logoFixedWidth -= $logoPadding * 2;
                    $logoFixedHeight -= $logoPadding * 2;
                }
                imagefill($logoImg, 0, 0, $logoBgColor);
                imagecopyresampled($logoImg, $logoFile, $logoPadding, $logoPadding, 0, 0, $logoFixedWidth, $logoFixedHeight, $logoImgW, $logoImgH);
                $logoFile = $logoImg;
            }

            // 没带logo的二维码图片宽度
            $fileWidth = imagesx($qrFile);
            // 没带logo的二维码图片高度
            $fileHeight = imagesy($qrFile);
            // logo的图片宽度
            $logoWidth = imagesx($logoFile);
            // logo的图片高度
            $logoHeight = imagesy($logoFile);
            // 缩小后的logo图片宽度
            $logoQrWidth = $fileWidth / 5;
            // 原logo图片缩小的倍率
            $scale = $logoWidth / $logoQrWidth;
            // 缩小后的logo图片高度
            $logoQrHeight = $logoHeight / $scale;
            // 新图片的xy坐标的偏移量
            $fromWidth = ($fileWidth - $logoQrWidth) / 2;
            imagesavealpha($logoFile,true);
            // 重新画图生成图片
            imagecopyresampled($qrFile, $logoFile, $fromWidth, $fromWidth, 0, 0, $logoQrWidth, $logoQrHeight, $logoWidth, $logoHeight);
            // 将新的图片流输出到指定图片文件
            imagepng($qrFile, $filePath);//带Logo二维码的文件名
            isset($logoImg) && @imagedestroy($logoImg);

            return $fileName;
        }
        return null;
    }

    /**
     * QR二维码生成图片,带底部水印；
     * 带logo或者不带logo的二维码
     *
     * @param array $params
     * Index Key: value 生成二维码的值； level：容错级别可以默认； size：二维码大小；file_path：二维码文件物理路径，上传用；file_name：二维码文件相对域名路径，显示用
     *        logo_path：logo文件物理路径，上传用；logo_name：logo文件相对域名路径，显示用；qr_text：二维码水印文字
     * @param integer $type 二维码类别 0：不带logo;1：带logo
     * @return file_name 二维码图片显示地址
     */
    public function generateQrcodeWithText ($params, $type = 0)
    {
        // logo的文件物理路径
        $logoPath = isset($params['logo_path']) ? $params['logo_path'] : false;
        // 二维码物理文件路径
        $filePath = isset($params['file_path']) ? $params['file_path'] : false;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        // 不带logo二维码
        if ($type == 0 && $filePath) {
            // 没带logo的二维码图片相对路径
            $fileName = $this->generateQrcode($params);
        }
        // 带logo二维码
        elseif (file_exists($logoPath) && $filePath) {
            // 带logo的二维码图片相对路径
            $fileName = $this->generateQrcodeWithLogo($params);
        }

        // 二维码生成成功的情况，添加水印文字
        if (isset($fileName)) {
            // 字体文件物理路径
            $fontPath = __DIR__ . '/msyh.ttf';
            if (file_exists($fontPath)) {
                // 打开图片
                $qrFile = imagecreatefromstring(file_get_contents($filePath));
                // 二维码图片宽度
                $fileWidth = imagesx($qrFile);
                //旋转角度
                $circleSize = 0;
                // 字体大小
                $fontSize = $params['size'];
                // 文字的长度
                $textLen = mb_strlen($params['qr_text']);
                // 文字距离左边距像素,一个字占几个像素，所以乘以几个像素就为文字的像素长度
                $left = ($fileWidth - $textLen*$fontSize) / 2;
                // 文字距离顶部边距像素,只要高度能容得下一个字的像素长度
                $top = $fileWidth - $fontSize/2;
                // 文字颜色
                $color = imagecolorallocate($qrFile,0,0,0);
                imagefttext($qrFile, $fontSize, $circleSize, $left, $top, $color, $fontPath, $params['qr_text']); // 创建文字
                if (imageistruecolor($qrFile))
                {
                    imagetruecolortopalette($qrFile, false, 65535);//添加这行代码来解决颜色失真问题
                    //imagecreatetruecolor($fileWidth, $fileWidth);
                }
                // 将新的图片流输出到指定图片文件
                imagepng($qrFile, $filePath);//带水印文字的二维码文件名
                return $fileName;
            }
        }
        return null;
    }

    /**
     * QR二维码生成图片,带底部水印和背景图；
     * 带logo或者不带logo的二维码
     *
     * @param array $params
     * Index Key: value 生成二维码的值； level：容错级别可以默认； size：二维码大小；file_path：二维码文件物理路径，上传用；file_name：二维码文件相对域名路径，显示用
     *        logo_path：logo文件物理路径，上传用；logo_name：logo文件相对域名路径，显示用；qr_text：二维码水印文字；background_path：二维码背景图物理路径
     * @param integer $type 二维码类别 0：不带logo;1：带logo
     * @return file_name 二维码图片显示地址
     */
    public function generateQrcodeWithTextAndBackground ($params, $type = 0)
    {
        // logo的文件物理路径
        $logoPath = isset($params['logo_path']) ? $params['logo_path'] : false;
        // 二维码物理文件路径
        $filePath = isset($params['file_path']) ? $params['file_path'] : false;
        // 背景图物理文件路径
        $backgroundPath = isset($params['background_path']) ? $params['background_path'] : false;
        // 二维码距离顶部的距离
        $qrcodeMarginTop = isset($params['qrcode_margin_top']) ? $params['qrcode_margin_top'] : 0;
        // 二维码空白处margin上下边距
        $qrcodeBlankMargin = isset($params['qrcode_blank_margin']) ? $params['qrcode_blank_margin'] : 0;
        // 底部边距
        $bottomMargin = isset($params['bottom_margin']) ? $params['bottom_margin'] : 0;
        // 字体颜色
        $textColor = isset($params['text_color']) ? $params['text_color'] : '#000000';
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $fileName = '';
        // 带logo二维码
        if (file_exists($logoPath) && $filePath) {
            // 带logo的二维码图片相对路径
            $fileName = $this->generateQrcodeWithLogo($params);
        }
        // 添加背景图
        if (file_exists($backgroundPath)) {
            // =============================添加背景图片段START=================================
            // 没带logo的二维码图片文件字符串
            $backgroundFile = imagecreatefromstring(file_get_contents($backgroundPath));
            // logo的图片文件字符串
            $qrFile = imagecreatefromstring(file_get_contents($filePath));
            if (imageistruecolor($qrFile))
            {
                imagetruecolortopalette($qrFile, false, 65535);//添加这行代码来解决颜色失真问题
            }
            // 带logo的二维码图片宽度
            $fileWidth = imagesx($backgroundFile);
            // 带logo的二维码图片高度
            $fileHeight = imagesy($backgroundFile);
            // 带logo的二维码图片宽度
            $qrFileWidth = imagesx($qrFile);
            // 带logo的二维码图片高度
            $qrFileHeight = imagesy($qrFile);
            // 缩小后的带logo的二维码图片宽度
            $micrifyQrFileWidth = $qrFileWidth;
            // 缩小后的带logo的二维码图片高度
            $micrifyQrFileHeight = $qrFileHeight;
            // 新图片的x坐标的偏移量
            $fromWidth = ($fileWidth - $micrifyQrFileWidth) / 2;
            // 新图片的y坐标的偏移量  总高度有732=存放二维码空白396+上边距244+下边距92；看图计算
            $fromHeight = $qrcodeMarginTop + (396 - $micrifyQrFileWidth)/2 - 10;//10像素为多条边的margin边距总和，调和用，可根据合适度调整
            imagesavealpha($qrFile,true);
            // 重新画图生成图片
            imagecopyresampled($backgroundFile, $qrFile, $fromWidth, $fromHeight, 0, 0, $micrifyQrFileWidth, $micrifyQrFileHeight, $qrFileWidth, $qrFileHeight);
            // =============================添加背景图片段END==================================

            // =============================添加水印片段START=================================
            // 字体文件物理路径
            $fontPath = __DIR__ . '/msyh.ttf';
            if (file_exists($fontPath)) {
                //旋转角度
                $circleSize = 0;
                // 字体大小
                $fontSize = $params['size']*1.3;// 字体大小是在二维码大小基础上乘以倍数，水印文字大小可以在这边调和
                // 文字的长度
                $textLen = mb_strlen($params['qr_text'], "utf-8");
                // 文字所占的像素长度,一个字占几个像素，所以乘以几个像素就为文字的像素长度,1.3为调和使用
                $textPixelsLen = $textLen*$fontSize*1.3;
                // 文字颜色
                $rgbArray = $this->hex2rgb($textColor);
                $color = imagecolorallocate($backgroundFile, $rgbArray['r'], $rgbArray['g'], $rgbArray['b']);
                // 判断文字的像素长度超过二维码宽度就把文字换行
                if ($textPixelsLen > $qrFileWidth) {
                    $firstContent = $this->watermarkContentAutowrap($fontSize, $circleSize, $fontPath, $params['qr_text'], $qrFileWidth);
                    //$params['qr_text2'] = str_replace($firstContent, '', mb_convert_encoding($params['qr_text'], "html-entities", "utf-8"));
                    $params['qr_text2'] = $params['qr_text'];
                    $params['qr_text'] = $firstContent;
                    /*第一行文字水印*/
                    // 文字的长度
                    $textLen = mb_strlen($params['qr_text'], "utf-8");
                    // 文字所占的像素长度,一个字占几个像素，所以乘以几个像素就为文字的像素长度,1.3为调和使用
                    $textPixelsLen = $textLen*$fontSize*1.3;
                    // 文字的像素宽度如果大于二维码宽度就使用二维码图片宽度
                    $textPixelsLen = $textPixelsLen > $qrFileWidth ? $qrFileWidth : $textPixelsLen;
                    // 文字距离左边距像素长度
                    $left = ($fileWidth - $textPixelsLen) / 2;
                    // 文字距离顶部边距像素,只要高度能容得下一个字的像素长度，top=二维码高度+上边距244+二维码空白处margin上下边距28+底部边距的一半92/2；看图计算
                    $top = $qrFileHeight + $qrcodeMarginTop + $qrcodeBlankMargin + $bottomMargin/3;
                    imagefttext($backgroundFile, $fontSize, $circleSize, $left, $top, $color, $fontPath, $params['qr_text']); // 创建文字

                    /*第二行文字水印*/
                    // 文字的长度
                    $textLen = mb_strlen($params['qr_text2'], "utf-8");
                    // 文字所占的像素长度,一个字占几个像素，所以乘以几个像素就为文字的像素长度,1.3为调和使用
                    $textPixelsLen = $textLen*3*1.3;
                    // 文字的像素宽度如果大于二维码宽度就使用二维码图片宽度
                    $textPixelsLen = $textPixelsLen > $qrFileWidth ? $qrFileWidth : $textPixelsLen;
                    // 文字距离左边距像素长度
                    $left = ($fileWidth - $textPixelsLen) / 2;
                    // 文字距离顶部边距像素,只要高度能容得下一个字的像素长度，top=二维码高度+上边距244+二维码空白处margin上下边距28+底部边距的一半92/2；看图计算
                    $top = $qrFileHeight + $qrcodeMarginTop + $qrcodeBlankMargin + $bottomMargin/1.5;
                    imagefttext($backgroundFile, $fontSize, $circleSize, $left, $top, $color, $fontPath, $params['qr_text2']); // 创建文字
                } else {
                    // 文字距离左边距像素长度
                    $left = ($fileWidth - $textPixelsLen) / 2;
                    // 文字距离顶部边距像素,只要高度能容得下一个字的像素长度，top=二维码高度+上边距244+二维码空白处margin上下边距28+底部边距的一半92/2；看图计算
                    $top = $qrFileHeight + $qrcodeMarginTop + $qrcodeBlankMargin + $bottomMargin/2;
                    imagefttext($backgroundFile, $fontSize, $circleSize, $left, $top, $color, $fontPath, $params['qr_text']); // 创建文字
                }
                if (imageistruecolor($backgroundFile))
                {
                    //imagetruecolortopalette($backgroundFile, false, 65535);//添加这行代码来解决颜色失真问题
                    //imagecreatetruecolor($fileWidth, $fileWidth);
                    imagealphablending($backgroundFile, true);
                    imagesavealpha($backgroundFile, true);
                    //$transColour = imagecolorallocatealpha($backgroundFile, 0, 0, 0, 127);
                    //imagefill($backgroundFile, 0, 0, $transColour);
                }
            }
            // =============================添加水印片段END==================================

            // 将新的图片流输出到指定图片文件
            imagepng($backgroundFile, $filePath);//带Logo和背景图二维码的文件名
        }
        return $fileName;
    }

    /**
     * GD库生成图片中文自动换行截取的字符串
     * @param type $fontsize 字体大小
     * @param type $angle 角度
     * @param type $fontface 字体名称
     * @param type $string 字符串
     * @param type $width 预设宽度
     * @return string
     */
    private function watermarkContentAutowrap ($fontsize, $angle, $fontface, &$string, $width)
    {
        $content = '';
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        for ($i=0;$i<mb_strlen($string);$i++) {
            $letter[] = mb_substr($string, $i, 1);
        }
        foreach ($letter as $k => $l) {
            $teststr = $content."".$l;
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                //$content = mb_substr($teststr, 0, mb_strlen($teststr) - 1);
                break;
            }

            $content .= $l;
            unset($letter[$k]);
        }
        //$content = mb_convert_encoding($content, "html-entities","utf-8" );
        $string = implode('', $letter);

        return $content;
    }

    /**
     * 十六进制颜色转RGB
     *
     * @param string $hexColor
     *
     * @return array
     */
    private function hex2rgb($hexColor)
    {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }
}

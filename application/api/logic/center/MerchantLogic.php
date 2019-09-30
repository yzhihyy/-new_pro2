<?php

namespace app\api\logic\center;

use app\api\logic\BaseLogic;
use app\common\utils\date\DateHelper;
use app\common\utils\mcrypt\AesEncrypt;
use app\common\utils\qrcode\Qrcode;
use app\common\utils\string\StringHelper;

class MerchantLogic extends BaseLogic
{
    /**
     * 获取店铺二维码和海报
     *
     * @param $shop 商家信息
     * @param bool|array $cacheArray 商家信息缓存数组
     *
     * @return array
     */
    public function getShopQrcodeAndPoster($shop, $cacheArray = false)
    {
        $codeArray = function ($receiptQrcode, $receiptPoster) {
            return [
                'receipt_qr_code' => $receiptQrcode,
                'receipt_qr_code_poster' => $receiptPoster,
            ];
        };

        $nowTime = DateHelper::getNowDateTime();
        // 文件上传存储目录
        $fileRootPath = config('app.image_server_root_path');
        // 收款二维码目录
        $receiptQrcodePath = config('parameters.shop_receipt_qrcode_img_dir') . $nowTime->format('Ymd') . '/';
        // 收款码内容
        $receiptQrcodeContent = url('/h5/pay', ['shop_id' => $shop->id], true, true);
        // 如果缓存不存在, 无法判断数据库保存的收款码和海报是否为最新, 则重新生成
        if (empty($cacheArray)) {
            $receiptQrcode = $this->generateShopQrcode($shop, $receiptQrcodeContent, $receiptQrcodePath);
            $receiptPoster = $this->generateShopPoster($shop, $receiptQrcodeContent, $receiptQrcodePath);
            return $codeArray($receiptQrcode, $receiptPoster);
        }

        // 店铺收款二维码
        if (empty($shop->receipt_qr_code) || !file_exists($fileRootPath . $shop->receipt_qr_code)) {
            $receiptQrcode = $this->generateShopQrcode($shop, $receiptQrcodeContent, $receiptQrcodePath);
        } else {
            $receiptQrcode = $shop->receipt_qr_code;
        }

        // 店铺收款二维码海报
        if (empty($shop->receipt_qr_code_poster) || !file_exists($fileRootPath . $shop->receipt_qr_code_poster) ||
            (!empty($cacheArray) && $cacheArray['shop_name'] != $shop->shop_name)
        ) {
            $receiptPoster = $this->generateShopPoster($shop, $receiptQrcodeContent, $receiptQrcodePath);
        } else {
            $receiptPoster = $shop->receipt_qr_code_poster;
        }

        return $codeArray($receiptQrcode, $receiptPoster);
    }

    /**
     * 生成店铺二维码
     *
     * @param Shop $shop 商家信息
     * @param array $qrcodeContent 二维码内容
     * @param string $qrcodePath 二维码路径
     *
     * @return string
     */
    public function generateShopQrcode($shop, $qrcodeContent, $qrcodePath)
    {
        $fileRootPath = config('app.image_server_root_path');
        if (!file_exists($fileRootPath . $qrcodePath)) {
            // 创建图片目录
            mkdirs($fileRootPath . $qrcodePath);
        }
        $qrcodeImgPath = $qrcodePath . StringHelper::generateNum() . '.png';
        $params = [
            'level' => 'L',
            'size' => 16,
            'margin' => 1,
            'value' => $qrcodeContent,
            'file_path' => $fileRootPath . $qrcodeImgPath,
            'file_name' => $qrcodeImgPath,
            'qrcode_fixed_width' => 300,
            'qrcode_fixed_height' => 300
        ];

        $qrcodeHandler = new Qrcode();
        $qrcode = $qrcodeHandler->generateQrcode($params);
        // 有旧的收款码则删除
        if (!empty($shop->receipt_qr_code)) {
            // 删除旧文件
            @unlink($fileRootPath . $shop->receipt_qr_code);
        }

        return $qrcode;
    }

    /**
     * 生成店铺海报
     *
     * @param Shop $shop 商家信息
     * @param array $qrcodeContent 二维码内容
     * @param string $qrcodePath 二维码路径
     *
     * @return string
     */
    public function generateShopPoster($shop, $qrcodeContent, $qrcodePath)
    {
        $fileRootPath = config('app.image_server_root_path');
        if (!file_exists($fileRootPath . $qrcodePath)) {
            // 创建图片目录
            mkdirs($fileRootPath . $qrcodePath);
        }

        $posterImgPath = $qrcodePath . StringHelper::generateNum() . '.png';
        // 海报背景
        $backgroundImgPath = config('parameters.shop_receipt_qrcode_img_dir') . config('parameters.shop_receipt_qrcode_bg_name');
        $params = [
            'level' => 'M',
            'size' => 16,
            'margin' => 2,
            'value' => $qrcodeContent,
            'qr_text' => $shop->shop_name,
            'file_path' => $fileRootPath . $posterImgPath,
            'file_name' => $posterImgPath,
            'logo_path' => $fileRootPath . $shop->shop_image,
            'background_path' => $fileRootPath . $backgroundImgPath,
            'qrcode_fixed_width' => 450,
            'qrcode_fixed_height' => 450,
            'qrcode_margin_top' => 138,
            'qrcode_blank_margin' => -30,
            'bottom_margin' => 90,
            'text_color' => '#666666',
            'logo_padding' => 4,
            'logo_fixed_width' => 74,
            'logo_fixed_height' => 74,
        ];

        $qrcodeHandler = new Qrcode();
        $poster = $qrcodeHandler->generateQrcodeWithTextAndBackground($params);

        // 有旧的店铺海报则删除
        if (!empty($shop->receipt_qr_code_poster)) {
            @unlink($fileRootPath . $shop->receipt_qr_code_poster);
        }

        return $poster;
    }

    /**
     * 处理明细记录
     *
     * @param array $transactions
     *
     * @return array
     */
    public function transactionsHandle($transactions)
    {
        $responseData = [];
        // 明细类型说明
        $typeDescArray = [
            1 => '消费买单',
            2 => '消费买单',
            3 => '提现'
        ];
        // 当前年份
        $currentYear = date('Y');
        // 银行和第三方支付图标
        $paymentIconArray = config('parameters.payment_icon');
        foreach ($transactions as $item) {
            $item['generate_time'] = DateHelper::getNowDateTime($item['generate_time']);
            // 默认显示图标
            $recordIcon = $item['avatar'];
            $recordTitle = $item['nickname'];
            // 明细类型
            $type = $item['type'];
            // 明细状态描述
            $statusDesc = '';
            // 提现
            if ($item['type'] == 3) {
                if (isset($paymentIconArray[$item['bank_card_type']])) {
                    $recordIcon = $paymentIconArray[$item['bank_card_type']];
                }
                $recordTitle = $item['bank_card_type'];
                $statusDesc = $item['status'] == 1 ? '审核中' : ($item['status'] == 2 ? '失败' : '成功');
            }
            $dateFormat = 'm月d日 H:i';
            if ($item['generate_time']->format('Y') != $currentYear) {
                $dateFormat = "Y年{$dateFormat}";
            }
            $symbol = $this->isInflowTransaction($type) ? '+' : ($this->isOutflowTransaction($type, $item['status']) ? '-' : '');
            $responseData[] = [
                'record_id' => $item['record_id'],
                'record_icon' => getImgWithDomain($recordIcon),
                'record_title' => $recordTitle,
                'record_time' => $item['generate_time']->format($dateFormat),
                'record_type' => $item['type'],
                'record_type_desc' => $typeDescArray[$type] . $statusDesc,
                'record_flag' => $symbol == '+' ? 1 : ($symbol == '-' ? 2 : 3),
                'record_amount' => $symbol . $item['amount'],
            ];
        }

        return $responseData;
    }

    /**
     * 是否是收入明细
     *
     * @param int $type 明细记录类型
     * 2:商家收入
     *
     * @return bool
     */
    public function isInflowTransaction($type)
    {
        return in_array($type, [2]);
    }

    /**
     * 是否是支出明细
     *
     * @param int $type 明细记录类型 1:用户支出,3:商家提现
     * @param int $status 状态，1：审核中；2：审核失败；3：审核成功或完成
     *
     * @return bool
     */
    public function isOutflowTransaction($type, $status)
    {
        // 如果是提现失败则不算支出
        return (in_array($type, [3]) && $status != 2) || (in_array($type, [1]));
    }

    /**
     * 银行卡号|身份证号校验
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json|array
     */
    public function bankcardIdCardVerify($paramsArray)
    {
        // 取得加密对象
        $aes = new AesEncrypt();
        if (!empty($paramsArray['bankcard_num'])) {
            // 加密的银行卡号解密
            $paramsArray['bankcard_num'] = $aes->aes128cbcHexDecrypt($paramsArray['bankcard_num']);
            // 银行卡号校验
            if (!is_numeric($paramsArray['bankcard_num']) || mb_strlen($paramsArray['bankcard_num']) < 16) {
                return $this->logicResponse(config('response.msg25'));
            }
        }

        if (!empty($paramsArray['identity_card_num'])) {
            $paramsArray['identity_card_num'] = $aes->aes128cbcHexDecrypt($paramsArray['identity_card_num']);
            // 身份证号校验
            if (!preg_match(config('parameters.idcard_number_regex'), $paramsArray['identity_card_num'])) {
                return $this->logicResponse(config('response.msg26'));
            }
        }

        return $this->logicResponse([], $paramsArray);
    }

    /**
     * 校验银行卡缓存次数
     *
     * @param int $shopId
     *
     * @return \think\response\Json
     */
    public function bankcardQueryCountVerify($shopId)
    {
        // 当前日期
        $nowDate = DateHelper::getNowDateTime()->format('Ymd');
        $bankcardVerifyCacheName = sprintf(config('juhe.shop_bankcard_verify_cache_name'), $shopId, $nowDate);
        // 读取用户银行卡校验次数缓存信息
        $cacheArray = unserialize(getCustomCache($bankcardVerifyCacheName));
        if (!empty($cacheArray)) {
            $bankcardVerifyCount = config('juhe.bankcard_verify_count');
            // 判断银行卡校验次数是否超出限制次数
            if ($cacheArray['shop_id'] == $shopId && $cacheArray['count'] >= $bankcardVerifyCount) {
                return sprintf(config('response.msg27'), config('juhe.bankcard_verify_count'));
            }

            $cacheArray['count'] += 1;
        } else {
            $cacheArray = ['shop_id' => $shopId, 'count' => 1];
        }

        // 缓存用户银行卡校验次数信息
        setCustomCache($bankcardVerifyCacheName, serialize($cacheArray));
    }

    /**
     * 银行卡四元素缓存信息
     *
     * @param int $shopId
     * @param string $bankcardNum
     * @param array $result 银行卡四元素接口返回的数组
     * @param string $method
     *
     * @return array|bool
     */
    public function bankcard4Cache($shopId, $bankcardNum, $result = [], $method = 'get')
    {
        // 缓存银行卡信息
        $bankcardInfoFileName = sprintf(config('juhe.shop_bankcard_info_cache_name'), $shopId, $bankcardNum);
        // 读取银行卡缓存信息
        $cacheArray = unserialize(getCustomCache($bankcardInfoFileName));
        if ($method == 'get') {
            return $cacheArray;
        }
        elseif ($method == 'set' && !empty($result)) {
            // 银行卡信息数组
            $bankcardInfoArray = [
                'phone' => $result['mobile'],
                'holder_name' => $result['realname'],
                'bankcard_num' => $result['bankcard'],
                'idcard' => $result['idcard']
            ];
            if (!empty($cacheArray)) {
                $bankcardInfoArray = array_merge($cacheArray, $bankcardInfoArray);
            }

            // 缓存银行卡信息
            setCustomCache($bankcardInfoFileName, serialize($bankcardInfoArray));
        }
    }

    /**
     * 缓存银行卡类别信息
     *
     * @param int $shopId
     * @param array $bankcardNum
     * @param array $result 银行卡类别
     */
    public function bankcardSilkCache($shopId, $bankcardNum, $result)
    {
        // 保存银行卡类型信息到缓存文件
        $bankcardInfoFileName = sprintf(config('juhe.shop_bankcard_info_cache_name'), $shopId, $bankcardNum);
        // 读取银行卡缓存信息
        $cacheArray = unserialize(getCustomCache($bankcardInfoFileName));
        $bankcardInfoArray = [
            'bankcard_num' => $bankcardNum,
            'bank' => $result['bank'],
            'type' => $result['type'],
            'logo' => $result['logo']
        ];
        if (!empty($cacheArray)) {
            $bankcardInfoArray = array_merge($cacheArray, $bankcardInfoArray);
        }

        // 缓存银行卡信息
        setCustomCache($bankcardInfoFileName, serialize($bankcardInfoArray));
    }
}

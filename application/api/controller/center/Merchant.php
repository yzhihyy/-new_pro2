<?php

namespace app\api\controller\center;

use app\api\logic\center\MerchantLogic;
use app\api\Presenter;
use app\common\utils\bankcard\BankcardHelper;
use app\common\utils\date\DateHelper;
use app\common\utils\string\StringHelper;
use app\api\logic\shop\QrCodeLogic;

class Merchant extends Presenter
{
    /**
     * 获取商家申请状态
     * @return json
     */
    public function getMerchantApplyStatus()
    {
        try {
            $status = 0; // 未申请
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺申请模型
            $shopApplyModel = model('api/ShopApply');
            $where = ['user_id' => $userId];
            $shopApplyInfo = $shopApplyModel->where($where)->find();
            if (!empty($shopApplyInfo)) {
                $status = 1; // 申请中
            }
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $where = [
                'user_id' => $userId,
                'account_status' => 1,
                'online_status' => 1,
            ];
            $shopInfo = $shopModel->where($where)->find();
            if (!empty($shopInfo)) {
                $status = 2; // 申请成功
            }
            $data = [
                'apply_status' => $status
            ];
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '获取商家申请状态接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 申请成为商家
     * @return json
     */
    public function applyMerchant()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('applyMerchant')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (!empty($shopInfo)) {
                // 已经是商家
                return apiError(config('response.msg21'));
            }
            // 实例化店铺申请模型
            $shopApplyModel = model('api/ShopApply');
            $shopApplyInfo = $shopApplyModel->where($where)->find();
            if (!empty($shopApplyInfo)) {
                // 已申请过成为商家
                return apiError(config('response.msg22'));
            }
            $shopApplyData = [
                'user_id' => $userId,
                'name' => $paramsArray['shop_name'],
                'phone' => $paramsArray['phone'],
                'address' => $paramsArray['address'],
                'generate_time' => date('Y-m-d H:i:s'),
            ];
            $result = $shopApplyModel->insertGetId($shopApplyData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '申请成为商家接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 免单设置
     * @return json
     */
    public function freeSetting()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('freeSetting')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            $shopData = [
                'free_order_frequency' => $paramsArray['order_frequency'],
            ];
            $result = $shopModel->where($where)->update($shopData);
            if (!($result || $result == 0)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '免单设置接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 店铺余额明细
     *
     * @return \think\response\Json
     */
    public function merchantTransactions()
    {
        // 分页号
        $page = input('page/d', 0);
        if ($page < 0) {
            $page = 0;
        }
        try {
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $shop = $shopModel->getShopInfo([
                'user_id' => $userId,
                'account_status' => 1,
                'online_status' => 1
            ]);
            if (empty($shop)) {
                return apiError();
            }

            // 明细模型
            $transactionsModel = model('api/UserTransactions');
            $transactions = $transactionsModel->getShopTransactions([
                'shop_user_id' => $shop->user_id,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);

            $merchantLogic = new MerchantLogic();
            $data = $merchantLogic->transactionsHandle($transactions);

            return apiSuccess(['transaction_list' => $data]);
        } catch (\Exception $e) {
            $logContent = '商家余额明细接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 商家中心接口
     * @return json
     */
    public function merchantCenter()
    {
        try {
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $shop = $shopModel->getShopInfo(['user_id' => $userId]);
            if (empty($shop)) {
                return apiError(config('response.msg10'));
            }
            $shopId = $shop['id'];
            // 收款二维码目录
            $receiptQrcodePath = config('parameters.shop_receipt_qrcode_img_dir') . '/' . date('Ymd');
            // 收款码内容
            $receiptQrcodeContent = url('/h5/pay', ['shop_id' => $shop->id], true, true);
            // 生成收款码所需参数
            $paramsQrcode = [
                'shopId' => $shop['id'],
                'shopName' => $shop['shop_name'],
                'shopImage' => $shop['shop_image'],
                'receiptQrCode' => $shop['receipt_qr_code'],
                'receiptQrCodePoster' => $shop['receipt_qr_code_poster']
            ];
            // 生成二维码和海报
            $qrcodeLogic = new QrCodeLogic();
            // 收款码缓存文件
            $qrcodeCacheName = sprintf(config('parameters.shop_qrcode_cache_name'), $shopId);
            // 商家二维码缓存信息
            $cacheArray = unserialize(getCustomCache($qrcodeCacheName));
            // 如果二维码和海报不存在则生成
            if (empty($shop['receipt_qr_code']) && empty($shop['receipt_qr_code_poster'])) {
                // 生成收款码
                $receiptQrCode = $qrcodeLogic->generateShopQrcode($paramsQrcode, $receiptQrcodeContent, $receiptQrcodePath);
                // 生成收款码海报
                $receiptQrCodePoster = $qrcodeLogic->generateShopPoster($paramsQrcode, $receiptQrCode, $receiptQrcodePath);
                $qrcodeArray = [
                    'receipt_qr_code' => $receiptQrCode,
                    'receipt_qr_code_poster' => $receiptQrCodePoster,
                ];
            } else {
                // 如果没有缓存或缓存内容发生变化，生成二维码和海报
                if (empty($cacheArray) || ($cacheArray['shop_image'] != $shop['shop_image'] || $cacheArray['shop_name'] != $shop['shop_name'])) {
                    // 生成收款码
                    $receiptQrCode = $qrcodeLogic->generateShopQrcode($paramsQrcode, $receiptQrcodeContent, $receiptQrcodePath);
                    // 生成收款码海报
                    $receiptQrCodePoster = $qrcodeLogic->generateShopPoster($paramsQrcode, $receiptQrCode, $receiptQrcodePath);
                    $qrcodeArray = [
                        'receipt_qr_code' => $receiptQrCode,
                        'receipt_qr_code_poster' => $receiptQrCodePoster,
                    ];
                } else {
                    $qrcodeArray = [
                        'receipt_qr_code' => $shop['receipt_qr_code'],
                        'receipt_qr_code_poster' => $shop['receipt_qr_code_poster'],
                    ];
                }
            }
            // 更新缓存
            if (!empty($qrcodeArray)) {
                $shopModel->save($qrcodeArray, ['id' => $shopId]);
                $cacheArray = serialize(['shop_image' => $shop->shop_image, 'shop_name' => $shop->shop_name]);
                // 缓存收款码信息
                setCustomCache($qrcodeCacheName, $cacheArray);
            }
            // 用户模型
            $userModel = model('api/User');
            $userInfo = $userModel->getUserInfo(['id' => $userId]);
            // 订单模型
            $orderModel = model('api/Order');
            $orderCondition = [
                ['shop_id', '=', $shopId],
                ['order_status', '=', 1],
                ['payment_time', '>=', date('Y-m-d')]
            ];
            // 查询订单统计信息
            $orderInfo = $orderModel->getOrderStatistics($orderCondition);
            // 查询店铺回头客数量（下单次数大于1）
            $shopCustomer = $orderModel->getShopCustomer(['shop_id' => $shopId]);
            $data = [
                'shop_name' => $shop->shop_name,
                'shop_image' => getImgWithDomain($shop->shop_image),
                'shop_thumb_image' => getImgWithDomain($shop->shop_thumb_image),
                'receipt_qrcode' => getImgWithDomain($qrcodeArray['receipt_qr_code']),
                'receipt_qrcode_poster' => getImgWithDomain($qrcodeArray['receipt_qr_code_poster']),
                'money' => $userInfo['money'],
                'today_earning' => $orderInfo['payment_amount_sum'],
                'shop_old_customer' => $shopCustomer[0]['id_count'] ?: 0,
                'free_order_frequency' => $shop->free_order_frequency,
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '我的中心接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 商家提现银行卡四元素校验
     *
     * @return \think\response\Json
     */
    public function bankcard4Verify()
    {
        $userId = $this->getUserId();
        try {
            // 获取请求参数
            $paramsArray = input('post.');
            // 实例化验证器
            $validate = validate('api/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('bankcard4Verify')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }

            $merchantLogic = new MerchantLogic();
            // 银行卡号|身份证号校验
            $cardVerify = $merchantLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $shop = $shopModel->getShopInfo(['user_id' => $userId]);
            if (empty($shop)) {
                return apiError(config('response.msg10'));
            }

            $queryCountVerify = $merchantLogic->bankcardQueryCountVerify($shop->id);
            if (!empty($queryCountVerify)) {
                return apiError($queryCountVerify);
            }

            $paramsArray['user_id'] = $userId;
            $bankcardHelper = new BankcardHelper();
            // 银行卡四元素校验
            $result = $bankcardHelper->bankcardVerify4($paramsArray);
            if ($result['error_code'] != 0) {
                return apiError($result['reason']);
            }

            $bankData = $result['result'];
            if (isset($bankData['res']) && $bankData['res'] == 1) {
                $merchantLogic->bankcard4Cache($shop->id, $paramsArray['bankcard_num'], $bankData, 'set');

                return apiSuccess();
            }
            // 银行卡信息认证信息不匹配
            elseif (isset($bankData['res']) && $bankData['res'] == 2) {
                return apiError($bankData['message']);
            }
        } catch (\Exception $e) {
            generateApiLog('商家银行卡四元素校验接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 商家提现银行卡类别查询
     *
     * @return \think\response\Json
     */
    public function bankcardQuery()
    {
        $userId = $this->getUserId();
        try {
            // 银行卡号
            $paramsArray['bankcard_num'] = input('post.bankcard_num');
            $merchantLogic = new MerchantLogic();
            // 银行卡号|身份证号校验
            $cardVerify = $merchantLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $shop = $shopModel->getShopInfo(['user_id' => $userId]);
            if (empty($shop)) {
                return apiError(config('response.msg10'));
            }

            // 银行卡类别查询
            $bankcardHelper = new BankcardHelper();
            $result = $bankcardHelper->bankcardQuery(['bankcard_num' => $paramsArray['bankcard_num'], 'user_id' => $userId]);
            if ($result['error_code'] != 0) {
                return apiError($result['reason']);
            }

            $bankData = $result['result'];
            $merchantLogic->bankcardSilkCache($shop->id, $paramsArray['bankcard_num'], $bankData);

            return apiSuccess($bankData);
        } catch (\Exception $e) {
            generateApiLog('商家银行卡类别查询接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 商家提现银行卡校验验证码确认
     *
     * @return \think\response\Json
     */
    public function bankcardCodeVerify()
    {
        $userId = $this->getUserId();
        try {
            // 获取请求参数
            $paramsArray = input('post.');
            // 实例化验证器
            $validate = validate('api/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('bankcardCodeVerify')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }

            $merchantLogic = new MerchantLogic();
            // 银行卡号|身份证号校验
            $cardVerify = $merchantLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            $captchaModel = model('api/Captcha');
            // 校验验证码是否正确
            $codeVerify = $captchaModel->checkLoginCode([
                'phone' => $paramsArray['phone'],
                'code' => $paramsArray['code']
            ]);
            if (empty($codeVerify)) {
                return apiError(config('response.msg6'));
            }

            // 实例化店铺模型
            $shopModel = model('api/Shop');
            $shop = $shopModel->getShopInfo(['user_id' => $userId]);
            if (empty($shop)) {
                return apiError(config('response.msg10'));
            }

            $bankcard4 = $merchantLogic->bankcard4Cache($shop->id, $paramsArray['bankcard_num']);
            if (!empty($bankcard4)) {
                $shop->save([
                    'withdraw_holder_phone' => $bankcard4['phone'],
                    'withdraw_bankcard_num' => $bankcard4['bankcard_num'],
                    'withdraw_holder_name' => $bankcard4['holder_name'],
                    'withdraw_id_card' => $bankcard4['idcard'],
                    'withdraw_bank_type' => $bankcard4['bank']
                ], ['id' => $shop->id]);

                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog('商家银行卡校验验证码接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 商家提现前信息返回
     *
     * @return \think\response\Json
     */
    public function withdrawPreInfo()
    {
        $userId = $this->getUserId();
        try {
            $userModel = model('api/User');
            $info = $userModel->getUserAndShop($userId);
            if (empty($info) || $info->shop_account_status == 0) {
                return apiError(config('response.msg10'));
            }

            // 判断是否有绑定银行卡信息
            if (empty($info->withdraw_bankcard_num)) {
                $error = config('response.msg29');
                list($code, $msg) = explode('|', $error);
                return apiError($msg, $code);
            }

            $responseData = StringHelper::nullValueToEmptyValue([
                'user_phone' => $info->user_phone,
                'money' => $info->money,
                'bank_card_type' => $info->withdraw_bank_type,
                'bank_card_number' => $info->withdraw_bankcard_num,
                'bank_card_holder_name' => $info->withdraw_holder_name,
                'bank_card_holder_phone' => $info->withdraw_holder_phone,
                'identity_card_number' => $info->withdraw_id_card,
                'withdraw_fee_rate' => $this->decimalFormat($info->withdraw_rate, true)
            ]);

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog('商家提现前信息返回接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 商家提现银行卡更换持卡人
     *
     * @return \think\response\Json
     */
    public function changeCardholder()
    {
        $userId = $this->getUserId();
        try {
            // 获取请求参数
            $paramsArray = input('post.');
            // 实例化验证器
            $validate = validate('api/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('changeCardholder')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }

            $merchantLogic = new MerchantLogic();
            // 银行卡号|身份证号校验
            $cardVerify = $merchantLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            $userModel = model('api/User');
            $info = $userModel->getUserAndShop($userId);
            if (empty($info) || $info->shop_account_status == 0) {
                return apiError(config('response.msg10'));
            }

            // 未绑定身份证
            if (empty($info->withdraw_id_card)) {
                return apiError(config('response.msg30'));
            }

            // 与绑定的身份证号不一致
            if ($paramsArray['identity_card_num'] != $info->withdraw_id_card) {
                return apiError(config('response.msg31'));
            }

            $captchaModel = model('api/Captcha');
            // 校验验证码是否正确
            $codeVerify = $captchaModel->checkLoginCode([
                'phone' => $info->withdraw_holder_phone,
                'code' => $paramsArray['code']
            ]);
            if (empty($codeVerify)) {
                return apiError(config('response.msg6'));
            }

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog('更换持卡人接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 商家提现
     *
     * @return \think\response\Json
     */
    public function withdraw()
    {
        $userId = $this->getUserId();
        try {
            // 获取请求参数
            $paramsArray = input('post.');
            // 实例化验证器
            $validate = validate('api/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('withdraw')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $captchaModel = model('api/Captcha');
            // 校验验证码是否正确
            $codeVerify = $captchaModel->checkLoginCode([
                'phone' => $paramsArray['phone'],
                'code' => $paramsArray['code']
            ]);
            if (empty($codeVerify)) {
                return apiError(config('response.msg6'));
            }

            // 提现额度
            $withdrawAmount = decimalSub($paramsArray['withdraw_amount'], 0);
            // 判断提现最低金额
            $withdrawLowestLimit = 0.01;
            if (decimalComp($withdrawAmount, $withdrawLowestLimit) < 0) {
                return apiError(sprintf(config('response.msg32'), $withdrawLowestLimit));
            }

            $userModel = model('api/User');
            $info = $userModel->getUserAndShop($userId);
            if (empty($info) || $info->shop_account_status == 0) {
                return apiError(config('response.msg10'));
            }

            // 判断提现的额度大于余额,不足则提示余额不足
            if (decimalComp($withdrawAmount, $info->money) == 1) {
                return apiError(config('response.msg33'));
            }

            // 判断是否有绑定银行卡信息
            if (empty($info->withdraw_bankcard_num)) {
                $error = config('response.msg29');
                list($code, $msg) = explode('|', $error);
                return apiError($msg, $code);
            }

            // 提现手续费, 费率最多保留9位小数
            $withdrawFee = decimalMul($withdrawAmount, decimalDiv($info->withdraw_rate, 100, 9));
            // 到账金额(提现金额 - 手续费)
            $withdrawActualAmount = decimalSub($withdrawAmount, $withdrawFee);
            // 提现预计到达天数 1天
            $withdrawPredictTransferDays = 1;
            // 预计到账时间
            $predictTransferDatetime = DateHelper::getNowDateTime()->add(new \DateInterval("P{$withdrawPredictTransferDays}D"))->format('Y年m月d日 H:i');

            // 开启事务
            $userModel->startTrans();

            // 操作后的余额
            $afterAmount = decimalSub($info->money, $withdrawAmount);
            // 减少余额
            $userModel->save(['money' => $afterAmount], ['id' => $info->user_id]);
            // 余额明细数据
            $extraData = [
                'type' => 3,
                'before_amount' => $info->money,
                'after_amount' => $afterAmount,
                'status' => 1,
                'withdraw_actual_amount' => $withdrawActualAmount,
                'withdraw_fee_rate' => $info->withdraw_rate,
                'withdraw_fee' => $withdrawFee,
                'bank_card_holder_name' => $info->withdraw_holder_name,
                'bank_card_number' => $info->withdraw_bankcard_num,
                'bank_card_type' => $info->withdraw_bank_type
            ];
            // 保存交易明细
            $recordResult = $this->saveUserTransactionsRecord($info->user_id, $withdrawAmount, $extraData);

            // 提交事务
            $userModel->commit();

            // 银行卡长度
            $bankcardNumberLen = mb_strlen($info->withdraw_bankcard_num);
            $withdrawInfo = StringHelper::nullValueToEmptyValue([
                'record_id' => $recordResult,
                'predict_transfer_datetime' => $predictTransferDatetime,
                'withdraw_amount' => $withdrawAmount,
                'withdraw_fee' => $withdrawFee,
                'withdraw_actual_amount' => $withdrawActualAmount,
                'bankcard_end_num' => $info->withdraw_bank_type . ' 尾号' . mb_substr($info->withdraw_bankcard_num, $bankcardNumberLen - 4, 4),
                'money' => $afterAmount
            ]);

            return apiSuccess($withdrawInfo);
        } catch (\Exception $e) {
            generateApiLog('商家提现接口异常：' . $e->getMessage());
        }

        return apiError();
    }

}

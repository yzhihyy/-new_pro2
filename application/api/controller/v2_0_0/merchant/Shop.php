<?php

namespace app\api\controller\v2_0_0\merchant;

use app\api\model\v3_0_0\UserMessageModel;
use app\api\Presenter;
use app\common\utils\bankcard\BankcardHelper;
use app\common\utils\date\DateHelper;
use think\Response\Json;
use app\api\service\UploadService;
use app\api\logic\v2_0_0\merchant\{
    BankcardLogic, ShopLogic, QrCodeLogic
};
use app\api\model\v2_0_0\{
    OrderModel, PhotoAlbumModel, ShopRecommendModel, UserHasShopModel, ShopModel, UserTransactionsModel, FreeRuleModel
};
use app\api\validate\v2_0_0\{
    ShopValidate, BankcardValidate
};

class Shop extends Presenter
{
    /**
     * 商家中心
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function merchantCenter()
    {
        try {
            // 店铺信息
            $shop = $this->request->selected_shop;
            // 获取店铺收款二维码和海报
            $qrcodeLogic = new QrCodeLogic();
            $qrcodeResult = $qrcodeLogic->getShopQrCodeAndPoster($shop);
            // 获取商家无权限的菜单
            $shopLogic = new ShopLogic();
            $noPermissionsMenu = $shopLogic->getShopNoPermissionsNodes((string)$shop->authorizedRule);
            // 返回数据
            $responseData = [
                'shop_name' => $shop->shopName,
                'shop_image' => getImgWithDomain($shop->shopImage),
                'shop_thumb_image' => getImgWithDomain($shop->shopThumbImage),
                'shop_balance' => $this->decimalFormat($shop->shopBalance),
                'receipt_qrcode' => getImgWithDomain($qrcodeResult['qrcode']),
                'receipt_qrcode_poster' => getImgWithDomain($qrcodeResult['poster']),
                'no_permissions_menu' => $noPermissionsMenu,
            ];

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog("我的中心接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家余额明细
     *
     * @return Json
     */
    public function merchantTransactions()
    {
        // 分页码
        $page = $this->request->get('page/d', 0);
        $page < 0 && ($page = 0);

        try {
            /** @var UserTransactionsModel $transactionsModel */
            $transactionsModel = model(UserTransactionsModel::class);
            $transactions = $transactionsModel->getShopTransactions([
                'shopId' => $this->request->selected_shop->id,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);

            $shopLogic = new ShopLogic();
            $responseData = $shopLogic->transactionsHandle($transactions);

            return apiSuccess(['transaction_list' => $responseData]);
        } catch (\Exception $e)  {
            generateApiLog("商家余额明细接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家提现银行卡四元素校验
     *
     * @return Json
     */
    public function bankcard4Verify()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, BankcardValidate::class . '.Bankcard4Verify');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $bankcardLogic = new BankcardLogic();
            // 银行卡号|身份证号校验
            $cardVerify = $bankcardLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            // 店铺ID
            $shopId = $this->request->selected_shop->id;
            // 校验银行卡查询次数
            $queryCountVerify = $bankcardLogic->bankcardQueryCountVerify($shopId);
            if (!empty($queryCountVerify)) {
                return apiError($queryCountVerify);
            }

            $paramsArray['user_id'] = $this->request->user->id;
            $bankcardHelper = new BankcardHelper();
            // 银行卡四元素校验
            $result = $bankcardHelper->bankcardVerify4($paramsArray);
            if ($result['error_code'] != 0) {
                return apiError($result['reason']);
            }

            $bankData = $result['result'];
            if (isset($bankData['res']) && $bankData['res'] == 1) {
                $bankcardLogic->bankcard4Cache($shopId, $paramsArray['bankcard_num'], $bankData, 'set');
                return apiSuccess();
            }
            // 银行卡信息认证信息不匹配
            elseif (isset($bankData['res']) && $bankData['res'] == 2) {
                return apiError($bankData['message']);
            }
        } catch (\Exception $e) {
            generateApiLog("商家银行卡四元素校验接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家提现银行卡类别查询
     *
     * @return Json
     */
    public function bankcardQuery()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            $bankcardLogic = new BankcardLogic();
            // 银行卡号校验
            $cardVerify = $bankcardLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            $paramsArray['user_id'] = $this->request->user->id;
            // 银行卡类别查询
            $bankcardHelper = new BankcardHelper();
            $result = $bankcardHelper->bankcardQuery($paramsArray);
            if ($result['error_code'] != 0) {
                return apiError($result['reason']);
            }

            $bankData = $result['result'];
            $bankcardLogic->bankcardSilkCache($this->request->selected_shop->id, $paramsArray['bankcard_num'], $bankData);

            return apiSuccess($bankData);
        } catch (\Exception $e) {
            generateApiLog("商家银行卡类别查询接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家提现银行卡校验验证码确认
     *
     * @return Json
     */
    public function bankcardCodeVerify()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, BankcardValidate::class . '.BankcardCodeVerify');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $bankcardLogic = new BankcardLogic();
            // 银行卡号
            $cardVerify = $bankcardLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            // 校验验证码
            $codeVerify = $this->phoneCodeVerify($paramsArray['phone'], $paramsArray['code']);
            if (!empty($codeVerify)) {
                return apiError($codeVerify);
            }

            // 店铺ID
            $shopId = $this->request->selected_shop->id;
            $bankcard4 = $bankcardLogic->bankcard4Cache($shopId, $paramsArray['bankcard_num']);
            if (!empty($bankcard4)) {
                /** @var ShopModel $shopModel */
                $shopModel = model(ShopModel::class);
                $shopModel::update([
                    'withdraw_holder_phone' => $bankcard4['phone'],
                    'withdraw_bankcard_num' => $bankcard4['bankcard_num'],
                    'withdraw_holder_name' => $bankcard4['holder_name'],
                    'withdraw_id_card' => $bankcard4['idcard'],
                    'withdraw_bank_type' => $bankcard4['bank']
                ], ['id' => $shopId]);

                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("商家银行卡校验验证码接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家提现银行卡更换持卡人
     *
     * @return Json
     */
    public function changeCardholder()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, BankcardValidate::class . '.ChangeCardholder');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $bankcardLogic = new BankcardLogic();
            // 身份证号校验
            $cardVerify = $bankcardLogic->bankcardIdCardVerify($paramsArray);
            if (!empty($cardVerify['msg'])) {
                return apiError($cardVerify['msg']);
            }

            $paramsArray = $cardVerify['data'];
            // 店铺
            $shop = $this->request->selected_shop;
            // 未绑定身份证
            if (empty($shop->withdrawIdCard)) {
                return apiError(config('response.msg30'));
            }

            // 与绑定的身份证号不一致
            if ($paramsArray['identity_card_num'] != $shop->withdrawIdCard) {
                return apiError(config('response.msg31'));
            }

            // 校验验证码
            $codeVerify = $this->phoneCodeVerify($shop->withdrawHolderPhone, $paramsArray['code']);
            if (!empty($codeVerify)) {
                return apiError($codeVerify);
            }

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("商家银行卡更换持卡人接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家提现前信息返回
     *
     * @return Json
     */
    public function withdrawPreInfo()
    {
        try {
            // 店铺
            $shop = $this->request->selected_shop;
            // 判断是否有绑定银行卡信息
            if (empty($shop->withdrawBankcardNum)) {
                list ($code, $msg) = explode('|', config('response.msg29'));
                return apiError($msg, $code);
            }

            // 店铺
            $shop = $this->request->selected_shop;
            $responseData = [
                'user_phone' => $this->request->user->phone,
                'shop_balance' => $this->decimalFormat($shop->shopBalance),
                'bank_card_type' => "{$shop->withdrawBankType}",
                'bank_card_number' => "{$shop->withdrawBankcardNum}",
                'bank_card_holder_name' => "{$shop->withdrawHolderName}",
                'bank_card_holder_phone' => "{$shop->withdrawHolderPhone}",
                'identity_card_number' => "{$shop->withdrawIdCard}",
                'withdraw_fee_rate' => $this->decimalFormat($shop->withdrawRate, true)
            ];

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog("商家提现前信息返回接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家提现
     *
     * @return Json
     */
    public function withdraw()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, BankcardValidate::class . '.Withdraw');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 校验验证码(当前用户)
            $codeVerify = $this->phoneCodeVerify($this->request->user->phone, $paramsArray['code']);
            if (!empty($codeVerify)) {
                return apiError($codeVerify);
            }

            // 提现额度
            $withdrawAmount = $this->decimalFormat($paramsArray['withdraw_amount']);
            // 判断提现最低金额
            $withdrawLowestLimit = 0.01;
            if (decimalComp($withdrawAmount, $withdrawLowestLimit) < 0) {
                return apiError(sprintf(config('response.msg32'), $withdrawLowestLimit));
            }

            // 店铺
            $shop = $this->request->selected_shop;
            // 判断提现的额度大于余额,不足则提示余额不足
            if (decimalComp($withdrawAmount, $shop->shopBalance) == 1) {
                return apiError(config('response.msg33'));
            }

            // 判断是否有绑定银行卡信息
            if (empty($shop->withdrawBankcardNum)) {
                list($code, $msg) = explode('|', config('response.msg29'));
                return apiError($msg, $code);
            }

            // 提现手续费,费率最多保留9位小数
            $withdrawFee = decimalMul($withdrawAmount, decimalDiv($shop->withdrawRate, 100, 9));
            // 到账金额(提现金额 - 手续费)
            $withdrawActualAmount = decimalSub($withdrawAmount, $withdrawFee);
            // 提现预计到达天数 1天
            $withdrawPredictTransferDays = 1;
            // 预计到账时间
            $predictTransferDatetime = DateHelper::getNowDateTime()->add(new \DateInterval("P{$withdrawPredictTransferDays}D"))->format('Y年m月d日 H:i');

            // 开启事务
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $shopModel->startTrans();

            // 当前用户ID
            $userId = $this->request->user->id;
            // 操作前的余额
            $beforeAmount = $shop->shopBalance;
            // 操作后的余额
            $afterAmount = decimalSub($beforeAmount, $withdrawAmount);
            // 减少店铺余额
            $shopModel->save(['balance' => $afterAmount], ['id' => $shop->id]);
            // 店铺余额明细数据
            $extraData = [
                'type' => 3,
                'shop_id' => $shop->id,
                'before_amount' => $beforeAmount,
                'after_amount' => $afterAmount,
                'status' => 1,
                'withdraw_type' => 1,
                'withdraw_actual_amount' => $withdrawActualAmount,
                'withdraw_fee_rate' => $shop->withdrawRate,
                'withdraw_fee' => $withdrawFee,
                'bank_card_holder_name' => $shop->withdrawHolderName,
                'bank_card_number' => $shop->withdrawBankcardNum,
                'bank_card_type' => $shop->withdrawBankType
            ];
            // 保存交易明细
            $recordResult = $this->saveUserTransactionsRecord($userId, $withdrawAmount, $extraData);

            // 提交事务
            $shopModel->commit();

            // 银行卡长度
            $bankcardNumberLen = mb_strlen($shop->withdrawBankcardNum);
            $withdrawInfo = [
                'record_id' => $recordResult,
                'predict_transfer_datetime' => $predictTransferDatetime,
                'withdraw_amount' => $withdrawAmount,
                'withdraw_fee' => $withdrawFee,
                'withdraw_actual_amount' => $withdrawActualAmount,
                'bankcard_end_num' => $shop->withdrawBankType . ' 尾号' . mb_substr($shop->withdrawBankcardNum, $bankcardNumberLen - 4, 4),
                'shop_balance' => $afterAmount
            ];

            return apiSuccess($withdrawInfo);
        } catch (\Exception $e) {
            generateApiLog("商家提现接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家订单列表
     *
     * @return Json
     */
    public function orderList()
    {
        // 分页码
        $page = $this->request->get('page/d', 0);
        $page < 0 && ($page = 0);

        try {
            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            $orderList = $orderModel->getMerchantOrderList([
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
                'shopId' => $this->request->selected_shop->id
            ]);
            foreach ($orderList as &$order) {
                $order = [
                    'order_id' => $order['orderId'],
                    'user_id' => $order['buyerId'],
                    'user_nickname' => $order['buyerNickname'],
                    'user_avatar' => getImgWithDomain($order['buyerAvatar']),
                    'user_thumb_avatar' => getImgWithDomain($order['buyerThumbAvatar']),
                    'pay_time' => dateFormat($order['payTime']),
                    'order_money' => $this->decimalFormat($order['orderMoney']),
                    'pay_money' => $this->decimalFormat($order['payMoney']),
                    'free_flag' => $order['freeFlag'],
                ];
            }
            unset($order);

            return apiSuccess(['order_list' => $orderList]);
        } catch (\Exception $e) {
            generateApiLog("商家订单列表接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 申请店铺
     *
     * @return Json
     */
    public function applyShop()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->param();
            // 用户ID
            $userId = $this->request->user->id;
            // 条件
            $condition = ['shopType' => 1, 'userId' => $userId];
            // 店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 店铺信息
            $shop = $shopModel->getShopInfo($condition);

            $shopLogic = new ShopLogic();
            // 申请店铺|重新申请店铺
            if ($this->request->isGet()) {
                return apiSuccess($shopLogic->getShopInfo($shop));
            }

            // 提交店铺申请
            if ($this->request->isPost()) {
                // 参数校验
                $validateResult = $this->validate($paramsArray, ShopValidate::class . '.ApplyShop');
                if ($validateResult !== true) {
                    return apiError($validateResult);
                }

                if (!empty($shop) && in_array($shop['onlineStatus'], [0, 1, 2])) {
                    $msgKey = $shop['onlineStatus'] == 0 ? 'response.msg67' : ($shop['onlineStatus'] == 1 ? 'response.msg21' : 'response.msg22');
                    return apiError(config($msgKey));
                }

                // 店铺名称已存在
                $repeatShop = $shopModel->getShopInfo(['shopName' => $paramsArray['shop_name']]);
                if (!empty($repeatShop) && $repeatShop['shopId'] != $shop['shopId']) {
                    return apiError(config('response.msg66'));
                }

                // 保存店铺申请
                $shopLogic->saveShop(1, $userId, $paramsArray, $shop, $shopModel);

                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("申请商家接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 申请分店
     *
     * @return Json
     */
    public function applyBranchShop()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->param();
            // 分店信息
            $branchShop = null;
            // 店铺ID
            $shopId = $paramsArray['shop_id'] ?? 0;
            // 用户ID
            $userId = $this->request->user->id;
            // 条件
            $condition = ['shopType' => 2, 'userId' => $userId];
            // 店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 申请分店
            if (is_numeric($shopId) && $shopId) {
                $condition['shopId'] = $shopId;
                $branchShop = $shopModel->getShopInfo($condition);
            }

            $shopLogic = new ShopLogic();
            // 申请分店|重新申请分店
            if ($this->request->isGet()) {
                // 获取主店信息
                if (empty($branchShop)) {
                    $shop = $shopModel->getShopInfo(['shopType' => 1, 'userId' => $this->request->selected_shop->shopUserId]);
                    if (!empty($shop)) {
                        // 电话号码、商家姓名、身份证号码、邀请人自动填充数据，
                        $shop['shopId'] = 0;
                        $shop['shopName'] = $shop['shopAddress'] = $shop['remark'] = '';
                        $shop['onlineStatus'] = -1;
                        $branchShop = $shop;
                    }
                }

                return apiSuccess($shopLogic->getShopInfo($branchShop));
            }

            // 提交分店申请
            if ($this->request->isPost()) {
                // 参数校验
                $validateResult = $this->validate($paramsArray, ShopValidate::class . '.ApplyShop');
                if ($validateResult !== true) {
                    return apiError($validateResult);
                }

                // 已通过
                if (!empty($branchShop) && in_array($branchShop['onlineStatus'], [0, 1, 2])) {
                    $msgKey = $branchShop['onlineStatus'] == 0 ? 'response.msg67' : ($branchShop['onlineStatus'] == 1 ? 'response.msg52' : 'response.msg51');
                    return apiError(config($msgKey));
                }

                // 店铺名称已存在
                $repeatBranchShop = $shopModel->getShopInfo(['shopName' => $paramsArray['shop_name']]);
                if (!empty($repeatBranchShop) && $repeatBranchShop['shopId'] != $branchShop['shopId']) {
                    return apiError(config('response.msg66'));
                }

                // 保存分店申请
                $shopLogic->saveShop(2, $userId, $paramsArray, $branchShop, $shopModel);

                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("申请分店接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 上传商家资料图片
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function uploadMerchantProfileImg()
    {
        $uploadImage = input('file.image');
        try {
            $uploadService = new UploadService();
            $uploadResult = $uploadService->setConfig([
                'image_upload_path' => config('parameters.merchant_identity_card_image_upload_path'),
                'image_max_size' => config('parameters.merchant_identity_card_image_max_size'),
                'image_thumb_flag' => false,
            ])->uploadImage($uploadImage);
            if (!$uploadResult['code']) {
                return apiError($uploadResult['msg']);
            }

            return apiSuccess(['image' => $uploadResult['data']['image']]);
        } catch (\Exception $e) {
            generateApiLog("上传商家资料图片接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 上传商家LOGO
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function uploadMerchantLogo()
    {
        $uploadImage = input('file.image');
        try {
            $uploadService = new UploadService();
            $uploadResult = $uploadService->setConfig([
                'image_upload_path' => config('parameters.merchant_image_upload_path'),
                'image_thumb_name_type' => 2,
            ])->uploadImage($uploadImage);
            if (!$uploadResult['code']) {
                return apiError($uploadResult['msg']);
            }

            return apiSuccess(['image' => $uploadResult['data']['image']]);
        } catch (\Exception $e) {
            generateApiLog("上传商家LOGO接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 我的分店
     *
     * @return Json
     */
    public function myBranchShop()
    {
        try {
            // 用户ID
            $userId = $this->request->user->id;
            // 店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 我的分店
            $myBranchShop = $shopModel->getMyBranchShop(['userId' => $userId]);
            // 店铺状态描述
            $statusText = config('parameters.merchant_status_text');
            $responseData = [];
            foreach ($myBranchShop as $shop) {
                $responseData[] = [
                    'shop_id' => $shop['shopId'],
                    'shop_image' => getImgWithDomain($shop['shopImage']),
                    'shop_thumb_image' => getImgWithDomain($shop['shopThumbImage']),
                    'shop_name' => $shop['shopName'],
                    'online_status' => $shop['onlineStatus'],
                    'status_text' => $statusText[$shop['onlineStatus']] ?? '',
                ];
            }

            return apiSuccess(['list' => $responseData]);
        } catch (\Exception $e) {
            generateApiLog("我的分店接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 商家登录
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function merchantLogin()
    {
        try {
            $responseData = [
                'authorize_shop_flag' => 0,
                'shop_id' => 0,
                'shop_name' => '',
                'shop_address' => '',
                'status' => -1,
                'remark' => '',
                'total_unread_msg_count' => 0
            ];

            // 用户ID
            $userId = $this->request->user->id;
            // 店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            /** @var UserHasShopModel $userHasShopModel */
            $userHasShopModel = model(UserHasShopModel::class);
            // 获取授权店铺
            $authorizedShop = $userHasShopModel->getAuthorizedShop(['type' => 2, 'userId' => $userId]);
            // 用户店铺搜索条件
            $shopCondition = ['shopType' => 1, 'userId' => $userId];

            // 用户无授权店铺
            if (empty($authorizedShop)) {
                // 店铺信息
                $shop = $shopModel->getShopInfo($shopCondition);
                if (!empty($shop)) {
                    if (in_array($shop['onlineStatus'], [0, 1])) {
                        throw new \Exception("用户无授权店铺,店铺却已申请通过,数据不完整,用户ID={$userId}");
                    }

                    $responseData['status'] = $shop['onlineStatus'];
                    $responseData['remark'] = $shop['remark'];
                }

                return apiSuccess($responseData);
            }

            // 所有店铺已下线
            $shopOnlineStatusArray = array_count_values(array_column($authorizedShop, 'onlineStatus'));
            if (isset($shopOnlineStatusArray[0]) && $shopOnlineStatusArray[0] == count($authorizedShop)) {
                // 取消默认店铺
                $userHasShopModel::update(['selected_shop_flag' => 0], ['user_id' => $userId]);
                list ($code, $msg) = explode('|', config('response.msg62'));

                return apiError($msg, $code, ['customer_service_phone' => config('parameters.customer_service_phone')]);
            }

            // 默认店铺
            $defaultShop = $authorizedShop[0]['selectedShopFlag'] ? $authorizedShop[0] : [];
            // 无默认店铺或默认店铺已下线
            if (empty($defaultShop) || $defaultShop['onlineStatus'] == 0) {
                // 店铺信息
                $shop = $shopModel->getShopInfo(array_merge($shopCondition, ['onlineStatus' => 1]));
                // 获取最新的授权店铺
                if (empty($shop)) {
                    foreach ($authorizedShop as $item) {
                        if (empty($shop) && $item['onlineStatus'] == 1) {
                            $shop = $item;
                            break;
                        }
                    }
                }

                // 开启事务
                $userHasShopModel->startTrans();
                // 取消默认店铺
                $userHasShopModel::update(['selected_shop_flag' => 0], ['user_id' => $userId]);
                // 设置默认店铺
                $userHasShopModel::update(['selected_shop_flag' => 1], [
                    'user_id' => $userId,
                    'shop_id' => $shop['shopId']
                ]);
                // 提交事务
                $userHasShopModel->commit();
                // 设置默认店铺ID
                $responseData['shop_id'] = $shop['shopId'];
                $responseData['shop_name'] = $shop['shopName'];
                $responseData['shop_address'] = $shop['shopAddress'];
            }
            // 有默认店铺且店铺已上线
            else {
                $responseData['shop_id'] = $defaultShop['shopId'];
                $responseData['shop_name'] = $defaultShop['shopName'];
                $responseData['shop_address'] = $defaultShop['shopAddress'];
            }

            $unreadMsgCount = UserMessageModel::where(['to_shop_id' => $responseData['shop_id'], 'read_status' => 0, 'delete_status' => 0])->count(1);
            // 该店未读消息数量
            $responseData['total_unread_msg_count'] = $unreadMsgCount;
            // 设置授权店铺标志
            $responseData['authorize_shop_flag'] = 1;
            // 默认店铺已下线,已切换至其他店铺
            if (!empty($defaultShop) && $defaultShop['onlineStatus'] == 0) {
                list ($code, $msg) = explode('|', config('response.msg63'));
                return apiSuccess($responseData, sprintf($msg, $defaultShop['shopName']), $code);
            }

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            isset($userHasShopModel) && $userHasShopModel->rollback();
            generateApiLog("商家登录接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 通知设置
     *
     * @return Json
     */
    public function notificationSetting()
    {
        try {
            $collectPushFlagKey = 'collect_push_flag';
            $shop = $this->request->selected_shop;
            if ($this->request->isGet()) {
                return apiSuccess([$collectPushFlagKey => $shop['collectPushFlag']]);
            }

            if ($this->request->isPost()) {
                $collectPushFlag = $this->request->post($collectPushFlagKey);
                if (!in_array($collectPushFlag, [0, 1])) {
                    return apiSuccess();
                }

                UserHasShopModel::update([$collectPushFlagKey => $collectPushFlag], ['id' => $shop['shopPivotId']]);

                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("店铺通知设置接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 获取免单配置
     *
     * @return Json
     */
    public function getFreeOrderConfiguration()
    {
        try {
            // 当前店铺
            $selectedShop = $this->request->selected_shop;
            // 免单次数
            $freeOrderFrequency = $selectedShop->freeOrderFrequency;

            return apiSuccess(['free_order_frequency' => $freeOrderFrequency]);
        } catch (\Exception $e) {
            generateApiLog("获取免单配置接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 免单设置
     *
     * @return Json
     */
    public function freeOrderSetting()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, ShopValidate::class . '.FreeOrderSetting');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 当前店铺
            $selectedShop = $this->request->selected_shop;
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $where = ['id' => $selectedShop->id];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            // 免单次数改小
            if ($paramsArray['free_order_frequency'] < $shopInfo['free_order_frequency']) {
                $model = $freeRuleModel = model(FreeRuleModel::class);
                // 启动事务
                $model->startTrans();
                $freeRuleWhere = [
                    ['shop_id', '=', $selectedShop->id],
                    ['status', '=', 1],
                    ['shop_free_order_frequency', '>', $paramsArray['free_order_frequency']]
                ];
                // 免单规则的次数更新
                $freeRuleModel->where($freeRuleWhere)->update(['shop_free_order_frequency' => $paramsArray['free_order_frequency']]);
            }
            // 更新免单次数
            $shopModel::update([
                'free_order_frequency' => $paramsArray['free_order_frequency']
            ], $where);
            // 提交事务
            isset($model) && $model->commit();
            return apiSuccess();
        } catch (\Exception $e) {
            // 回滚事务
            isset($model) && $model->rollback();
            generateApiLog("免单设置接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 店铺推荐列表
     *
     * @return Json
     */
    public function getRecommendList()
    {
        // 分页码
        $page = $this->request->get('page/d', 0);
        $page < 0 && ($page = 0);

        try {
            // 店铺ID
            $shopId = $this->request->selected_shop->id;
            /** @var ShopRecommendModel $recommendModel */
            $recommendModel = model(ShopRecommendModel::class);
            // 店铺推荐列表
            $recommendList = $recommendModel->getShopRecommendList([
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
                'shopId' => $shopId,
            ]);
            foreach ($recommendList as &$recommend) {
                $recommend = [
                    'recommend_id' => $recommend['recommendId'],
                    'content' => $recommend['content'],
                    'image' => getImgWithDomain($recommend['image']),
                    'thumb_image' => getImgWithDomain($recommend['thumbImage']),
                    'pageviews' => $recommend['pageviews'],
                    'generate_time' => dateFormat($recommend['generateTime']),
                ];
            }
            unset($recommend);

            return apiSuccess(['list' => $recommendList]);
        } catch (\Exception $e) {
            generateApiLog("店铺推荐列表接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 新增店铺推荐
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function addRecommend()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, ShopValidate::class . '.AddRecommend');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $shopLogic = new ShopLogic();
            $result = $shopLogic->albumParamsValidate($paramsArray['image_list']);
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            // 封面图片
            $coverImage = $result['data']['coverImage'];
            // 图片列表
            $imageList = $result['data']['imageList'];
            // 店铺ID
            $shopId = $this->request->selected_shop->id;
            // 当前时间
            $nowTime = date('Y-m-d H:i:s');
            // 店铺推荐模型
            $recommendModel = model(ShopRecommendModel::class);
            // 开启事务
            $recommendModel->startTrans();

            // 新增店铺推荐
            $recommendId = $recommendModel->insertGetId([
                'shop_id' => $shopId,
                'content' => $paramsArray['content'],
                'image' => $coverImage['image'],
                'thumb_image' => $coverImage['thumb_image'],
                'generate_time' => $nowTime,
            ]);

            // 保存图片
            $photoAlbumModel = model(PhotoAlbumModel::class);
            foreach ($imageList as &$image) {
                unset($image['id']);
                $image['shop_id'] = $shopId;
                $image['relation_id'] = $recommendId;
                $image['type'] = 4;
                $image['generate_time'] = $nowTime;
            }
            unset($image);
            $photoAlbumModel->insertAll($imageList);

            // 提交事务
            $recommendModel->commit();

            return apiSuccess(['recommend_id' => $recommendId]);
        } catch (\Exception $e) {
            isset($recommendModel) && $recommendModel->rollback();
            generateApiLog("新增店铺推荐接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 删除店铺推荐
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function deleteRecommend()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, ShopValidate::class . '.DeleteRecommend');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 店铺ID
            $shopId = $this->request->selected_shop->id;
            /** @var ShopRecommendModel $recommendModel */
            $recommendModel = model(ShopRecommendModel::class);
            $recommend = $recommendModel->where([
                'id' => $paramsArray['recommend_id'],
                'shop_id' => $shopId,
            ])->find();
            if (!empty($recommend)) {
                // 开启事务
                $recommendModel->startTrans();

                // 删除店铺推荐
                $recommend->delete();
                /** @var PhotoAlbumModel $photoAlbumModel */
                $photoAlbumModel = model(PhotoAlbumModel::class);
                // 获取店铺推荐的图片
                $photoAlbums = $photoAlbumModel->getShopPhotoAlbum([
                    'type' => 4,
                    'shopId' => $shopId,
                    'relationId' => $paramsArray['recommend_id']
                ])->toArray();
                if (!empty($photoAlbums)) {
                    // 删除店铺推荐的图片
                    $photoAlbumModel->whereIn('id', array_column($photoAlbums, 'id'))->delete();
                    // 图片服务器根目录
                    $imageServerRootPath = config('app.image_server_root_path');
                    foreach ($photoAlbums as $photoAlbum) {
                        @unlink($imageServerRootPath . $photoAlbum['image']);
                        @unlink($imageServerRootPath . $photoAlbum['thumbImage']);
                    }
                }

                // 提交事务
                $recommendModel->commit();
            }

            return apiSuccess();
        } catch (\Exception $e) {
            isset($recommendModel) && $recommendModel->rollback();
            generateApiLog("删除店铺推荐接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 上传店铺推荐图片
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function uploadRecommendImg()
    {
        $uploadImage = input('file.image');
        try {
            $uploadService = new UploadService();
            $uploadResult = $uploadService->setConfig([
                'image_upload_path' => config('parameters.merchant_recommend_upload_path'),
            ])->uploadImage($uploadImage);
            if (!$uploadResult['code']) {
                return apiError($uploadResult['msg']);
            }

            return apiSuccess(['image' => $uploadResult['data']['image']]);
        } catch (\Exception $e) {
            generateApiLog("上传店铺推荐图片接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 保存商家信息
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function saveInfo()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, ShopValidate::class . '.SaveInfo');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $fieldArray = ['shop_address', 'operation_time', 'announcement'];
            $shopData = [];
            foreach ($fieldArray as $field) {
                if (isset($paramsArray[$field])) {
                    $shopData[$field] = !empty($paramsArray[$field]) ? $paramsArray[$field] : '';
                }
            }
            if (!empty($shopData)) {
                /** @var ShopModel $shopModel */
                $shopModel = model(ShopModel::class);
                $shopModel::update($shopData, ['id' => $this->request->selected_shop->id]);
            }

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("保存商家信息接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 获取店铺详情
     *
     * @return Json
     */
    public function getShopDetail()
    {
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(ShopValidate::class);
            $checkResult = $validate->scene('merchantGetShopDetail')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 获取当前选中店铺
            $shop = $this->request->selected_shop;
            $shopId = $shop['id'];
            // 判断店铺是否存在
            /* @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $shop = $shopModel->getQuery()
                ->where([
                    's.account_status' => 1,
                    's.online_status' => 1,
                    's.id' => $shopId
                ])
                ->find();
            if (!$shop) {
                return apiError(config('response.msg10'));
            }
            // 获取店铺详情
            $where = [
                'shopId' => $shopId,
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude']
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            $shopDetail = $shopModel->getShopDetail($where);
            // 获取消费信息（已消费几次，再消费几次可免单）
            $countAlreadyBuyTimes = $shopDetail['countAlreadyBuyTimes'] ?? 0;
            $countAlsoNeedBuyTimes = $shopDetail['countAlsoNeedBuyTimes'] ?? 0;
            $haveBought = $shopDetail['haveBought'] ?? 0;
            // 订单统计
            $countMyTotalOrder = $shopDetail['countMyTotalOrder'] ?? 0;
            $countMyNormalOrder = $shopDetail['countMyNormalOrder'] ?? 0;
            $countMyFreeOrder = $shopDetail['countMyFreeOrder'] ?? 0;
            // 更新店铺浏览量
            // 响应信息
            $responseData = [
                'shop_id' => $shopDetail['shopId'],
                'shop_name' => $shopDetail['shopName'],
                'announcement' => $shopDetail['announcement'],
                'shop_image' => getImgWithDomain($shopDetail['shopImage']),
                'shop_thumb_image' => getImgWithDomain($shopDetail['shopThumbImage']),
                'shop_address' => $shopDetail['shopAddress'],
                'shop_address_poi' => $shopDetail['shopAddressPoi'],
                'shop_phone' => $shopDetail['shopPhone'],
                'operation_time' => $shopDetail['operationTime'], // 营业时间
                'shop_category_name' => $shopDetail['shopCategoryName'],
                'free_order_frequency' => $shopDetail['freeOrderFrequency'],  // 免单次数
                'how_many_people_bought' => $shopDetail['howManyPeopleBought'],  // 多少人买过
                'order_count' => $shopDetail['countOrder'],
                'distance' => $shopDetail['distance'],
                'show_distance' => $this->showDistance($shopDetail['distance']),
                'have_bought' => $haveBought, // 是否买过
                'count_already_buy_times' => $countAlreadyBuyTimes, // 已消费次数
                'count_also_need_buy_times' => $countAlsoNeedBuyTimes, // 还需消费次数
                'count_total_order' => $countMyTotalOrder, // 消费次数(全部订单)
                'count_normal_order' => $countMyNormalOrder, // 消费次数(普通订单)
                'count_free_order' => $countMyFreeOrder, // 消费次数(免单订单)
                'share_url' => config('app_host') . '/h5/storeDetail.html?shop_id=' . $shopId,
                'views' => $shopDetail['views'], // 浏览量
                'show_views' => $this->showViews($shopDetail['views']),
                'longitude' => $shopDetail['longitude'],
                'latitude' => $shopDetail['latitude']
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '商家端获取店铺详情接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}

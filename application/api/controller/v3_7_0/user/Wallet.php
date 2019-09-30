<?php

namespace app\api\controller\v3_7_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\common\utils\mcrypt\AesEncrypt;
use app\api\model\Order;
use app\api\model\v3_7_0\{
    CopperRuleModel,UserModel,UserTransactionsModel,UserCopperDetailModel
};
use app\api\validate\v3_7_0\{
    WalletValidate
};
use app\api\traits\v3_7_0\PaymentTrait;
use app\api\logic\v3_7_0\user\WalletLogic;

class Wallet extends Presenter
{
    use PaymentTrait;

    /**
     * 我的钱包
     *
     * @return Json
     */
    public function myWallet()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 用户模型
            $userModel = model(UserModel::class);
            $user = $userModel->where(['id' => $userId])->find();
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            $data = [
                'balance' => $this->decimalFormat($user['balance']),
                'copper' => $this->decimalFormat($user['copper_coin'], 1)
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('我的钱包接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 准备充值铜板接口
     *
     * @return Json
     */
    public function preRecharge()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 用户模型
            $userModel = model(UserModel::class);
            $user = $userModel->where(['id' => $userId])->find();

            // 铜板模型
            $copperRuleModel = model(CopperRuleModel::class);
            $copperRule = $copperRuleModel->select()->toArray();
            $copperList = [];
            if (!empty($copperRule)) {
                foreach ($copperRule as $value) {
                    $info = [
                        'copper_id' => $value['id'],
                        'name' => $value['name'],
                        'price' => $value['price'],
                        'copper_count' => $value['copper_count'],
                        'apple_pay_copper_count' => $value['apple_pay_copper_count'],
                        'gift_copper_count' => $value['gift_copper_count'],
                        'apple_item_id' => $value['apple_item_id'],
                    ];
                    $copperList[] = $info;
                }
            }
            // 是否苹果审核状态
            $isAppleReviewStatus = config('parameters.is_apple_review_status');
            // 非苹果审核状态下的苹果支付开关
            $applePayFlag = config('parameters.not_in_review_status_apple_pay_flag');
            // 进入苹果审核状态时
            if ($isAppleReviewStatus) {
                $currentVersion = config('parameters.current_version');
                $version = $this->request->header('version');
                // 当前版本
                if (version_compare($version, $currentVersion) == 0) {
                    $isAppleReviewStatus = 1;
                    $applePayFlag = 1;
                } else {
                    $isAppleReviewStatus = 0;
                    $applePayFlag = config('parameters.not_in_review_status_apple_pay_flag');
                }
            }
            $data = [
                'copper_coin' => (float)$user['copper_coin'],
                'current_version_apple_review_status' => $isAppleReviewStatus,
                'apple_pay_enable_flag' => $applePayFlag,
                'copper_list' => $copperList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('准备充值铜板接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 充值接口
     *
     * @return Json
     */
    public function recharge()
    {
        try {
            // 订单日志路径
            $logPath = config('parameters.order_log_path');
            // 获取请求参数
            $paramsArray = input();
            // 用户id
            $userId = $this->getUserId();
            // 日志信息
            $logContent = '用户id='.$userId.', 下单请求参数信息'.serialize($paramsArray);
            // 日志级别
            $logLevel = config('parameters.log_level');
            // 记录请求日志
            generateCustomLog($logContent, $logPath, $logLevel['info']);

            if (!$userId) {
                return apiError(config('response.msg9'));
            }
            // 实例化验证器
            $validate = validate(WalletValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('recharge')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 铜板id
            $copperId = $paramsArray['copper_id'];
            // 铜板模型
            $copperRuleModel = model(CopperRuleModel::class);
            $copperInfo = $copperRuleModel->where(['id' => $copperId])->find();
            if (empty($copperInfo)) {
                // 铜板不存在
                return apiError(config('response.msg111'));
            }
            // 支付类型
            $paymentType = $paramsArray['payment_type'];
            // 支付金额
            $paymentMoney = $paramsArray['payment_money'];
            // 订单类型
            $orderType = 6;
            // 日期
            $date = date('Y-m-d H:i:s');
            if ($paymentMoney != $copperInfo['price']) {
                // 支付金额错误
                return apiError(config('response.msg23'));
            }
            if ($paymentType == 3) {
                $copper = $copperInfo['apple_pay_copper_count'];
            } else {
                $copper = decimalAdd($copperInfo['copper_count'], $copperInfo['gift_copper_count']);
            }
            // 实例化订单模型
            $orderModel = model(Order::class);
            $orderData = [
                'order_num' => StringHelper::generateNum('FO'),
                'user_id' => $userId,
                'order_amount' => $paymentMoney,
                'payment_type' => $paymentType,
                'payment_amount' => $paymentMoney,
                'generate_time' => $date,
                'order_type' => $orderType,
                'copper_rule_id' => $copperId,
                'copper' => $copper,
                'apple_item_id' => $copperInfo['apple_item_id'],
            ];
            // 平台
            $platform = request()->header('platform');
            if ($platform) {
                $orderData['order_platform'] = $platform;
            }
            // 微信openid
            if (!empty($paramsArray['wechat_mp_openid'])) {
                $orderData['wechat_mp_openid'] = $paramsArray['wechat_mp_openid'];
            }
            // 写入数据
            $orderResult = $orderModel::create($orderData);
            if (empty($orderResult['id'])) {
                return apiError(config('response.msg11'));
            }
            $payInfo = '';

            // 第三方支付需要的交易信息
            $tradeInfo = [
                'num' => $orderResult['order_num'], // 编号
                'money' => $paymentMoney // 金额
            ];
            // 支付宝
            if ($paymentType == 1) {
                // h5支付
                if ($platform == 3) {
                    // 支付完跳转的 url
                    $returnUrl = config('app_host').'/h5/paySuccess.html?serial_number='.$orderResult['order_num'];
                    $extraConfig = ['return_url' => $returnUrl];
                    $payInfo = $this->alipayUnified($tradeInfo, 2, $extraConfig);
                } else {
                    $payInfo = $this->alipayUnified($tradeInfo, 1);
                }
            } elseif ($paymentType == 2) { // 微信
                // h5支付
                if ($platform == 3 || $platform == 4) {
                    // 微信openid
                    if (empty($paramsArray['wechat_mp_openid'])) {
                        return apiError(config('response.msg36'));
                    }
                    $payType = $platform == 3 ? 4 : 2;
                    $payInfo = $this->wxpayUnified($tradeInfo, $payType, $paramsArray['wechat_mp_openid']);
                } else {
                    $payInfo = $this->wxpayUnified($tradeInfo, 1);
                }
            } elseif ($paymentType == 3) {  // 苹果支付
                $payInfo = 'notify_url='.config('applePay.apple_pay_config')['notify_url_v3_7_0'];
            }
            if (empty($payInfo) && $orderResult['order_status'] != 1) {
                return apiError(config('response.msg13'));
            }
            // h5 不加密
            if ($platform != 3) {
                $aes = new AesEncrypt();
                // 加密支付信息
                $payInfo = $aes->aes128cbcEncrypt($payInfo);
            }
            $data = [
                'order_id' => $orderResult['id'],
                'serial_number' => $orderResult['order_num'],
                'pay_info' => $payInfo
            ];
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '充值接口异常：' . $e->getMessage(). ', 出错文件：' . $e->getFile() . ', 出错行号：' . $e->getLine();
            generateCustomLog($logContent, $logPath);
        }
        return apiError();
    }

    /**
     * 用户明细
     *
     * @return Json
     */
    public function transactions()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 请求参数
            $paramsArray = input();
            $transactionsModel = model(UserTransactionsModel::class);
            $transactions = $transactionsModel->getUserTransactions([
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3'),
                'userId' => $userId
            ]);

            $walletLogic = new WalletLogic();
            $transactionList = $walletLogic->transactionsHandle($transactions);

            return apiSuccess(['transaction_list' => $transactionList]);
        } catch (\Exception $e)  {
            generateApiLog("用户余额明细接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 铜板明细列表
     *
     * @return Json
     */
    public function copperRecordList()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 请求参数
            $paramsArray = input();
            // 铜板明细模型
            $userCopperDetailModel = model(UserCopperDetailModel::class);
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3'),
                'userId' => $userId
            ];
            // 获取铜板明细记录
            $copperRecordArray = $userCopperDetailModel->getCopperRecordList($condition);
            $copperRecordList = [];
            if (!empty($copperRecordArray)) {
                // 当前年份
                $currentYear = date('Y');
                // 银行和第三方支付图标
                $paymentIconArray = config('parameters.payment_icon');
                // 类型文字描述
                $typeDescrArray = [
                    1 => '支付宝支付',
                    2 => '微信支付',
                    3 => '苹果支付',
                    4 => '视频通话',
                    5 => '邀请好友',
                    6 => '注册账号',
                ];
                // 默认显示标题
                $txTitle = config('app.app_name');
                // 默认显示图标
                $txIcon = config('app.app_logo');
                foreach ($copperRecordArray as $value) {
                    $time = strtotime($value['generate_time']);
                    if (date('Y', $time) == $currentYear) {
                        $time = date('m月d日 H:i', $time);
                    } else {
                        $time = date('Y年m月d日 H:i', $time);
                    }
                    switch ($value['copper_type']) {
                        case 1;
                        case 2;
                        case 3;
                            $txTitle = '充值';
                            break;
                        case 4;
                            $txTitle = $value['nickname'];
                            $txIcon = $value['thumb_avatar'];
                            break;
                        case 5;
                        case 6;
                            $txTitle = '赠送';
                            break;
                    }
                    if ($value['copper_type'] == 1) {
                        $txIcon = $paymentIconArray['ALIPAY'];
                    } elseif ($value['copper_type'] == 2) {
                        $txIcon = $paymentIconArray['WECHAT'];
                    }
                    $symbol = $value['action_type'] == 1 ? '+' : '-';
                    $info = [
                        'title' => $txTitle,
                        'icon' => getImgWithDomain($txIcon),
                        'type_desc' => $typeDescrArray[$value['copper_type']],
                        'time' => $time,
                        'action_type' => $value['action_type'],
                        'copper_count' => $symbol.$value['copper_count'],
                        'payment_amount' => $value['payment_amount'],
                        'duration' => $value['end_time'] && $value['start_time'] ? ceil((strtotime($value['end_time']) - strtotime($value['start_time']))/60) : 0
                    ];
                    $copperRecordList[] = $info;
                }
            }
            $data = [
                'copper_record_list' => $copperRecordList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('铜板明细列表接口异常：' . $e->getMessage());
        }
        return apiError();
    }
}
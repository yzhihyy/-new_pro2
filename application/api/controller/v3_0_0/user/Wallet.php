<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use think\Response\Json;
use app\api\model\v3_0_0\{
    UserModel, UserTransactionsModel
};
use app\api\validate\v3_0_0\WalletValidate;
use app\api\logic\v3_0_0\user\WalletLogic;

class Wallet extends Presenter
{
    /**
     * 我的钱包
     *
     * @return Json
     */
    public function myWallet()
    {
        try {
            $user = $this->request->user;
            $settings = $this->getSettingsByGroup('user_withdraw');
            $responseData = [
                'authorization_flag' => empty($user->wechatAppOpenid) ? 0 : 1,
                'balance' => $this->decimalFormat($user->balance),
                'phone' => "{$user->phone}",
                'withdraw_lowest_limit' => $this->decimalFormat($settings['lowest_limit']['value'] ?? 1),
                'withdraw_fee_rate' => $this->decimalFormat($settings['fee_rate']['value'] ?? 0, true),
                'copper' => $this->decimalFormat($user->copper_coin, 1)
            ];

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog("我的钱包接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 用户提现绑定第三方
     *
     * @return Json
     */
    public function withdrawBindThirdParty()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, WalletValidate::class . '.WithdrawBindThirdParty');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            /** @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $user = $this->request->user;
            $bindFlag = true;
            $bindData = [];

            if ($paramsArray['third_party_type'] == 1) {
                if ($user->wechatUnionid != $paramsArray['unionid']) {
                    // 一个微信不能绑定多个用户
                    $bindUser = $userModel->where(['wechat_unionid' => $paramsArray['unionid']])->find();
                    if (!empty($bindUser)) {
                        return apiError(config('response.msg88'));
                    }
                }

                $bindData['wechat_nickname'] = $paramsArray['nickname'];
                $bindData['wechat_unionid'] = $paramsArray['unionid'];
                $bindData['wechat_app_openid'] = $paramsArray['openid'];
            }

            if (!empty($bindData)) {
                $res = $userModel->where('id', $this->request->user->id)->update($bindData);
                !$res && $bindFlag = false;
            }

            if ($bindFlag) {
                return apiSuccess();
            }
        } catch (\Exception $e) {
            generateApiLog("提现绑定第三方接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 用户提现
     *
     * @return Json
     */
    public function withdraw()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, WalletValidate::class . '.Withdraw');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 校验验证码
            $codeVerify = $this->phoneCodeVerify($this->request->user->phone, $paramsArray['code']);
            if (!empty($codeVerify)) {
                return apiError($codeVerify);
            }

            // 提现配置
            $settings = $this->getSettingsByGroup('user_withdraw');
            // 提现金额
            $withdrawAmount = $this->decimalFormat($paramsArray['withdraw_amount']);
            // 最低提现金额
            $withdrawLowestLimit = $this->decimalFormat($settings['lowest_limit']['value'] ?? 1);
            if (decimalComp($withdrawAmount, $withdrawLowestLimit) < 0) {
                return apiError(sprintf(config('response.msg32'), $withdrawLowestLimit));
            }

            $user = $this->request->user;
            // 判断提现金额是否大于余额
            if (decimalComp($withdrawAmount, $user->balance) == 1) {
                return apiError(config('response.msg33'));
            }

            // 判断是否已授权
            if (empty($user->wechatAppOpenid)) {
                list($code, $msg) = explode('|', config('response.msg84'));
                return apiError($msg, $code);
            }

            // 提现手续费率
            $withdrawFeeRate = $settings['fee_rate']['value'] ?? 0;
            // 提现手续费,费率最多保留9位小数
            $withdrawFee = decimalMul($withdrawAmount,  decimalDiv($withdrawFeeRate, 100, 9));
            // 到账金额(提现金额 - 手续费)
            $withdrawActualAmount = decimalSub($withdrawAmount, $withdrawFee);

            // 开启事务
            /** @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $userModel->startTrans();

            // 用户ID
            $userId = $this->request->user->id;
            // 操作前的余额
            $beforeAmount = $user->balance;
            // 操作后的余额
            $afterAmount = decimalSub($beforeAmount, $withdrawAmount);
            // 减少用户余额
            $result = $userModel->saveWithOptimLock([
                'balance' => $afterAmount,
            ], [
                ['id', '=', $userId]
            ], $user->lockVersion);
            if (!$result) {
                throw new \Exception("用户余额更新失败,用户ID={$userId}");
            }

            // 保存用户余额明细
            $extraData = [
                'type' => 4,
                'user_id' => $userId,
                'before_amount' => $beforeAmount,
                'after_amount' => $afterAmount,
                'status' => 1,
                'withdraw_type' => 2,
                'withdraw_actual_amount' => $withdrawActualAmount,
                'withdraw_fee_rate' => $withdrawFeeRate,
                'withdraw_fee' => $withdrawFee,
                'wechat_app_openid' => $user->wechatAppOpenid,
            ];
            // 保存交易明细
            $recordResult = $this->saveUserTransactionsRecord($userId, $withdrawAmount, $extraData);

            // 提交事务
            $userModel->commit();

            $responseData = [
                'record_id' => (int)$recordResult,
                'predict_transfer_datetime' => '预计1-3个工作日到账,请您耐心等待',
                'withdraw_amount' => $withdrawAmount,
                'withdraw_fee' => $withdrawFee,
                'withdraw_actual_amount' => $withdrawActualAmount,
                'balance' => $afterAmount
            ];

            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog("用户提现接口异常：{$e->getMessage()}");
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
        // 分页码
        $page = $this->request->get('page/d', 0);
        $page < 0 && ($page = 0);

        try {
            /** @var UserTransactionsModel $transactionsModel */
            $transactionsModel = model(UserTransactionsModel::class);
            $transactions = $transactionsModel->getUserTransactions([
                'userId' => $this->request->user->id,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2')
            ]);

            $walletLogic = new WalletLogic();
            $transactionList = $walletLogic->transactionsHandle($transactions);

            return apiSuccess(['transaction_list' => $transactionList]);
        } catch (\Exception $e)  {
            generateApiLog("用户余额明细接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}